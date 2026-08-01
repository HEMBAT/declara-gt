<?php

namespace App\Servicios;

use App\Enums\TipoDeclaracion;
use App\Models\Declaracion;
use App\Models\Documento;
use App\Servicios\Dto\LineaSat2237;
use App\Servicios\Dto\ResultadoDesgloseSat2237;
use Carbon\Carbon;

/**
 * Orquesta el desglose por casilla del SAT-2237 de un período: resuelve las
 * líneas de emitidas/recibidas desde Eloquent y delega la aritmética pura a
 * DesgloseSat2237.
 *
 * A diferencia de CalculoIvaPeriodoService, este orquestador NO persiste
 * nada — es una vista derivada de los mismos documentos que ya calcula y
 * guarda CalculoIvaPeriodoService. Solo lee el remanente ya guardado por
 * ese servicio; nunca escribe en `declaraciones`, para no tener dos
 * escritores del mismo dato.
 */
final class CalculoDesgloseSat2237PeriodoService
{
    public function __construct(
        private readonly DesgloseSat2237 $calculadora = new DesgloseSat2237,
    ) {}

    public function calcularPeriodo(string $periodo): ResultadoDesgloseSat2237
    {
        $lineasEmitidas = Documento::query()
            ->with('tipoDte')
            ->paraDebitoIva($periodo)
            ->get()
            ->map(fn (Documento $documento) => new LineaSat2237(
                iva: (string) $documento->iva,
                baseSinIva: (string) $documento->base_sin_iva,
                signo: $documento->tipoDte->signo,
                tipo: $documento->tipo?->value,
                generaCredito: null,
            ));

        $lineasRecibidas = Documento::query()
            ->with('tipoDte')
            ->paraRecibidasIva($periodo)
            ->get()
            ->map(fn (Documento $documento) => new LineaSat2237(
                iva: (string) $documento->iva,
                baseSinIva: (string) $documento->base_sin_iva,
                signo: $documento->tipoDte->signo,
                tipo: $documento->tipo?->value,
                generaCredito: $documento->genera_credito,
                esPequenoContribuyente: $documento->tipoDte->codigo === 'FPEQ',
            ));

        $periodoAnterior = Carbon::createFromFormat('Y-m-d', $periodo.'-01')
            ->subMonthNoOverflow()
            ->format('Y-m');

        $declaracionAnterior = Declaracion::query()
            ->where('tipo', TipoDeclaracion::Iva)
            ->where('periodo', $periodoAnterior)
            ->first();

        $remanenteAnterior = (string) ($declaracionAnterior?->remanente_credito ?? '0.00');

        return $this->calculadora->calcular($lineasEmitidas, $lineasRecibidas, $remanenteAnterior);
    }
}
