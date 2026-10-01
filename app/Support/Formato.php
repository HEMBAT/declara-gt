<?php

namespace App\Support;

use Carbon\Carbon;
use Carbon\CarbonInterface;

/**
 * Formato de montos en quetzales, fechas y períodos en español, compartido
 * por todas las vistas para que el dashboard, la tabla de documentos y los
 * demás listados muestren los números de forma idéntica.
 */
final class Formato
{
    private const MESES = [
        1 => 'enero', 2 => 'febrero', 3 => 'marzo', 4 => 'abril', 5 => 'mayo', 6 => 'junio',
        7 => 'julio', 8 => 'agosto', 9 => 'septiembre', 10 => 'octubre', 11 => 'noviembre', 12 => 'diciembre',
    ];

    private const MESES_ABREVIADOS = [
        1 => 'ene', 2 => 'feb', 3 => 'mar', 4 => 'abr', 5 => 'may', 6 => 'jun',
        7 => 'jul', 8 => 'ago', 9 => 'sep', 10 => 'oct', 11 => 'nov', 12 => 'dic',
    ];

    public static function moneda(string|int|float $valor, bool $conSigno = false): string
    {
        $numero = (float) $valor;
        $negativo = $numero < 0;
        $texto = number_format(abs($numero), 2, '.', ',');
        $signo = $negativo ? '-' : ($conSigno ? '+' : '');

        return $signo.'Q'.$texto;
    }

    /**
     * Limpia un monto tal como se copia de Declaraguate ("1,858", "Q1,858.00")
     * para validarlo como número. No valida: solo quita separadores y "Q".
     */
    public static function limpiarMontoIngresado(mixed $valor): string
    {
        return str_replace([',', ' ', 'Q'], '', (string) $valor);
    }

    public static function periodoLabel(string $periodo): string
    {
        [$anio, $mes] = explode('-', $periodo);
        $nombreMes = self::MESES[(int) $mes] ?? $mes;

        return ucfirst($nombreMes).' '.$anio;
    }

    public static function fecha(CarbonInterface|string $fecha): string
    {
        $fecha = $fecha instanceof CarbonInterface ? $fecha : Carbon::parse($fecha);

        return sprintf('%02d %s %d', $fecha->day, self::MESES_ABREVIADOS[$fecha->month], $fecha->year);
    }
}
