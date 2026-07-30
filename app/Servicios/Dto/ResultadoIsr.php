<?php

namespace App\Servicios\Dto;

final readonly class ResultadoIsr
{
    /**
     * Todos los montos en formato decimal "0.00".
     */
    public function __construct(
        public string $baseGravable,
        public string $tramo1Base,
        public string $tramo1Monto,
        public string $tramo1Tasa,
        public string $tramo2Base,
        public string $tramo2Monto,
        public string $tramo2Tasa,
        public string $isrDeterminado,
        public string $totalRetenciones,
        public string $isrAPagar,
    ) {}
}
