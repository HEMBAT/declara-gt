<?php

namespace App\Servicios;

use App\Servicios\Dto\LineaDocumento;
use App\Servicios\Dto\ResultadoIsr;
use App\Servicios\Dto\TramoIsr;
use InvalidArgumentException;

/**
 * Calcula el ISR Régimen Opcional Simplificado Mensual de Guatemala.
 *
 * Clase pura: no toca Eloquent ni la base de datos. Recibe únicamente DTOs
 * simples y devuelve un DTO con el desglose completo, listo para mostrarse
 * tal como lo pide el formulario SAT-1311.
 *
 * Toda la aritmética monetaria ocurre en centavos enteros (sin bcmath, sin
 * error de punto flotante) para que los casos límite del §7 del brief
 * (Q30,000.00 exacto y Q30,000.01) sean exactos.
 */
final class CalculadoraIsrOpcional
{
    /**
     * @param  iterable<LineaDocumento>  $lineas  documentos emitidos del período
     * @param  array{0: TramoIsr, 1: TramoIsr}  $tramosIsr  tramo 1 y tramo 2, en ese orden
     * @param  string  $totalRetenciones  suma de retenciones del período, formato "0.00"
     */
    public function calcular(iterable $lineas, array $tramosIsr, string $totalRetenciones = '0.00'): ResultadoIsr
    {
        if (count($tramosIsr) !== 2) {
            throw new InvalidArgumentException('Se requieren exactamente 2 tramos de ISR (tramo 1 y tramo 2).');
        }

        [$tramo1, $tramo2] = $tramosIsr;

        $baseGravableCentavos = 0;
        foreach ($lineas as $linea) {
            $baseGravableCentavos += self::aCentavos($linea->baseSinIva) * $linea->signo;
        }
        $baseGravableCentavos = max(0, $baseGravableCentavos);

        $limite1Centavos = self::aCentavos($tramo1->limiteSuperior ?? '0.00');
        $tasa1Escalada = self::aEscalado($tramo1->tasa, 4);
        $tasa2Escalada = self::aEscalado($tramo2->tasa, 4);

        $tramo1BaseCentavos = min($baseGravableCentavos, $limite1Centavos);
        $tramo2BaseCentavos = max(0, $baseGravableCentavos - $limite1Centavos);

        $tramo1MontoCentavos = self::centavosPorTasaEscalada($tramo1BaseCentavos, $tasa1Escalada, 4);
        $tramo2MontoCentavos = self::centavosPorTasaEscalada($tramo2BaseCentavos, $tasa2Escalada, 4);

        $isrDeterminadoCentavos = $tramo1MontoCentavos + $tramo2MontoCentavos;
        $retencionesCentavos = self::aCentavos($totalRetenciones);
        $isrAPagarCentavos = max(0, $isrDeterminadoCentavos - $retencionesCentavos);

        return new ResultadoIsr(
            baseGravable: self::aDecimal($baseGravableCentavos),
            tramo1Base: self::aDecimal($tramo1BaseCentavos),
            tramo1Monto: self::aDecimal($tramo1MontoCentavos),
            tramo1Tasa: $tramo1->tasa,
            tramo2Base: self::aDecimal($tramo2BaseCentavos),
            tramo2Monto: self::aDecimal($tramo2MontoCentavos),
            tramo2Tasa: $tramo2->tasa,
            isrDeterminado: self::aDecimal($isrDeterminadoCentavos),
            totalRetenciones: self::aDecimal($retencionesCentavos),
            isrAPagar: self::aDecimal($isrAPagarCentavos),
        );
    }

    /**
     * Convierte un string decimal "0.00" (o "-0.00") a centavos enteros, sin
     * pasar por punto flotante.
     */
    private static function aCentavos(string $valor): int
    {
        return self::aEscalado($valor, 2);
    }

    /**
     * Convierte un string decimal a un entero escalado por 10^$decimales,
     * p. ej. aEscalado('0.0500', 4) === 500.
     */
    private static function aEscalado(string $valor, int $decimales): int
    {
        $negativo = str_starts_with($valor, '-');
        $valor = ltrim($valor, '-+');

        [$entero, $decimal] = array_pad(explode('.', $valor, 2), 2, '0');
        $decimal = str_pad(substr($decimal, 0, $decimales), $decimales, '0');

        $escalado = ((int) $entero) * (10 ** $decimales) + (int) $decimal;

        return $negativo ? -$escalado : $escalado;
    }

    /**
     * Multiplica un monto en centavos por una tasa escalada (p. ej. tasa al
     * 10^4) y redondea al centavo más cercano (mitad hacia arriba), todo con
     * aritmética entera.
     */
    private static function centavosPorTasaEscalada(int $centavos, int $tasaEscalada, int $decimalesTasa): int
    {
        $divisor = 10 ** $decimalesTasa;
        $producto = $centavos * $tasaEscalada;

        $cociente = intdiv($producto, $divisor);
        $resto = $producto % $divisor;

        if ($resto * 2 >= $divisor) {
            $cociente++;
        }

        return $cociente;
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
