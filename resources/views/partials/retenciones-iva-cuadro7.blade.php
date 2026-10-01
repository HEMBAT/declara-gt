{{--
    Saldo de retenciones de IVA, en el orden del cuadro 7 del SAT-2237.

    Se muestra en Retenciones y en la pantalla de formulario. Espera
    $resultadoIva (ResultadoIva) y, opcionalmente, $declaracionIva y
    $remanenteRetencionesCalculado para indicar si el remanente anterior
    viene de Declaraguate.
--}}
@php
    $remanenteSegunSat = isset($declaracionIva) ? $declaracionIva?->remanente_retenciones_anterior_sat : null;
    $resolucion = isset($declaracionIva) ? $declaracionIva?->resolucion_acreditamiento : null;
@endphp
<div class="tarjeta__fila">
    <span>
        Remanente de retenciones del período anterior
        @if($remanenteSegunSat !== null && isset($remanenteRetencionesCalculado))
            <span class="tarjeta__nota" style="display:block;">Según Declaraguate · el programa calculó {{ \App\Support\Formato::moneda($remanenteRetencionesCalculado) }}</span>
        @endif
    </span>
    <span class="mono">{{ \App\Support\Formato::moneda($resultadoIva->remanenteRetencionesAnterior) }}</span>
</div>
<div class="tarjeta__fila">
    <span>
        (−) Acreditamiento en cuenta bancaria
        @if($resolucion)
            <span class="tarjeta__nota" style="display:block;">Resolución {{ $resolucion }}</span>
        @endif
    </span>
    <span class="mono">{{ \App\Support\Formato::moneda($resultadoIva->acreditamientoRetenciones) }}</span>
</div>
<div class="tarjeta__fila">
    <span>(=) Remanente de retenciones IVA recibidas en el período</span>
    <span class="mono">{{ \App\Support\Formato::moneda($resultadoIva->remanenteRetenciones) }}</span>
</div>
<div class="tarjeta__fila">
    <span>Constancias de retenciones del IVA recibidas en el período</span>
    <span class="mono">{{ \App\Support\Formato::moneda($resultadoIva->retencionesPeriodo) }}</span>
</div>
<div class="tarjeta__fila">
    <span>Retenciones usadas contra el impuesto del período</span>
    <span class="mono">{{ \App\Support\Formato::moneda($resultadoIva->retencionesAplicadas) }}</span>
</div>
<div class="tarjeta__fila tarjeta__fila--total">
    <span>Saldo de retenciones para el período siguiente</span>
    <span class="mono">{{ \App\Support\Formato::moneda($resultadoIva->saldoRetenciones) }}</span>
</div>
@if((float) $resultadoIva->saldoRetenciones > 0 && (float) $resultadoIva->impuestoDeterminado === 0.0)
    <div class="tarjeta__nota" style="margin-top:10px;">
        Este mes no hay IVA a pagar, así que tus retenciones no se usan y se siguen acumulando. Puedes solicitar al SAT que acredite este saldo en tu cuenta bancaria; consúltalo con tu contador.
    </div>
@endif
