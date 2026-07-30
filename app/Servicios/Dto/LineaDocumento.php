<?php

namespace App\Servicios\Dto;

final readonly class LineaDocumento
{
    /**
     * @param  string  $baseSinIva  monto en formato decimal "0.00"
     * @param  int  $signo  +1 o -1, según el tipo_dte del documento
     */
    public function __construct(
        public string $baseSinIva,
        public int $signo,
    ) {}
}
