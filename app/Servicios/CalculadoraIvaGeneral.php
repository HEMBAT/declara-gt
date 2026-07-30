<?php

namespace App\Servicios;

use App\Servicios\Dto\LineaIva;
use App\Servicios\Dto\ResultadoIva;

/**
 * Calcula el IVA Régimen General mensual de Guatemala (formulario SAT-2237).
 *
 * Clase pura: no toca Eloquent ni la base de datos. Recibe únicamente DTOs
 * simples y devuelve un DTO con el desglose completo.
 *
 * Toda la aritmética monetaria ocurre en centavos enteros (sin bcmath, sin
 * error de punto flotante), igual que CalculadoraIsrOpcional — duplicado
 * aquí a propósito en vez de compartido, para no tocar el servicio de ISR
 * ya cerrado.
 */
final class CalculadoraIvaGeneral
{
    /**
     * @param  iterable<LineaIva>  $lineasDebito  documentos emitidos del período
     * @param  iterable<LineaIva>  $lineasCredito  documentos recibidos del período con genera_credito=true
     * @param  string  $remanenteAnterior  remanente de crédito del período anterior, formato "0.00"
     */
    public function calcular(iterable $lineasDebito, iterable $lineasCredito, string $remanenteAnterior = '0.00'): ResultadoIva
    {
        $debitoCentavos = 0;
        foreach ($lineasDebito as $linea) {
            $debitoCentavos += self::aCentavos($linea->iva) * $linea->signo;
        }

        $creditoCentavos = 0;
        foreach ($lineasCredito as $linea) {
            $creditoCentavos += self::aCentavos($linea->iva) * $linea->signo;
        }

        $remanenteCentavos = self::aCentavos($remanenteAnterior);
        $resultadoCentavos = $debitoCentavos - $creditoCentavos - $remanenteCentavos;

        $ivaAPagarCentavos = max(0, $resultadoCentavos);
        $remanenteNuevoCentavos = max(0, -$resultadoCentavos);

        return new ResultadoIva(
            debito: self::aDecimal($debitoCentavos),
            credito: self::aDecimal($creditoCentavos),
            remanenteAnterior: self::aDecimal($remanenteCentavos),
            resultado: self::aDecimal($resultadoCentavos),
            ivaAPagar: self::aDecimal($ivaAPagarCentavos),
            remanenteCredito: self::aDecimal($remanenteNuevoCentavos),
        );
    }

    /**
     * Convierte un string decimal "0.00" (o "-0.00") a centavos enteros, sin
     * pasar por punto flotante.
     */
    private static function aCentavos(string $valor): int
    {
        $negativo = str_starts_with($valor, '-');
        $valor = ltrim($valor, '-+');

        [$entero, $decimal] = array_pad(explode('.', $valor, 2), 2, '0');
        $decimal = str_pad(substr($decimal, 0, 2), 2, '0');

        $centavos = ((int) $entero) * 100 + (int) $decimal;

        return $negativo ? -$centavos : $centavos;
    }

    /**
     * Convierte centavos enteros de vuelta a un string decimal "0.00".
     */
    private static function aDecimal(int $centavos): string
    {
        $negativo = $centavos < 0;
        $centavos = abs($centavos);

        $entero = intdiv($centavos, 100);
        $resto = $centavos % 100;

        return ($negativo ? '-' : '').sprintf('%d.%02d', $entero, $resto);
    }
}
