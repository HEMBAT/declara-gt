<?php

namespace App\Servicios;

use App\Enums\TipoDeclaracion;
use App\Models\Declaracion;
use App\Models\Documento;
use App\Models\RetencionIva;
use App\Servicios\Dto\LineaIva;
use App\Servicios\Dto\ResultadoIva;
use Illuminate\Support\Facades\DB;

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
 * También encadena el saldo de retenciones de IVA (cuadro 7), con la misma
 * mecánica. Ambos remanentes de entrada pueden venir de Declaraguate en vez
 * de la cadena (ver Declaracion::remanenteIvaAnterior).
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

        $resultado = $this->calculadora->calcular(
            $lineasDebito,
            $lineasCredito,
            remanenteAnterior: Declaracion::remanenteIvaAnterior($periodo),
            remanenteRetencionesAnterior: Declaracion::remanenteRetencionesIvaAnterior($periodo),
            acreditamientoRetenciones: Declaracion::delPeriodo($periodo)?->acreditamiento_retenciones ?? '0.00',
            retencionesPeriodo: RetencionIva::totalDelPeriodo($periodo),
        );

        Declaracion::query()->updateOrCreate(
            ['tipo' => TipoDeclaracion::Iva, 'periodo' => $periodo],
            [
                'montos' => $resultado->toArray(),
                'remanente_credito' => $resultado->remanenteCredito,
                'remanente_retenciones' => $resultado->saldoRetenciones,
            ]
        );

        return $resultado;
    }

    /**
     * Guarda datos que el usuario copió de Declaraguate (remanentes,
     * acreditamiento) en la declaración del período y recalcula la cadena
     * desde ahí, todo en una transacción.
     *
     * @param  array<string, string|null>  $columnas  columna de `declaraciones` => valor
     */
    public function anotarEnPeriodo(string $periodo, array $columnas): void
    {
        DB::transaction(function () use ($periodo, $columnas) {
            // Garantiza que exista la fila del período antes de anotarle nada.
            $this->calcularPeriodo($periodo);

            Declaracion::query()
                ->where('tipo', TipoDeclaracion::Iva)
                ->where('periodo', $periodo)
                ->update($columnas);

            $this->recalcularDesde($periodo);
        });
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
