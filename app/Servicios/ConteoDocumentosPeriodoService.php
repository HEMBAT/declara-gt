<?php

namespace App\Servicios;

use App\Enums\Direccion;
use App\Models\Documento;
use Illuminate\Support\Facades\DB;

/**
 * Cuenta los documentos de un período por dirección y tipo de DTE.
 *
 * A diferencia del resto de los servicios de esta carpeta, aquí NO se filtra
 * por estado, anulado, moneda ni tipo de DTE: el formulario pregunta cuántos
 * documentos hubo en el período, sin importar si se anularon o si son de
 * Pequeño Contribuyente. Por eso no reusa los scopes de Documento, que
 * existen justamente para aplicar esos filtros.
 *
 * No calcula montos — solo conteos.
 */
final class ConteoDocumentosPeriodoService
{
    /**
     * @return array{emitida: array{total: int, porTipo: array<string, int>}, recibida: array{total: int, porTipo: array<string, int>}}
     */
    public function contarPeriodo(string $periodo): array
    {
        $conteo = [
            Direccion::Emitida->value => ['total' => 0, 'porTipo' => []],
            Direccion::Recibida->value => ['total' => 0, 'porTipo' => []],
        ];

        $filas = Documento::query()
            ->join('tipos_dte', 'tipos_dte.id', '=', 'documentos.tipo_dte_id')
            ->where('documentos.periodo', $periodo)
            ->groupBy('documentos.direccion', 'tipos_dte.codigo')
            ->orderBy('tipos_dte.codigo')
            ->get(['documentos.direccion', 'tipos_dte.codigo', DB::raw('count(*) as cantidad')]);

        foreach ($filas as $fila) {
            $direccion = $fila->direccion instanceof Direccion ? $fila->direccion->value : (string) $fila->direccion;

            if (! isset($conteo[$direccion])) {
                continue;
            }

            $conteo[$direccion]['porTipo'][$fila->codigo] = (int) $fila->cantidad;
            $conteo[$direccion]['total'] += (int) $fila->cantidad;
        }

        return $conteo;
    }
}
