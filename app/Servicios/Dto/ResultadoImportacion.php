<?php

namespace App\Servicios\Dto;

final readonly class ResultadoImportacion
{
    /**
     * @param  array<int, string>  $advertencias
     */
    public function __construct(
        public int $nuevos,
        public int $actualizados,
        public int $ignorados,
        public array $advertencias,
    ) {}
}
