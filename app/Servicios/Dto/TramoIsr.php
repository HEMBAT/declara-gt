<?php

namespace App\Servicios\Dto;

final readonly class TramoIsr
{
    /**
     * @param  string  $tasa  fracción decimal "0.0500", no porcentaje
     * @param  string|null  $limiteInferior  formato decimal "0.00"
     * @param  string|null  $limiteSuperior  formato decimal "0.00"
     */
    public function __construct(
        public string $tasa,
        public ?string $limiteInferior,
        public ?string $limiteSuperior,
    ) {}
}
