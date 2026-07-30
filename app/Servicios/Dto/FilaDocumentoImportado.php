<?php

namespace App\Servicios\Dto;

final readonly class FilaDocumentoImportado
{
    public function __construct(
        public string $numeroAutorizacion,
        public string $fechaEmision,
        public string $periodo,
        public string $tipoDteCodigo,
        public ?string $serie,
        public ?string $numero,
        public string $nitContraparte,
        public string $nombreContraparte,
        public string $direccion,
        public string $granTotal,
        public string $iva,
        public string $idp,
        public string $baseSinIva,
        public ?string $tipoSugerido,
        public string $moneda,
        public string $estado,
        public bool $anulado,
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
