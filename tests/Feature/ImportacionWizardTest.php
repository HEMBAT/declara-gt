<?php

use App\Models\Cliente;
use App\Models\Contribuyente;
use App\Models\Documento;

const NIT_CONTRIBUYENTE_WIZARD = '5551234-9';

function contribuyenteWizard(): Contribuyente
{
    return Contribuyente::factory()->create([
        'nit' => NIT_CONTRIBUYENTE_WIZARD,
        'nombre' => 'Empresa Wizard, S.A.',
    ]);
}

function filaWizard(array $overrides = []): array
{
    return array_merge([
        'Fecha de emisión' => '2026-05-10T00:00:00-06:00',
        'Número de Autorización' => '11111111-1111-1111-1111-111111111111',
        'Tipo de DTE (nombre)' => 'FACT',
        'Serie' => 'A',
        'Número del DTE' => '1',
        'NIT del emisor' => NIT_CONTRIBUYENTE_WIZARD,
        'Nombre completo del emisor' => 'Empresa Wizard, S.A.',
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

it('conserva las clasificaciones ya elegidas y señala solo al cliente faltante', function () {
    contribuyenteWizard();

    $filaUno = filaWizard([
        'Número de Autorización' => '22222222-2222-2222-2222-222222222222',
        'ID del receptor' => '5555555-5',
        'Nombre completo del receptor' => 'Cliente Uno, S.A.',
    ]);
    $filaDos = filaWizard([
        'Número de Autorización' => '33333333-3333-3333-3333-333333333333',
        'ID del receptor' => '6666666-6',
        'Nombre completo del receptor' => 'Cliente Dos, S.A.',
    ]);

    $this->post(route('importar.procesar'), [
        'archivo' => construirArchivoSat([$filaUno, $filaDos]),
    ])->assertOk();

    $this->post(route('importar.continuar'))->assertRedirect(route('importar.clasificar'));

    // Solo clasifica al primer cliente; deja el segundo sin elegir.
    $intentoFallido = $this->from(route('importar.clasificar'))->post(route('importar.guardarClasificacion'), [
        'clasificaciones' => ['5555555-5' => 'servicio'],
    ]);

    $intentoFallido->assertRedirect(route('importar.clasificar'));
    $intentoFallido->assertSessionHasErrors('clasificaciones');
    $intentoFallido->assertSessionHasInput('clasificaciones.5555555-5', 'servicio');

    $mensaje = session('errors')->first('clasificaciones');
    expect($mensaje)->toContain('Cliente Dos, S.A.')
        ->toContain('6666666-6')
        ->not->toContain('Cliente Uno');

    // La pantalla debe recordar "servicio" para el que ya se eligió, no reiniciar a la sugerencia.
    $pantallaReintentada = $this->get(route('importar.clasificar'));
    $pantallaReintentada->assertOk();
    $html = $pantallaReintentada->getContent();

    preg_match('/name="clasificaciones\[5555555-5\]" value="servicio"[^>]*checked/', $html, $coincidenciaServicio);
    preg_match('/name="clasificaciones\[5555555-5\]" value="bien"[^>]*checked/', $html, $coincidenciaBien);

    expect($coincidenciaServicio)->not->toBeEmpty()
        ->and($coincidenciaBien)->toBeEmpty();

    // Completar solo la fila faltante y reenviar debe terminar la importación.
    $intentoFinal = $this->post(route('importar.guardarClasificacion'), [
        'clasificaciones' => [
            '5555555-5' => 'servicio',
            '6666666-6' => 'bien',
        ],
    ]);

    $intentoFinal->assertOk();
    expect(Cliente::where('nit', '5555555-5')->first()?->tipo_default->value)->toBe('servicio')
        ->and(Cliente::where('nit', '6666666-6')->first()?->tipo_default->value)->toBe('bien')
        ->and(Documento::count())->toBe(2);
});

it('marca por defecto "Sí" en la pregunta de crédito fiscal de un proveedor nuevo', function () {
    contribuyenteWizard();

    // NIT del emisor distinto del contribuyente => dirección recibida => esProveedor.
    $filaRecibida = filaWizard([
        'Número de Autorización' => '44444444-4444-4444-4444-444444444444',
        'NIT del emisor' => '7777777-7',
        'Nombre completo del emisor' => 'Proveedor Uno, S.A.',
        'ID del receptor' => NIT_CONTRIBUYENTE_WIZARD,
        'Nombre completo del receptor' => 'Empresa Wizard, S.A.',
    ]);

    $this->post(route('importar.procesar'), [
        'archivo' => construirArchivoSat([$filaRecibida]),
    ])->assertOk();
    $this->post(route('importar.continuar'))->assertRedirect(route('importar.clasificar'));

    $pantalla = $this->get(route('importar.clasificar'));
    $pantalla->assertOk();
    $html = $pantalla->getContent();

    preg_match('/name="credito\[7777777-7\]" value="1"[^>]*checked/', $html, $coincidenciaSi);
    preg_match('/name="credito\[7777777-7\]" value="0"[^>]*checked/', $html, $coincidenciaNo);

    expect($coincidenciaSi)->not->toBeEmpty()
        ->and($coincidenciaNo)->toBeEmpty();
});

it('no resalta ninguna fila como faltante en la primera visita a la pantalla de clasificar', function () {
    contribuyenteWizard();

    $filaUno = filaWizard([
        'Número de Autorización' => '99999999-9999-9999-9999-999999999999',
        'ID del receptor' => '1111111-1',
        'Nombre completo del receptor' => 'Cliente Fresco Uno, S.A.',
    ]);
    $filaDos = filaWizard([
        'Número de Autorización' => '88888888-8888-8888-8888-888888888888',
        'ID del receptor' => '2222222-2',
        'Nombre completo del receptor' => 'Cliente Fresco Dos, S.A.',
    ]);

    $this->post(route('importar.procesar'), [
        'archivo' => construirArchivoSat([$filaUno, $filaDos]),
    ])->assertOk();
    $this->post(route('importar.continuar'))->assertRedirect(route('importar.clasificar'));

    // Primera visita real a /importar/clasificar: nadie ha enviado nada todavía.
    $pantalla = $this->get(route('importar.clasificar'));
    $pantalla->assertOk();

    expect($pantalla->getContent())->not->toContain('border-left:3px solid var(--alerta)');
});

it('bloquea la pregunta de crédito fiscal en la pantalla de clasificar cuando el proveedor solo trae FPEQ', function () {
    contribuyenteWizard();

    $filaFpeq = filaWizard([
        'Número de Autorización' => '77777777-7777-7777-7777-777777777777',
        'Tipo de DTE (nombre)' => 'FPEQ',
        'NIT del emisor' => '3333333-3',
        'Nombre completo del emisor' => 'Pequeño Contribuyente, S.A.',
        'ID del receptor' => NIT_CONTRIBUYENTE_WIZARD,
        'Nombre completo del receptor' => 'Empresa Wizard, S.A.',
    ]);

    $this->post(route('importar.procesar'), [
        'archivo' => construirArchivoSat([$filaFpeq]),
    ])->assertOk();
    $this->post(route('importar.continuar'))->assertRedirect(route('importar.clasificar'));

    $pantalla = $this->get(route('importar.clasificar'));
    $html = $pantalla->getContent();

    expect($html)->toContain('No genera crédito (FPEQ)')
        ->not->toContain('name="credito[3333333-3]"');
});

it('bloquea la pastilla de tipo en la pantalla de clasificar cuando el cliente solo trae facturas con IDP', function () {
    contribuyenteWizard();

    $filaCombustible = filaWizard([
        'Número de Autorización' => '44444444-1111-1111-1111-111111111111',
        'ID del receptor' => '4444444-4',
        'Nombre completo del receptor' => 'Gasolinera Demo, S.A.',
        'Petróleo (monto de este impuesto)' => 150.00,
    ]);

    $this->post(route('importar.procesar'), [
        'archivo' => construirArchivoSat([$filaCombustible]),
    ])->assertOk();
    $this->post(route('importar.continuar'))->assertRedirect(route('importar.clasificar'));

    $pantalla = $this->get(route('importar.clasificar'));
    $html = $pantalla->getContent();

    expect($html)->toContain('Combustible (por IDP)')
        ->not->toContain('id="tipo-0-bien"')
        ->not->toContain('id="tipo-0-servicio"')
        ->not->toContain('id="tipo-0-combustible"');
});

it('conserva la respuesta de crédito fiscal ya elegida cuando falta otro campo por completar', function () {
    contribuyenteWizard();

    $filaRecibida = filaWizard([
        'Número de Autorización' => '55555555-5555-5555-5555-555555555555',
        'NIT del emisor' => '8888888-8',
        'Nombre completo del emisor' => 'Proveedor Dos, S.A.',
        'ID del receptor' => NIT_CONTRIBUYENTE_WIZARD,
        'Nombre completo del receptor' => 'Empresa Wizard, S.A.',
    ]);

    $this->post(route('importar.procesar'), [
        'archivo' => construirArchivoSat([$filaRecibida]),
    ])->assertOk();
    $this->post(route('importar.continuar'))->assertRedirect(route('importar.clasificar'));

    // Responde "No" a crédito fiscal pero deja el tipo sin elegir, para forzar el error.
    $intentoFallido = $this->from(route('importar.clasificar'))->post(route('importar.guardarClasificacion'), [
        'credito' => ['8888888-8' => '0'],
    ]);
    $intentoFallido->assertRedirect(route('importar.clasificar'));
    $intentoFallido->assertSessionHasErrors('clasificaciones');
    $intentoFallido->assertSessionHasInput('credito.8888888-8', '0');

    $pantallaReintentada = $this->get(route('importar.clasificar'));
    $html = $pantallaReintentada->getContent();

    preg_match('/name="credito\[8888888-8\]" value="0"[^>]*checked/', $html, $coincidenciaNo);
    preg_match('/name="credito\[8888888-8\]" value="1"[^>]*checked/', $html, $coincidenciaSi);

    expect($coincidenciaNo)->not->toBeEmpty()
        ->and($coincidenciaSi)->toBeEmpty();
});
