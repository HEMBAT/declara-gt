<?php

namespace App\Servicios;

use App\Servicios\Dto\LineaSat2237;
use App\Servicios\Dto\ResultadoDesgloseSat2237;

/**
 * Desglosa el débito y el crédito del IVA general en los subtotales por
 * casilla que pide el formulario SAT-2237 (§11 del brief) — Declaraguate no
 * acepta un total único, pide ventas/servicios y combustibles/otras compras/
 * servicios adquiridos por separado.
 *
 * Clase pura, mismo estándar que CalculadoraIsrOpcional y
 * CalculadoraIvaGeneral: sin Eloquent, aritmética en centavos enteros
 * (duplicada a propósito en vez de compartida, para no tocar esos dos
 * servicios ya cerrados).
 *
 * La suma de los subtotales de débito siempre debe igualar el débito total
 * que calcula CalculadoraIvaGeneral (misma regla para crédito) — son la
 * misma cifra vista con más o menos detalle, nunca deben divergir.
 */
final class DesgloseSat2237
{
    /**
     * @param  iterable<LineaSat2237>  $lineasEmitidas  documentos emitidos del período
     * @param  iterable<LineaSat2237>  $lineasRecibidas  documentos recibidos del período (con y sin crédito)
     * @param  string  $remanenteAnterior  remanente de crédito del período anterior, formato "0.00"
     */
    public function calcular(iterable $lineasEmitidas, iterable $lineasRecibidas, string $remanenteAnterior = '0.00'): ResultadoDesgloseSat2237
    {
        $ventasBienCentavos = 0;
        $serviciosCentavos = 0;
        $ingresosBienCentavos = 0;
        $ingresosServicioCentavos = 0;

        foreach ($lineasEmitidas as $linea) {
            $ivaCentavos = self::aCentavos($linea->iva) * $linea->signo;
            $baseCentavos = self::aCentavos($linea->baseSinIva) * $linea->signo;

            if ($linea->tipo === 'servicio') {
                $serviciosCentavos += $ivaCentavos;
                $ingresosServicioCentavos += $baseCentavos;
            } else {
                // 'bien', 'combustible' o null caen en la casilla de ventas de bienes:
                // el SAT-2237 solo distingue bienes/servicios en el débito, la
                // categoría de combustible es una particularidad del crédito.
                $ventasBienCentavos += $ivaCentavos;
                $ingresosBienCentavos += $baseCentavos;
            }
        }

        $combustiblesCentavos = 0;
        $otrasComprasCentavos = 0;
        $serviciosAdquiridosCentavos = 0;
        $baseNoDeducibleCentavos = 0;

        foreach ($lineasRecibidas as $linea) {
            $baseCentavos = self::aCentavos($linea->baseSinIva) * $linea->signo;

            if ($linea->generaCredito !== true) {
                $baseNoDeducibleCentavos += $baseCentavos;

                continue;
            }

            $ivaCentavos = self::aCentavos($linea->iva) * $linea->signo;

            match ($linea->tipo) {
                'combustible' => $combustiblesCentavos += $ivaCentavos,
                'servicio' => $serviciosAdquiridosCentavos += $ivaCentavos,
                default => $otrasComprasCentavos += $ivaCentavos, // 'bien' o null
            };
        }

        $debitoTotalCentavos = $ventasBienCentavos + $serviciosCentavos;
        $creditoTotalCentavos = $combustiblesCentavos + $otrasComprasCentavos + $serviciosAdquiridosCentavos;

        return new ResultadoDesgloseSat2237(
            ventasGravadasBien: self::aDecimal($ventasBienCentavos),
            serviciosGravados: self::aDecimal($serviciosCentavos),
            debitoTotal: self::aDecimal($debitoTotalCentavos),
            creditoCombustibles: self::aDecimal($combustiblesCentavos),
            creditoOtrasCompras: self::aDecimal($otrasComprasCentavos),
            creditoServiciosAdquiridos: self::aDecimal($serviciosAdquiridosCentavos),
            creditoTotal: self::aDecimal($creditoTotalCentavos),
            baseNoDeducible: self::aDecimal($baseNoDeducibleCentavos),
            remanenteAnterior: $remanenteAnterior,
            ingresosBienes: self::aDecimal($ingresosBienCentavos),
            ingresosServicios: self::aDecimal($ingresosServicioCentavos),
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
