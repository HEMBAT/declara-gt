<?php

namespace App\Servicios\Dto;

final readonly class ResultadoIva
{
    /**
     * Todos los montos en formato decimal "0.00". `resultado` es la única
     * cifra que puede ser negativa (débito − crédito − remanente anterior);
     * `ivaAPagar` y `remanenteCredito` ya vienen recortados a cero.
     *
     * `impuestoDeterminado` es el "Saldo del impuesto" del SAT-2237, antes de
     * retenciones; `ivaAPagar` es el "Impuesto a pagar", ya descontadas.
     * `remanenteRetenciones` es la fila "(=) Remanente de retenciones IVA
     * recibidas en el período" (anterior − acreditamiento) y
     * `saldoRetenciones` el "Saldo de retenciones para el período siguiente".
     */
    public function __construct(
        public string $debito,
        public string $credito,
        public string $remanenteAnterior,
        public string $resultado,
        public string $ivaAPagar,
        public string $remanenteCredito,
        public string $impuestoDeterminado,
        public string $remanenteRetencionesAnterior,
        public string $acreditamientoRetenciones,
        public string $remanenteRetenciones,
        public string $retencionesPeriodo,
        public string $retencionesAplicadas,
        public string $saldoRetenciones,
    ) {}

    /**
     * @return array<string, string>
     */
    public function toArray(): array
    {
        return get_object_vars($this);
    }
}
