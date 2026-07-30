<?php

namespace App\Servicios\Dto;

final readonly class ClientePorClasificar
{
    public function __construct(
        public string $nit,
        public string $nombre,
        public ?string $tipoSugerido,
        /** true si el NIT aparece en al menos una fila recibida de este archivo. */
        public bool $esProveedor = false,
        /** Sugerencia para "¿genera crédito fiscal?", solo relevante si esProveedor. */
        public bool $generaCreditoSugerido = true,
        /**
         * true si TODAS las filas recibidas de este NIT en el archivo son FPEQ
         * (Pequeño Contribuyente) — en ese caso la pregunta no se ofrece como
         * elección, queda fija en "No" (§10 del brief: FPEQ nunca genera
         * crédito fiscal).
         */
        public bool $creditoBloqueado = false,
        /**
         * true si TODAS las filas de este NIT en el archivo (sin importar
         * dirección) tienen IDP > 0 — el tipo no se ofrece como elección,
         * queda fijo en "combustible": el IDP solo aplica a combustibles.
         */
        public bool $tipoBloqueado = false,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return get_object_vars($this);
    }

    /**
     * @param  array<string, mixed>  $datos
     */
    public static function fromArray(array $datos): self
    {
        return new self(...$datos);
    }
}
