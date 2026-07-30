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
        // Crédito — cuadro 5 del SAT-2237 (solo genera_credito=true).
        public string $creditoCombustibles,
        public string $creditoOtrasCompras,
        public string $creditoServiciosAdquiridos,
        public string $creditoTotal,
        // Informativo: compras recibidas que no generan crédito, y el
        // remanente del período anterior (§10 del brief).
        public string $baseNoDeducible,
        public string $remanenteAnterior,
        // Desglose de ingresos (base sin IVA, no monto de IVA) para el SAT-1311.
        public string $ingresosBienes,
        public string $ingresosServicios,
    ) {}
}
