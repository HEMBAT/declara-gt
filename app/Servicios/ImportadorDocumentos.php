<?php

namespace App\Servicios;

use App\Exceptions\ArchivoImportacionInvalidoException;
use App\Exceptions\ContribuyenteNoConfiguradoException;
use App\Models\Cliente;
use App\Models\Contribuyente;
use App\Models\Documento;
use App\Models\TipoDte;
use App\Servicios\Dto\ClientePorClasificar;
use App\Servicios\Dto\DraftImportacion;
use App\Servicios\Dto\FilaDocumentoImportado;
use App\Servicios\Dto\ResultadoImportacion;
use Carbon\Carbon;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date as FechaExcel;
use RuntimeException;
use Throwable;

/**
 * Lee el .xls/.xlsx que exporta la Agencia Virtual del SAT (hoja
 * InformacionDTE-FEL) y lo convierte en documentos. Detecta la dirección de
 * cada fila comparando el NIT del emisor contra el contribuyente configurado,
 * y hace upsert idempotente por numero_autorizacion.
 *
 * El proceso está partido en dos pasos porque los NITs de contraparte nuevos
 * requieren que el usuario elija Bien/Servicio/Combustible antes de guardar
 * (§4 del brief): `previsualizar()` solo lee y valida, sin tocar la base de
 * datos; `confirmar()` hace los upserts. `importar()` encadena ambos para el
 * caso en que no se necesita ese paso intermedio.
 */
final class ImportadorDocumentos
{
    private const HOJA = 'InformacionDTE-FEL';

    private const TAMANO_MAXIMO_BYTES = 5 * 1024 * 1024;

    private const EXTENSIONES_PERMITIDAS = ['xls', 'xlsx'];

    private const MIMES_CLARAMENTE_INVALIDOS = [
        'text/plain', 'text/html', 'text/csv', 'application/pdf', 'application/json', 'application/xml',
    ];

    private const COLUMNAS_REQUERIDAS = [
        'fecha de emisión',
        'número de autorización',
        'tipo de dte (nombre)',
        'nit del emisor',
        'nombre completo del emisor',
        'id del receptor',
        'nombre completo del receptor',
        'estado',
        'marca de anulado',
        'moneda',
        'gran total (moneda original)',
        'iva (monto de este impuesto)',
    ];

    public function importar(UploadedFile $archivo, array $clasificaciones = [], array $clasificacionesCredito = []): ResultadoImportacion
    {
        return $this->confirmar($this->previsualizar($archivo), $clasificaciones, $clasificacionesCredito);
    }

    public function previsualizar(UploadedFile $archivo): DraftImportacion
    {
        $this->validarArchivo($archivo);

        $contribuyente = Contribuyente::actual();
        if ($contribuyente === null) {
            throw new ContribuyenteNoConfiguradoException(
                'Configura el NIT de tu empresa antes de importar facturas.'
            );
        }

        $filasHoja = $this->leerFilasHoja($archivo);
        $mapaColumnas = $this->mapearColumnas($filasHoja[1] ?? []);

        $filas = [];
        $advertencias = [];
        $filasConError = 0;
        $sugerenciaPorNit = [];
        $nombrePorNit = [];
        $proveedorPorNit = [];
        $soloFpeqPorNit = [];
        $soloCombustiblePorNit = [];

        foreach ($filasHoja as $numeroFila => $fila) {
            if ($numeroFila === 1 || $this->filaVacia($fila)) {
                continue;
            }

            try {
                $filaImportada = $this->parsearFila($fila, $mapaColumnas, $contribuyente->nit);
            } catch (Throwable) {
                $filasConError++;
                $advertencias[] = "Fila {$numeroFila}: datos ilegibles, se omitió.";

                continue;
            }

            if ($filaImportada->moneda !== 'GTQ') {
                $advertencias[] = "Fila {$numeroFila}: moneda {$filaImportada->moneda} distinta de GTQ, se excluye del cálculo.";
            }

            if ($filaImportada->tipoSugerido === 'combustible') {
                $sugerenciaPorNit[$filaImportada->nitContraparte] = 'combustible';
            }
            $nombrePorNit[$filaImportada->nitContraparte] ??= $filaImportada->nombreContraparte;
            // true mientras todas las filas vistas de este NIT (cualquier dirección) tengan IDP.
            $soloCombustiblePorNit[$filaImportada->nitContraparte] =
                ($soloCombustiblePorNit[$filaImportada->nitContraparte] ?? true)
                && ((float) $filaImportada->idp) > 0;
            if ($filaImportada->direccion === 'recibida') {
                $proveedorPorNit[$filaImportada->nitContraparte] = true;
                // true mientras todas las filas recibidas vistas de este NIT sean FPEQ.
                $soloFpeqPorNit[$filaImportada->nitContraparte] =
                    ($soloFpeqPorNit[$filaImportada->nitContraparte] ?? true)
                    && $filaImportada->tipoDteCodigo === 'FPEQ';
            }

            $filas[] = $filaImportada;
        }

        $nitsVistos = array_values(array_unique(array_map(fn (FilaDocumentoImportado $f) => $f->nitContraparte, $filas)));
        $nitsExistentes = $nitsVistos === []
            ? []
            : Cliente::query()->whereIn('nit', $nitsVistos)->pluck('nit')->all();

        $clientesPorClasificar = [];
        foreach ($nitsVistos as $nit) {
            if (in_array($nit, $nitsExistentes, true)) {
                continue;
            }
            $esProveedor = $proveedorPorNit[$nit] ?? false;
            $creditoBloqueado = $esProveedor && ($soloFpeqPorNit[$nit] ?? false);
            $tipoBloqueado = $soloCombustiblePorNit[$nit] ?? false;

            $clientesPorClasificar[] = new ClientePorClasificar(
                nit: $nit,
                nombre: $nombrePorNit[$nit] ?? $nit,
                tipoSugerido: $sugerenciaPorNit[$nit] ?? null,
                esProveedor: $esProveedor,
                generaCreditoSugerido: ! $creditoBloqueado,
                creditoBloqueado: $creditoBloqueado,
                tipoBloqueado: $tipoBloqueado,
            );
        }

        return new DraftImportacion($filas, $clientesPorClasificar, $advertencias, $filasConError);
    }

    /**
     * @param  array<string, string>  $clasificaciones  nit => bien|servicio|combustible
     * @param  array<string, bool>  $clasificacionesCredito  nit => ¿genera crédito fiscal? (solo proveedores)
     */
    public function confirmar(DraftImportacion $draft, array $clasificaciones = [], array $clasificacionesCredito = []): ResultadoImportacion
    {
        return DB::transaction(function () use ($draft, $clasificaciones, $clasificacionesCredito) {
            foreach ($draft->clientesPorClasificar as $candidato) {
                Cliente::query()->firstOrCreate(
                    ['nit' => $candidato->nit],
                    [
                        'nombre' => $candidato->nombre,
                        // tipoBloqueado (todas sus filas de este archivo tienen IDP) manda siempre
                        // sobre lo que venga en la petición: el IDP solo aplica a combustibles.
                        'tipo_default' => $candidato->tipoBloqueado
                            ? 'combustible'
                            : ($clasificaciones[$candidato->nit] ?? $candidato->tipoSugerido ?? 'bien'),
                        // creditoBloqueado (todas sus filas de este archivo son FPEQ) manda siempre
                        // sobre lo que venga en la petición: no es una elección del usuario.
                        'genera_credito_default' => $candidato->creditoBloqueado
                            ? false
                            : ($clasificacionesCredito[$candidato->nit] ?? $candidato->generaCreditoSugerido),
                    ]
                );
            }

            $nuevos = 0;
            $actualizados = 0;
            $ignorados = $draft->filasConError;

            foreach ($draft->filas as $fila) {
                $tipoDte = TipoDte::query()->firstOrCreate(
                    ['codigo' => $fila->tipoDteCodigo],
                    ['nombre' => $fila->tipoDteCodigo, 'signo' => 1, 'revisar' => true]
                );

                $cliente = Cliente::query()->firstOrCreate(
                    ['nit' => $fila->nitContraparte],
                    ['nombre' => $fila->nombreContraparte, 'tipo_default' => $fila->tipoSugerido ?? 'bien', 'genera_credito_default' => true]
                );

                $documento = Documento::query()->updateOrCreate(
                    ['numero_autorizacion' => $fila->numeroAutorizacion],
                    [
                        'fecha_emision' => $fila->fechaEmision,
                        'periodo' => $fila->periodo,
                        'tipo_dte_id' => $tipoDte->id,
                        'serie' => $fila->serie,
                        'numero' => $fila->numero,
                        'cliente_id' => $cliente->id,
                        'direccion' => $fila->direccion,
                        'gran_total' => $fila->granTotal,
                        'iva' => $fila->iva,
                        'idp' => $fila->idp,
                        'base_sin_iva' => $fila->baseSinIva,
                        // Un documento con IDP siempre es combustible, sin importar el default
                        // del cliente (que puede tener facturas mixtas de otros períodos/tipos).
                        'tipo' => ((float) $fila->idp) > 0 ? 'combustible' : $cliente->tipo_default,
                        // Las FPEQ nunca generan crédito fiscal (régimen de Pequeño Contribuyente),
                        // sin importar el default del proveedor ni lo que haya elegido el usuario.
                        'genera_credito' => $fila->direccion === 'recibida'
                            ? ($fila->tipoDteCodigo === 'FPEQ' ? false : $cliente->genera_credito_default)
                            : null,
                        'moneda' => $fila->moneda,
                        'estado' => $fila->estado,
                        'anulado' => $fila->anulado,
                    ]
                );

                $documento->wasRecentlyCreated ? $nuevos++ : $actualizados++;

                if ($fila->moneda !== 'GTQ' || $fila->anulado) {
                    $ignorados++;
                }
            }

            return new ResultadoImportacion($nuevos, $actualizados, $ignorados, $draft->advertencias);
        });
    }

    private function validarArchivo(UploadedFile $archivo): void
    {
        $extension = strtolower((string) $archivo->getClientOriginalExtension());
        if (! in_array($extension, self::EXTENSIONES_PERMITIDAS, true)) {
            throw new ArchivoImportacionInvalidoException('El archivo debe tener extensión .xls o .xlsx.');
        }

        if ($archivo->getSize() > self::TAMANO_MAXIMO_BYTES) {
            throw new ArchivoImportacionInvalidoException('El archivo supera el tamaño máximo permitido de 5 MB.');
        }

        $mime = $archivo->getMimeType();
        if ($mime !== null && $this->esMimeClaramenteInvalido($mime)) {
            throw new ArchivoImportacionInvalidoException('El archivo no parece ser una hoja de cálculo válida.');
        }
    }

    private function esMimeClaramenteInvalido(string $mime): bool
    {
        if (in_array($mime, self::MIMES_CLARAMENTE_INVALIDOS, true)) {
            return true;
        }

        return str_starts_with($mime, 'image/') || str_starts_with($mime, 'video/') || str_starts_with($mime, 'audio/');
    }

    /**
     * @return array<int, array<string, mixed>> filas indexadas por número de fila real (1 = encabezado)
     */
    private function leerFilasHoja(UploadedFile $archivo): array
    {
        try {
            $spreadsheet = IOFactory::load($archivo->getRealPath());
        } catch (Throwable $e) {
            throw new ArchivoImportacionInvalidoException('No se pudo leer el archivo: '.$e->getMessage());
        }

        $hoja = $spreadsheet->getSheetByName(self::HOJA);
        if ($hoja === null) {
            throw new ArchivoImportacionInvalidoException('El archivo no contiene la hoja "'.self::HOJA.'".');
        }

        return $hoja->toArray(null, true, true, true);
    }

    /**
     * @param  array<string, mixed>  $filaEncabezado
     * @return array<string, string> encabezado normalizado => columna (letra)
     */
    private function mapearColumnas(array $filaEncabezado): array
    {
        $mapa = [];
        foreach ($filaEncabezado as $columna => $encabezado) {
            if ($encabezado === null || trim((string) $encabezado) === '') {
                continue;
            }
            $mapa[$this->normalizarEncabezado((string) $encabezado)] = $columna;
        }

        $faltantes = array_diff(self::COLUMNAS_REQUERIDAS, array_keys($mapa));
        if ($faltantes !== []) {
            throw new ArchivoImportacionInvalidoException(
                'Faltan columnas requeridas en el archivo: '.implode(', ', $faltantes)
            );
        }

        return $mapa;
    }

    private function normalizarEncabezado(string $valor): string
    {
        return mb_strtolower(trim((string) preg_replace('/\s+/', ' ', $valor)));
    }

    /**
     * @param  array<string, mixed>  $fila
     * @param  array<string, string>  $mapaColumnas
     */
    private function parsearFila(array $fila, array $mapaColumnas, string $nitContribuyente): FilaDocumentoImportado
    {
        $valor = fn (string $clave): mixed => isset($mapaColumnas[$clave]) ? ($fila[$mapaColumnas[$clave]] ?? null) : null;

        $numeroAutorizacion = trim((string) $valor('número de autorización'));
        $fechaEmisionValor = $valor('fecha de emisión');
        $tipoDteCodigo = trim((string) $valor('tipo de dte (nombre)'));
        $nitEmisor = trim((string) $valor('nit del emisor'));
        $nombreEmisor = trim((string) $valor('nombre completo del emisor'));
        $idReceptor = trim((string) $valor('id del receptor'));
        $nombreReceptor = trim((string) $valor('nombre completo del receptor'));
        $estado = trim((string) $valor('estado'));
        $marcaAnulado = mb_strtolower(trim((string) $valor('marca de anulado')));
        $moneda = strtoupper(trim((string) $valor('moneda'))) ?: 'GTQ';

        if ($numeroAutorizacion === '' || $tipoDteCodigo === '' || $nitEmisor === '' || $idReceptor === '' || $fechaEmisionValor === null || $fechaEmisionValor === '') {
            throw new RuntimeException('Faltan datos obligatorios en la fila.');
        }

        $fechaEmision = $this->parsearFecha($fechaEmisionValor);
        $granTotal = $this->aDecimal($valor('gran total (moneda original)'));
        $iva = $this->aDecimal($valor('iva (monto de este impuesto)'));
        $idp = $this->aDecimal($valor('petróleo (monto de este impuesto)'));
        // El IDP no lleva IVA ni genera crédito (§10 del brief): se resta también de la base.
        $baseSinIva = number_format(round(((float) $granTotal) - ((float) $iva) - ((float) $idp), 2), 2, '.', '');

        $esEmitida = $nitEmisor === $nitContribuyente;

        return new FilaDocumentoImportado(
            numeroAutorizacion: $numeroAutorizacion,
            fechaEmision: $fechaEmision->format('Y-m-d'),
            periodo: $fechaEmision->format('Y-m'),
            tipoDteCodigo: $tipoDteCodigo,
            serie: ($s = trim((string) $valor('serie'))) !== '' ? $s : null,
            numero: ($n = trim((string) $valor('número del dte'))) !== '' ? $n : null,
            nitContraparte: $esEmitida ? $idReceptor : $nitEmisor,
            nombreContraparte: $esEmitida ? $nombreReceptor : $nombreEmisor,
            direccion: $esEmitida ? 'emitida' : 'recibida',
            granTotal: $granTotal,
            iva: $iva,
            idp: $idp,
            baseSinIva: $baseSinIva,
            tipoSugerido: ((float) $idp) > 0 ? 'combustible' : null,
            moneda: $moneda,
            estado: $estado,
            anulado: in_array($marcaAnulado, ['sí', 'si'], true),
        );
    }

    private function filaVacia(array $fila): bool
    {
        foreach ($fila as $valorCelda) {
            if ($valorCelda !== null && trim((string) $valorCelda) !== '') {
                return false;
            }
        }

        return true;
    }

    private function parsearFecha(mixed $valor): Carbon
    {
        if (is_numeric($valor)) {
            return Carbon::instance(FechaExcel::excelToDateTimeObject((float) $valor));
        }

        return Carbon::parse((string) $valor);
    }

    private function aDecimal(mixed $valor): string
    {
        if ($valor === null || $valor === '') {
            return '0.00';
        }

        if (is_string($valor)) {
            $valor = str_replace(',', '', trim($valor));
        }

        return number_format((float) $valor, 2, '.', '');
    }
}
