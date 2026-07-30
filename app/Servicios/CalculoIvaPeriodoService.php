<?php

namespace App\Servicios;

use App\Enums\TipoDeclaracion;
use App\Models\Declaracion;
use App\Models\Documento;
use App\Servicios\Dto\LineaIva;
use App\Servicios\Dto\ResultadoIva;
use Carbon\Carbon;

/**
 * Orquesta el cálculo del IVA general de un período: resuelve las líneas de
 * débito/crédito desde Eloquent y delega la aritmética pura a
 * CalculadoraIvaGeneral.
 *
 * A diferencia de CalculoIsrPeriodoService, este orquestador SÍ persiste su
 * resultado (tabla `declaraciones`): el remanente de crédito de un período
 * es la entrada del siguiente (§10 del brief), así que cada cálculo guarda
 * su remanente para que el período siguiente pueda leerlo. No es una
 * caché — se recalcula igual cada vez, solo se guarda el remanente.
 */
final class CalculoIvaPeriodoService
{
    public function __construct(
        private readonly CalculadoraIvaGeneral $calculadora = new CalculadoraIvaGeneral,
    ) {}

    public function calcularPeriodo(string $periodo): ResultadoIva
    {
        $lineasDebito = Documento::query()
            ->with('tipoDte')
            ->paraDebitoIva($periodo)
            ->get()
            ->map(fn (Documento $documento) => new LineaIva(
                iva: (string) $documento->iva,
                signo: $documento->tipoDte->signo,
            ));

        $lineasCredito = Documento::query()
            ->with('tipoDte')
            ->paraCreditoIva($periodo)
            ->get()
            ->map(fn (Documento $documento) => new LineaIva(
                iva: (string) $documento->iva,
                signo: $documento->tipoDte->signo,
            ));

        $periodoAnterior = Carbon::createFromFormat('Y-m-d', $periodo.'-01')
            ->subMonthNoOverflow()
            ->format('Y-m');

        $declaracionAnterior = Declaracion::query()
            ->where('tipo', TipoDeclaracion::Iva)
            ->where('periodo', $periodoAnterior)
            ->first();

        $remanenteAnterior = $declaracionAnterior?->remanente_credito ?? '0.00';

        $resultado = $this->calculadora->calcular($lineasDebito, $lineasCredito, (string) $remanenteAnterior);

        Declaracion::query()->updateOrCreate(
            ['tipo' => TipoDeclaracion::Iva, 'periodo' => $periodo],
            [
                'montos' => $resultado->toArray(),
                'remanente_credito' => $resultado->remanenteCredito,
            ]
        );

        return $resultado;
    }
}
