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
 * Aparte del total, informa cuántos de esos documentos están anulados: no
 * entran a ningún cálculo, pero el usuario necesita verlos para cuadrar el
 * conteo contra lo que muestra la Agencia Virtual.
 *
 * No calcula montos — solo conteos.
 */
final class ConteoDocumentosPeriodoService
{
    /**
     * @return array{emitida: array{total: int, anulados: int, porTipo: array<string, int>}, recibida: array{total: int, anulados: int, porTipo: array<string, int>}}
     */
    public function contarPeriodo(string $periodo): array
    {
        $conteo = [
            Direccion::Emitida->value => ['total' => 0, 'anulados' => 0, 'porTipo' => []],
            Direccion::Recibida->value => ['total' => 0, 'anulados' => 0, 'porTipo' => []],
        ];

        $filas = Documento::query()
            ->join('tipos_dte', 'tipos_dte.id', '=', 'documentos.tipo_dte_id')
            ->where('documentos.periodo', $periodo)
            ->groupBy('documentos.direccion', 'tipos_dte.codigo')
            ->orderBy('tipos_dte.codigo')
            ->get([
                'documentos.direccion',
                'tipos_dte.codigo',
                DB::raw('count(*) as cantidad'),
                DB::raw('sum(case when documentos.anulado = 1 then 1 else 0 end) as cantidad_anulados'),
            ]);

        foreach ($filas as $fila) {
            $direccion = $fila->direccion instanceof Direccion ? $fila->direccion->value : (string) $fila->direccion;

            if (! isset($conteo[$direccion])) {
                continue;
            }

            $conteo[$direccion]['porTipo'][$fila->codigo] = (int) $fila->cantidad;
            $conteo[$direccion]['total'] += (int) $fila->cantidad;
            $conteo[$direccion]['anulados'] += (int) $fila->cantidad_anulados;
        }

        return $conteo;
    }
}
