<?php

namespace App\Servicios;

use App\Models\Documento;
use App\Models\ParametroImpuesto;
use App\Models\Retencion;
use App\Servicios\Dto\LineaDocumento;
use App\Servicios\Dto\ResultadoIsr;
use App\Servicios\Dto\TramoIsr;
use Carbon\Carbon;

/**
 * Orquesta el cálculo del ISR de un período: resuelve los documentos, las
 * retenciones y los parámetros de impuesto vigentes desde Eloquent, y delega
 * la aritmética pura a CalculadoraIsrOpcional.
 */
final class CalculoIsrPeriodoService
{
    public function __construct(
        private readonly CalculadoraIsrOpcional $calculadora = new CalculadoraIsrOpcional,
    ) {}

    /**
     * @param  string  $periodo  formato "YYYY-MM"
     */
    public function calcularPeriodo(string $periodo): ResultadoIsr
    {
        $fechaReferencia = Carbon::createFromFormat('Y-m-d', $periodo.'-01');

        $lineas = Documento::query()
            ->paraCalculoIsr($periodo)
            ->with('tipoDte')
            ->get()
            ->map(fn (Documento $documento) => new LineaDocumento(
                baseSinIva: (string) $documento->base_sin_iva,
                signo: $documento->tipoDte->signo,
            ));

        $totalRetenciones = number_format(
            (float) Retencion::query()->where('periodo', $periodo)->sum('monto_isr'),
            2, '.', ''
        );

        $tramos = ParametroImpuesto::tramosIsrVigentesEn($fechaReferencia)
            ->map(fn (ParametroImpuesto $parametro) => new TramoIsr(
                tasa: (string) $parametro->tasa,
                limiteInferior: $parametro->limite_inferior !== null ? (string) $parametro->limite_inferior : null,
                limiteSuperior: $parametro->limite_superior !== null ? (string) $parametro->limite_superior : null,
            ))
            ->values()
            ->all();

        return $this->calculadora->calcular($lineas, $tramos, $totalRetenciones);
    }
}
