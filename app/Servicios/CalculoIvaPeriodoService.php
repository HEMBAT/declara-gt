<?php

namespace App\Servicios;

use App\Enums\TipoDeclaracion;
use App\Models\Declaracion;
use App\Models\Documento;
use App\Servicios\Dto\LineaIva;
use App\Servicios\Dto\ResultadoIva;

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
 *
 * El remanente de entrada puede venir de Declaraguate en vez de la cadena
 * (ver Declaracion::remanenteIvaAnterior).
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

        $remanenteAnterior = Declaracion::remanenteIvaAnterior($periodo);

        $resultado = $this->calculadora->calcular($lineasDebito, $lineasCredito, $remanenteAnterior);

        Declaracion::query()->updateOrCreate(
            ['tipo' => TipoDeclaracion::Iva, 'periodo' => $periodo],
            [
                'montos' => $resultado->toArray(),
                'remanente_credito' => $resultado->remanenteCredito,
            ]
        );

        return $resultado;
    }

    /**
     * Recalcula el período y, en orden, todos los posteriores ya calculados,
     * para que un cambio en el remanente de uno se propague por la cadena en
     * vez de quedar obsoleto hasta que el usuario abra cada mes.
     */
    public function recalcularDesde(string $periodo): void
    {
        $periodosPosteriores = Declaracion::query()
            ->where('tipo', TipoDeclaracion::Iva)
            ->where('periodo', '>', $periodo)
            ->orderBy('periodo')
            ->pluck('periodo');

        $this->calcularPeriodo($periodo);

        foreach ($periodosPosteriores as $periodoPosterior) {
            $this->calcularPeriodo($periodoPosterior);
        }
    }
}
