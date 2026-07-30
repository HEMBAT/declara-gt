<?php

namespace App\Servicios\Dto;

final readonly class DraftImportacion
{
    /**
     * @param  array<int, FilaDocumentoImportado>  $filas  filas parseables del archivo
     * @param  array<int, ClientePorClasificar>  $clientesPorClasificar  NITs de contraparte no registrados
     * @param  array<int, string>  $advertencias
     * @param  int  $filasConError  filas descartadas por datos ilegibles (fecha, NIT o UUID inválidos)
     */
    public function __construct(
        public array $filas,
        public array $clientesPorClasificar,
        public array $advertencias,
        public int $filasConError,
    ) {}

    /**
     * Laravel serializa la sesión como JSON por defecto (config/session.php),
     * lo que descarta el tipo de cualquier objeto guardado directamente. Este
     * draft se persiste en sesión entre pasos del wizard de importación como
     * array plano vía toArray()/fromArray(), no como objeto.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'filas' => array_map(fn (FilaDocumentoImportado $f) => $f->toArray(), $this->filas),
            'clientesPorClasificar' => array_map(fn (ClientePorClasificar $c) => $c->toArray(), $this->clientesPorClasificar),
            'advertencias' => $this->advertencias,
            'filasConError' => $this->filasConError,
        ];
    }

    /**
     * @param  array<string, mixed>  $datos
     */
    public static function fromArray(array $datos): self
    {
        return new self(
            filas: array_map(fn (array $f) => FilaDocumentoImportado::fromArray($f), $datos['filas']),
            clientesPorClasificar: array_map(fn (array $c) => ClientePorClasificar::fromArray($c), $datos['clientesPorClasificar']),
            advertencias: $datos['advertencias'],
            filasConError: $datos['filasConError'],
        );
    }
}
