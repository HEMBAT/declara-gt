<?php

namespace App\Servicios\Dto;

final readonly class ResultadoDesgloseSat2237
{
    /**
     * Todos los montos en formato decimal "0.00".
     */
    public function __construct(
        // Débito — cuadro 3 del SAT-2237.
        public string $ventasGravadasBien,
        public string $serviciosGravados,
        public string $debitoTotal,
        // Crédito — cuadro 5 del SAT-2237 (solo genera_credito=true). Cada
        // casilla del formulario pide dos columnas: la base sin IVA (ni IDP)
        // y el crédito, así que van en pares.
        public string $creditoCombustibles,
        public string $creditoOtrasCompras,
        public string $creditoServiciosAdquiridos,
        public string $creditoTotal,
        public string $baseCombustibles,
        public string $baseOtrasCompras,
        public string $baseServiciosAdquiridos,
        public string $baseCreditoTotal,
        // Casillas de solo base del cuadro 5: las compras a Pequeño
        // Contribuyente tienen la suya propia, separada del resto de las que
        // no generan derecho a crédito. Más el remanente del período anterior
        // (§10 del brief).
        public string $basePequenosContribuyentes,
        public string $baseNoDeducible,
        public string $remanenteAnterior,
        // Desglose de ingresos (base sin IVA, no monto de IVA): es a la vez la
        // columna Base del cuadro 3 y el desglose que pide el SAT-1311.
        public string $ingresosBienes,
        public string $ingresosServicios,
        public string $baseDebitoTotal,
    ) {}
}
