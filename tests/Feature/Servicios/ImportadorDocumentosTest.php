<?php

use App\Exceptions\ArchivoImportacionInvalidoException;
use App\Exceptions\ContribuyenteNoConfiguradoException;
use App\Models\Cliente;
use App\Models\Contribuyente;
use App\Models\Documento;
use App\Models\TipoDte;
use App\Servicios\ImportadorDocumentos;
use Illuminate\Http\UploadedFile;

const NIT_CONTRIBUYENTE_PRUEBA = '1234567-8';

function filaSatDePrueba(array $overrides = []): array
{
    return array_merge([
        'Fecha de emisión' => '2026-05-10T00:00:00-06:00',
        'Número de Autorización' => '11111111-1111-1111-1111-111111111111',
        'Tipo de DTE (nombre)' => 'FACT',
        'Serie' => 'A',
        'Número del DTE' => '1',
        'NIT del emisor' => NIT_CONTRIBUYENTE_PRUEBA,
        'Nombre completo del emisor' => 'Empresa Ejemplo, S.A.',
        'ID del receptor' => '9999999-9',
        'Nombre completo del receptor' => 'Cliente Ejemplo, S.A.',
        'Estado' => 'Vigente',
        'Marca de anulado' => 'No',
        'Moneda' => 'GTQ',
        'Gran Total (Moneda Original)' => 1120.00,
        'IVA (monto de este impuesto)' => 120.00,
        'Petróleo (monto de este impuesto)' => 0,
    ], $overrides);
}

function contribuyenteDePrueba(): Contribuyente
{
    return Contribuyente::factory()->create([
        'nit' => NIT_CONTRIBUYENTE_PRUEBA,
        'nombre' => 'Empresa Ejemplo, S.A.',
    ]);
}

it('es idempotente: importar el mismo archivo dos veces no duplica documentos', function () {
    contribuyenteDePrueba();
    $importador = new ImportadorDocumentos;

    $resultado1 = $importador->importar(construirArchivoSat([filaSatDePrueba()]));
    expect($resultado1->nuevos)->toBe(1)
        ->and($resultado1->actualizados)->toBe(0)
        ->and(Documento::count())->toBe(1);

    $resultado2 = $importador->importar(construirArchivoSat([filaSatDePrueba()]));
    expect($resultado2->nuevos)->toBe(0)
        ->and($resultado2->actualizados)->toBe(1)
        ->and(Documento::count())->toBe(1);
});

it('detecta la dirección comparando el NIT del emisor con el contribuyente configurado', function () {
    contribuyenteDePrueba();

    $filaEmitida = filaSatDePrueba([
        'Número de Autorización' => '22222222-2222-2222-2222-222222222222',
        'NIT del emisor' => NIT_CONTRIBUYENTE_PRUEBA,
        'ID del receptor' => '5555555-5',
        'Nombre completo del receptor' => 'Cliente Receptor, S.A.',
    ]);

    $filaRecibida = filaSatDePrueba([
        'Número de Autorización' => '33333333-3333-3333-3333-333333333333',
        'NIT del emisor' => '6666666-6',
        'Nombre completo del emisor' => 'Proveedor Ejemplo, S.A.',
        'ID del receptor' => NIT_CONTRIBUYENTE_PRUEBA,
    ]);

    (new ImportadorDocumentos)->importar(construirArchivoSat([$filaEmitida, $filaRecibida]));

    $emitida = Documento::where('numero_autorizacion', '22222222-2222-2222-2222-222222222222')->first();
    $recibida = Documento::where('numero_autorizacion', '33333333-3333-3333-3333-333333333333')->first();

    expect($emitida->direccion->value)->toBe('emitida')
        ->and($emitida->cliente->nit)->toBe('5555555-5')
        ->and($recibida->direccion->value)->toBe('recibida')
        ->and($recibida->cliente->nit)->toBe('6666666-6');
});

it('almacena los documentos anulados pero los excluye del cálculo', function () {
    contribuyenteDePrueba();

    $resultado = (new ImportadorDocumentos)->importar(construirArchivoSat([
        filaSatDePrueba(['Marca de anulado' => 'Sí']),
    ]));

    $documento = Documento::first();

    expect($documento->anulado)->toBeTrue()
        ->and($resultado->ignorados)->toBe(1)
        ->and(Documento::query()->paraCalculoIsr($documento->periodo)->count())->toBe(0);
});

it('almacena documentos en moneda distinta a GTQ pero importa con advertencia y los excluye del cálculo', function () {
    contribuyenteDePrueba();

    $resultado = (new ImportadorDocumentos)->importar(construirArchivoSat([
        filaSatDePrueba(['Moneda' => 'USD']),
    ]));

    $documento = Documento::first();

    expect($documento->moneda)->toBe('USD')
        ->and($resultado->ignorados)->toBe(1)
        ->and($resultado->advertencias)->not->toBeEmpty()
        ->and(Documento::query()->paraCalculoIsr($documento->periodo)->count())->toBe(0);
});

it('sugiere tipo combustible para clientes nuevos cuando la columna Petróleo es mayor a cero', function () {
    contribuyenteDePrueba();

    $draft = (new ImportadorDocumentos)->previsualizar(construirArchivoSat([
        filaSatDePrueba(['Petróleo (monto de este impuesto)' => 50.00]),
    ]));

    expect($draft->clientesPorClasificar)->toHaveCount(1)
        ->and($draft->clientesPorClasificar[0]->tipoSugerido)->toBe('combustible');

    (new ImportadorDocumentos)->confirmar($draft);

    expect(Cliente::where('nit', '9999999-9')->first()->tipo_default->value)->toBe('combustible');
});

it('rechaza archivos con extensión inválida', function () {
    contribuyenteDePrueba();

    (new ImportadorDocumentos)->importar(UploadedFile::fake()->create('facturas.txt', 10));
})->throws(ArchivoImportacionInvalidoException::class);

it('rechaza archivos que superan el tamaño máximo permitido', function () {
    contribuyenteDePrueba();

    (new ImportadorDocumentos)->importar(UploadedFile::fake()->create('facturas.xlsx', 6000));
})->throws(ArchivoImportacionInvalidoException::class);

it('rechaza archivos sin la hoja InformacionDTE-FEL', function () {
    contribuyenteDePrueba();

    $archivo = construirArchivoSat([filaSatDePrueba()], ['nombreHoja' => 'OtraHoja']);

    (new ImportadorDocumentos)->importar($archivo);
})->throws(ArchivoImportacionInvalidoException::class);

it('rechaza archivos sin las columnas mínimas requeridas', function () {
    contribuyenteDePrueba();

    $encabezados = [
        'Fecha de emisión',
        'Número de Autorización',
        'Tipo de DTE (nombre)',
        'NIT del emisor',
        'Nombre completo del emisor',
        'ID del receptor',
        'Nombre completo del receptor',
        'Estado',
        'Marca de anulado',
        'Moneda',
        'Gran Total (Moneda Original)',
        // falta "IVA (monto de este impuesto)"
    ];

    $archivo = construirArchivoSat([filaSatDePrueba()], ['encabezados' => $encabezados]);

    (new ImportadorDocumentos)->importar($archivo);
})->throws(ArchivoImportacionInvalidoException::class);

it('crea un tipo_dte nuevo marcado para revisar cuando el código es desconocido', function () {
    contribuyenteDePrueba();

    (new ImportadorDocumentos)->importar(construirArchivoSat([
        filaSatDePrueba(['Tipo de DTE (nombre)' => 'ZZZZ']),
    ]));

    $tipoDte = TipoDte::where('codigo', 'ZZZZ')->first();

    expect($tipoDte)->not->toBeNull()
        ->and($tipoDte->signo)->toBe(1)
        ->and($tipoDte->revisar)->toBeTrue();
});

it('lanza una excepción clara cuando el contribuyente no está configurado', function () {
    (new ImportadorDocumentos)->importar(construirArchivoSat([filaSatDePrueba()]));
})->throws(ContribuyenteNoConfiguradoException::class);

it('captura la columna Petróleo como idp y la resta de la base sin IVA', function () {
    contribuyenteDePrueba();

    (new ImportadorDocumentos)->importar(construirArchivoSat([
        filaSatDePrueba([
            'Gran Total (Moneda Original)' => 1120.00,
            'IVA (monto de este impuesto)' => 120.00,
            'Petróleo (monto de este impuesto)' => 150.00,
        ]),
    ]));

    $documento = Documento::first();

    expect((string) $documento->idp)->toBe('150.00')
        ->and((string) $documento->base_sin_iva)->toBe('850.00');
});

it('fuerza tipo=combustible en un documento con IDP aunque el cliente tenga otro tipo_default', function () {
    contribuyenteDePrueba();
    Cliente::factory()->create(['nit' => '9999999-9', 'tipo_default' => 'bien']);

    (new ImportadorDocumentos)->importar(construirArchivoSat([
        filaSatDePrueba(['Petróleo (monto de este impuesto)' => 150.00]),
    ]));

    $documento = Documento::first();

    expect($documento->tipo->value)->toBe('combustible');
});

it('bloquea la clasificación de tipo cuando todas las filas de un NIT nuevo tienen IDP', function () {
    contribuyenteDePrueba();

    $draft = (new ImportadorDocumentos)->previsualizar(construirArchivoSat([
        filaSatDePrueba(['Petróleo (monto de este impuesto)' => 150.00]),
    ]));

    $candidato = collect($draft->clientesPorClasificar)->firstWhere('nit', '9999999-9');

    expect($candidato->tipoBloqueado)->toBeTrue()
        ->and($candidato->tipoSugerido)->toBe('combustible');

    // Aunque el usuario intente forzar "bien", el documento y el default quedan en combustible.
    (new ImportadorDocumentos)->confirmar($draft, clasificaciones: ['9999999-9' => 'bien']);

    expect(Cliente::where('nit', '9999999-9')->first()?->tipo_default->value)->toBe('combustible')
        ->and(Documento::first()->tipo->value)->toBe('combustible');
});

it('no bloquea la clasificación de tipo cuando un NIT nuevo mezcla filas con y sin IDP', function () {
    contribuyenteDePrueba();

    $filaConIdp = filaSatDePrueba([
        'Número de Autorización' => '77777777-7777-7777-7777-777777777777',
        'Petróleo (monto de este impuesto)' => 150.00,
    ]);
    $filaSinIdp = filaSatDePrueba([
        'Número de Autorización' => '66666666-6666-6666-6666-666666666666',
        'Petróleo (monto de este impuesto)' => 0,
    ]);

    $draft = (new ImportadorDocumentos)->previsualizar(construirArchivoSat([$filaConIdp, $filaSinIdp]));

    $candidato = collect($draft->clientesPorClasificar)->firstWhere('nit', '9999999-9');

    expect($candidato->tipoBloqueado)->toBeFalse();

    // El usuario elige "servicio" como default; el documento CON IDP igual se fuerza a combustible.
    (new ImportadorDocumentos)->confirmar($draft, clasificaciones: ['9999999-9' => 'servicio']);

    $documentoConIdp = Documento::where('numero_autorizacion', '77777777-7777-7777-7777-777777777777')->first();
    $documentoSinIdp = Documento::where('numero_autorizacion', '66666666-6666-6666-6666-666666666666')->first();

    expect(Cliente::where('nit', '9999999-9')->first()?->tipo_default->value)->toBe('servicio')
        ->and($documentoConIdp->tipo->value)->toBe('combustible')
        ->and($documentoSinIdp->tipo->value)->toBe('servicio');
});

it('marca esProveedor solo para NITs nuevos que aparecen en documentos recibidos', function () {
    contribuyenteDePrueba();

    $filaEmitida = filaSatDePrueba([
        'Número de Autorización' => '44444444-4444-4444-4444-444444444444',
        'NIT del emisor' => NIT_CONTRIBUYENTE_PRUEBA,
        'ID del receptor' => '5555555-5',
        'Nombre completo del receptor' => 'Cliente Nuevo, S.A.',
    ]);

    $filaRecibida = filaSatDePrueba([
        'Número de Autorización' => '55555555-5555-5555-5555-555555555555',
        'NIT del emisor' => '6666666-6',
        'Nombre completo del emisor' => 'Proveedor Nuevo, S.A.',
        'ID del receptor' => NIT_CONTRIBUYENTE_PRUEBA,
    ]);

    $draft = (new ImportadorDocumentos)->previsualizar(construirArchivoSat([$filaEmitida, $filaRecibida]));

    $porNit = collect($draft->clientesPorClasificar)->keyBy('nit');

    expect($porNit['5555555-5']->esProveedor)->toBeFalse()
        ->and($porNit['6666666-6']->esProveedor)->toBeTrue()
        ->and($porNit['6666666-6']->generaCreditoSugerido)->toBeTrue();
});

it('aplica la clasificación de crédito fiscal al cliente y a sus documentos recibidos', function () {
    contribuyenteDePrueba();

    $filaRecibida = filaSatDePrueba([
        'Número de Autorización' => '66666666-6666-6666-6666-666666666666',
        'NIT del emisor' => '6666666-6',
        'Nombre completo del emisor' => 'Proveedor Sin Crédito, S.A.',
        'ID del receptor' => NIT_CONTRIBUYENTE_PRUEBA,
    ]);

    $draft = (new ImportadorDocumentos)->previsualizar(construirArchivoSat([$filaRecibida]));

    (new ImportadorDocumentos)->confirmar(
        $draft,
        clasificaciones: ['6666666-6' => 'servicio'],
        clasificacionesCredito: ['6666666-6' => false],
    );

    $cliente = Cliente::where('nit', '6666666-6')->first();
    $documento = Documento::where('numero_autorizacion', '66666666-6666-6666-6666-666666666666')->first();

    expect($cliente->genera_credito_default)->toBeFalse()
        ->and($documento->genera_credito)->toBeFalse();
});

it('deja genera_credito en null para documentos emitidos', function () {
    contribuyenteDePrueba();

    (new ImportadorDocumentos)->importar(construirArchivoSat([filaSatDePrueba()]));

    $documento = Documento::first();

    expect($documento->direccion->value)->toBe('emitida')
        ->and($documento->genera_credito)->toBeNull();
});

it('fuerza genera_credito=false en una FPEQ recibida aunque el usuario elija que sí genera crédito', function () {
    contribuyenteDePrueba();

    $filaFpeq = filaSatDePrueba([
        'Tipo de DTE (nombre)' => 'FPEQ',
        'NIT del emisor' => '8888888-8',
        'Nombre completo del emisor' => 'Vendedor Pequeño Contribuyente',
        'ID del receptor' => NIT_CONTRIBUYENTE_PRUEBA,
        'Nombre completo del receptor' => 'Empresa Ejemplo, S.A.',
    ]);

    $draft = (new ImportadorDocumentos)->previsualizar(construirArchivoSat([$filaFpeq]));

    // El usuario intenta forzar "sí genera crédito"; como esta contraparte solo
    // tiene filas FPEQ en el archivo, la pregunta viene bloqueada y la petición
    // se ignora tanto para el documento como para el default del cliente.
    (new ImportadorDocumentos)->confirmar($draft, clasificacionesCredito: ['8888888-8' => true]);

    $documento = Documento::first();
    $cliente = Cliente::where('nit', '8888888-8')->first();

    expect($cliente->genera_credito_default)->toBeFalse()
        ->and($documento->direccion->value)->toBe('recibida')
        ->and($documento->genera_credito)->toBeFalse();
});

it('bloquea la pregunta de crédito fiscal cuando todas las filas de un proveedor nuevo son FPEQ', function () {
    contribuyenteDePrueba();

    $filaFpeq = filaSatDePrueba([
        'Número de Autorización' => '77777777-7777-7777-7777-777777777777',
        'Tipo de DTE (nombre)' => 'FPEQ',
        'NIT del emisor' => '9999999-1',
        'Nombre completo del emisor' => 'Edwin Antonio, Aroche Alvarez',
        'ID del receptor' => NIT_CONTRIBUYENTE_PRUEBA,
        'Nombre completo del receptor' => 'Empresa Ejemplo, S.A.',
    ]);

    $draft = (new ImportadorDocumentos)->previsualizar(construirArchivoSat([$filaFpeq]));

    $candidato = collect($draft->clientesPorClasificar)->firstWhere('nit', '9999999-1');

    expect($candidato->creditoBloqueado)->toBeTrue()
        ->and($candidato->generaCreditoSugerido)->toBeFalse();
});

it('no bloquea la pregunta de crédito fiscal cuando un proveedor nuevo mezcla FPEQ con otro tipo de DTE', function () {
    contribuyenteDePrueba();

    $filaFpeq = filaSatDePrueba([
        'Número de Autorización' => '66666666-6666-6666-6666-666666666666',
        'Tipo de DTE (nombre)' => 'FPEQ',
        'NIT del emisor' => '9999999-2',
        'Nombre completo del emisor' => 'Proveedor Mixto, S.A.',
        'ID del receptor' => NIT_CONTRIBUYENTE_PRUEBA,
        'Nombre completo del receptor' => 'Empresa Ejemplo, S.A.',
    ]);
    $filaFact = filaSatDePrueba([
        'Número de Autorización' => '55555555-5555-5555-5555-555555555555',
        'Tipo de DTE (nombre)' => 'FACT',
        'NIT del emisor' => '9999999-2',
        'Nombre completo del emisor' => 'Proveedor Mixto, S.A.',
        'ID del receptor' => NIT_CONTRIBUYENTE_PRUEBA,
        'Nombre completo del receptor' => 'Empresa Ejemplo, S.A.',
    ]);

    $draft = (new ImportadorDocumentos)->previsualizar(construirArchivoSat([$filaFpeq, $filaFact]));

    $candidato = collect($draft->clientesPorClasificar)->firstWhere('nit', '9999999-2');

    expect($candidato->creditoBloqueado)->toBeFalse()
        ->and($candidato->generaCreditoSugerido)->toBeTrue();
});

it('fuerza genera_credito=false y bloquea la pregunta de crédito en una FCAP recibida', function () {
    contribuyenteDePrueba();

    $filaFcap = filaSatDePrueba([
        'Tipo de DTE (nombre)' => 'FCAP',
        'NIT del emisor' => '8888888-8',
        'Nombre completo del emisor' => 'Vendedor Pequeño Contribuyente',
        'ID del receptor' => NIT_CONTRIBUYENTE_PRUEBA,
        'Nombre completo del receptor' => 'Empresa Ejemplo, S.A.',
    ]);

    $draft = (new ImportadorDocumentos)->previsualizar(construirArchivoSat([$filaFcap]));
    $candidato = collect($draft->clientesPorClasificar)->firstWhere('nit', '8888888-8');

    (new ImportadorDocumentos)->confirmar($draft, clasificacionesCredito: ['8888888-8' => true]);

    expect($candidato->creditoBloqueado)->toBeTrue()
        ->and(Documento::first()->genera_credito)->toBeFalse()
        ->and(Cliente::where('nit', '8888888-8')->first()->genera_credito_default)->toBeFalse();
});

it('crea un tipo_dte del catálogo con sus datos aunque la base no esté sembrada', function () {
    contribuyenteDePrueba();

    (new ImportadorDocumentos)->importar(construirArchivoSat([
        filaSatDePrueba(['Tipo de DTE (nombre)' => 'FCAP']),
    ]));

    $tipoDte = TipoDte::where('codigo', 'FCAP')->first();

    expect($tipoDte->nombre)->toBe('Factura Cambiaria Pequeño Contribuyente')
        ->and($tipoDte->revisar)->toBeFalse();
});

it('guarda las notas de abono, las cuenta como ignoradas y avisa una sola vez por tipo', function () {
    contribuyenteDePrueba();

    $resultado = (new ImportadorDocumentos)->importar(construirArchivoSat([
        filaSatDePrueba(['Número de Autorización' => 'AAAAAAAA-0000-0000-0000-000000000001', 'Tipo de DTE (nombre)' => 'NABN', 'IVA (monto de este impuesto)' => 0]),
        filaSatDePrueba(['Número de Autorización' => 'AAAAAAAA-0000-0000-0000-000000000002', 'Tipo de DTE (nombre)' => 'NABN', 'IVA (monto de este impuesto)' => 0]),
    ]));

    $notasDeAbono = array_values(array_filter($resultado->advertencias, fn (string $a) => str_contains($a, 'notas de abono')));

    expect(Documento::count())->toBe(2)
        ->and($resultado->ignorados)->toBe(2)
        ->and($notasDeAbono)->toHaveCount(1)
        ->and($notasDeAbono[0])->toStartWith('2 notas de abono (NABN)')
        ->and(Documento::query()->paraCalculoIsr('2026-05')->count())->toBe(0);
});

it('avisa cuando el archivo trae un tipo de DTE desconocido', function () {
    contribuyenteDePrueba();

    $draft = (new ImportadorDocumentos)->previsualizar(construirArchivoSat([
        filaSatDePrueba(['Tipo de DTE (nombre)' => 'ZZZZ']),
    ]));

    expect($draft->advertencias)->toContain('Tipo de DTE desconocido «ZZZZ» en 1 fila: se tomó como factura y sí suma al cálculo. Revísalo antes de declarar.');
});

it('no agrega avisos de tipo de DTE para facturas normales', function () {
    contribuyenteDePrueba();

    $draft = (new ImportadorDocumentos)->previsualizar(construirArchivoSat([filaSatDePrueba()]));

    expect($draft->advertencias)->toBe([]);
});
