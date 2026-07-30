<?php

namespace App\Servicios\Dto;

final readonly class LineaSat2237
{
    /**
     * @param  string  $iva  monto en formato decimal "0.00"
     * @param  string  $baseSinIva  monto en formato decimal "0.00"
     * @param  int  $signo  +1 o -1, según el tipo_dte del documento
     * @param  ?string  $tipo  'bien'|'servicio'|'combustible'|null
     * @param  ?bool  $generaCredito  null para emitidas (no aplica); true/false para recibidas
     */
    public function __construct(
        public string $iva,
        public string $baseSinIva,
        public int $signo,
        public ?string $tipo,
        public ?bool $generaCredito,
    ) {}
}
