<?php

namespace App\Servicios\Dto;

final readonly class ResultadoIva
{
    /**
     * Todos los montos en formato decimal "0.00". `resultado` es la única
     * cifra que puede ser negativa (débito − crédito − remanente anterior);
     * `ivaAPagar` y `remanenteCredito` ya vienen recortados a cero.
     */
    public function __construct(
        public string $debito,
        public string $credito,
        public string $remanenteAnterior,
        public string $resultado,
        public string $ivaAPagar,
        public string $remanenteCredito,
    ) {}

    /**
     * @return array<string, string>
     */
    public function toArray(): array
    {
        return get_object_vars($this);
    }
}
