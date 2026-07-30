@extends('layouts.app')

@section('titulo', 'Dashboard — declara·gt')

@section('contenido')
    @if($periodoSeleccionado !== null)
        @php
            $estado = $periodo->estado ?? 'pendiente';
            $estadoMeta = [
                'pendiente' => ['label' => 'Pendiente de calcular', 'clase' => 'insignia--pendiente'],
                'calculado' => ['label' => 'Calculado, sin declarar', 'clase' => 'insignia--calculado'],
                'declarado' => ['label' => 'Declarado / presentado', 'clase' => 'insignia--declarado'],
            ][$estado];
            $isrDeterminado = (float) $resultado->isrDeterminado;
            $totalRetenciones = (float) $resultado->totalRetenciones;
            $ivaAPagar = (float) $resultadoIva->ivaAPagar;
            $remanenteCredito = (float) $resultadoIva->remanenteCredito;
        @endphp

        <div class="flex-entre" style="margin-bottom:22px;">
            <div>
                <div class="encabezado-pantalla__subtitulo" style="text-transform:uppercase; letter-spacing:.08em; font-weight:600; margin-bottom:6px;">Período</div>
                <form method="GET" action="{{ route('dashboard') }}">
                    <select name="periodo" onchange="this.form.submit()" style="font-family:var(--fuente-titulo); font-size:24px; font-weight:700; background:transparent; border:none; border-bottom:2px solid var(--verde-quetzal); padding:2px 4px 4px 0; cursor:pointer;">
                        @foreach($periodosDisponibles as $opcion)
                            <option value="{{ $opcion }}" @selected($opcion === $periodoSeleccionado)>{{ \App\Support\Formato::periodoLabel($opcion) }}</option>
                        @endforeach
                        <option value="__none__" @selected($periodoSeleccionado === null)>— Ver primer uso (sin período) —</option>
                    </select>
                </form>
            </div>
            <div style="display:flex; align-items:center; gap:10px; flex-wrap:wrap;">
                <div class="insignia {{ $estadoMeta['clase'] }}">{{ $estadoMeta['label'] }}</div>
                @if($estado === 'pendiente' && $cantidadFacturas > 0)
                    <form method="POST" action="{{ route('dashboard.calcular', $periodoSeleccionado) }}">
                        @csrf
                        <button type="submit" class="btn btn--oscuro">Marcar como calculado</button>
                    </form>
                @endif
                @if($estado === 'calculado')
                    <form method="POST" action="{{ route('dashboard.declarar', $periodoSeleccionado) }}">
                        @csrf
                        <button type="submit" class="btn btn--primario">Marcar como declarado</button>
                    </form>
                @endif
            </div>
        </div>

        @if($tieneDocumentos)
            <div style="font-family:var(--fuente-titulo); font-size:15px; font-weight:700; margin-bottom:10px;">ISR Opcional Simplificado — SAT-1311</div>
            <div class="tarjetas">
                <div class="tarjeta">
                    <div class="tarjeta__etiqueta">Total facturado</div>
                    <div class="tarjeta__valor mono">{{ \App\Support\Formato::moneda($totalFacturado) }}</div>
                    <div class="tarjeta__nota">{{ $cantidadFacturas }} facturas activas</div>
                </div>
                <div class="tarjeta">
                    <div class="tarjeta__etiqueta">Total sin IVA</div>
                    <div class="tarjeta__valor mono">{{ \App\Support\Formato::moneda($resultado->baseGravable) }}</div>
                    <div class="tarjeta__nota">IVA: {{ \App\Support\Formato::moneda($totalIva) }}</div>
                    <div style="margin-top:10px; padding-top:8px; border-top:1px solid rgba(10,10,10,0.06);">
                        <div class="tarjeta__fila"><span>Bienes</span><span class="mono">{{ \App\Support\Formato::moneda($desglose->ingresosBienes) }}</span></div>
                        <div class="tarjeta__fila"><span>Servicios</span><span class="mono">{{ \App\Support\Formato::moneda($desglose->ingresosServicios) }}</span></div>
                    </div>
                </div>
                <div class="tarjeta">
                    <div class="tarjeta__etiqueta">ISR calculado — tramos</div>
                    <div class="tarjeta__fila"><span>5% hasta Q30,000</span><span class="mono">{{ \App\Support\Formato::moneda($resultado->tramo1Monto) }}</span></div>
                    <div class="tarjeta__fila"><span>7% excedente</span><span class="mono">{{ \App\Support\Formato::moneda($resultado->tramo2Monto) }}</span></div>
                    <div class="tarjeta__fila tarjeta__fila--total"><span>Impuesto</span><span class="mono">{{ \App\Support\Formato::moneda($resultado->isrDeterminado) }}</span></div>
                </div>
                <div class="tarjeta">
                    <div class="tarjeta__etiqueta">Retenciones acreditadas</div>
                    <div class="tarjeta__valor mono">{{ \App\Support\Formato::moneda($resultado->totalRetenciones) }}</div>
                    <div class="tarjeta__nota">{{ $cantRetenciones }} constancias</div>
                </div>
            </div>

            <div class="monto-final">
                <div>
                    <div class="monto-final__etiqueta">Monto final a pagar</div>
                    <div class="monto-final__valor @if($isrDeterminado > $totalRetenciones) monto-final__valor--pendiente @endif mono">{{ \App\Support\Formato::moneda($resultado->isrAPagar) }}</div>
                </div>
                <div class="monto-final__nota">
                    @if($isrDeterminado > $totalRetenciones)
                        Este monto debe pagarse en el formulario SAT-1311 dentro del plazo mensual.
                    @elseif($totalRetenciones > $isrDeterminado)
                        Tus retenciones superan el impuesto determinado — no hay saldo pendiente este período.
                    @else
                        No hay impuesto pendiente este período.
                    @endif
                </div>
            </div>

            <div class="flex-entre" style="margin-bottom:10px; margin-top:8px;">
                <div style="font-family:var(--fuente-titulo); font-size:15px; font-weight:700;">IVA General — SAT-2237</div>
                <a href="{{ route('formulario.index', ['periodo' => $periodoSeleccionado]) }}" class="enlace-discreto">Ver formulario completo →</a>
            </div>
            <div class="tarjetas">
                <div class="tarjeta">
                    <div class="tarjeta__etiqueta">Débito fiscal</div>
                    <div class="tarjeta__valor mono">{{ \App\Support\Formato::moneda($resultadoIva->debito) }}</div>
                    <div class="tarjeta__nota">IVA de tus facturas emitidas</div>
                    <div style="margin-top:10px; padding-top:8px; border-top:1px solid rgba(10,10,10,0.06);">
                        <div class="tarjeta__fila"><span>Bienes</span><span class="mono">{{ \App\Support\Formato::moneda($desglose->ventasGravadasBien) }}</span></div>
                        <div class="tarjeta__fila"><span>Servicios</span><span class="mono">{{ \App\Support\Formato::moneda($desglose->serviciosGravados) }}</span></div>
                    </div>
                </div>
                <div class="tarjeta">
                    <div class="tarjeta__etiqueta">Crédito fiscal</div>
                    <div class="tarjeta__valor mono">{{ \App\Support\Formato::moneda($resultadoIva->credito) }}</div>
                    <div class="tarjeta__nota">IVA de tus facturas recibidas con derecho a crédito</div>
                    <div style="margin-top:10px; padding-top:8px; border-top:1px solid rgba(10,10,10,0.06);">
                        <div class="tarjeta__fila"><span>Combustibles</span><span class="mono">{{ \App\Support\Formato::moneda($desglose->creditoCombustibles) }}</span></div>
                        <div class="tarjeta__fila"><span>Otras compras</span><span class="mono">{{ \App\Support\Formato::moneda($desglose->creditoOtrasCompras) }}</span></div>
                        <div class="tarjeta__fila"><span>Servicios</span><span class="mono">{{ \App\Support\Formato::moneda($desglose->creditoServiciosAdquiridos) }}</span></div>
                    </div>
                </div>
                <div class="tarjeta">
                    <div class="tarjeta__etiqueta">Remanente del período anterior</div>
                    <div class="tarjeta__valor mono">{{ \App\Support\Formato::moneda($resultadoIva->remanenteAnterior) }}</div>
                    <div class="tarjeta__nota">Crédito acumulado que no se usó antes</div>
                </div>
            </div>

            <div class="monto-final">
                <div>
                    <div class="monto-final__etiqueta">{{ $ivaAPagar > 0 ? 'IVA a pagar' : 'Remanente de crédito para el siguiente período' }}</div>
                    <div class="monto-final__valor @if($ivaAPagar > 0) monto-final__valor--pendiente @endif mono">{{ \App\Support\Formato::moneda($ivaAPagar > 0 ? $resultadoIva->ivaAPagar : $resultadoIva->remanenteCredito) }}</div>
                </div>
                <div class="monto-final__nota">
                    @if($ivaAPagar > 0)
                        Este monto debe pagarse en el formulario SAT-2237 dentro del plazo mensual.
                    @elseif($remanenteCredito > 0)
                        Tu crédito fiscal superó al débito — no hay IVA que pagar y el excedente se arrastra al siguiente período.
                    @else
                        No hay IVA pendiente este período.
                    @endif
                </div>
            </div>
        @else
            <div class="estado-vacio">
                <div class="estado-vacio__icono"><span></span></div>
                <div class="estado-vacio__titulo">Sin facturas para {{ \App\Support\Formato::periodoLabel($periodoSeleccionado) }}</div>
                <div class="estado-vacio__texto">Aún no se ha importado el archivo de facturación del SAT para este período.</div>
                <a href="{{ route('importar.index') }}" class="btn btn--primario">Importar facturas</a>
            </div>
        @endif
    @else
        <div class="bienvenida">
            <div class="bienvenida__icono"><span></span></div>
            <div class="bienvenida__titulo">Bienvenido a declara·gt</div>
            <div class="bienvenida__texto">Es tu primera vez aquí. Importa el archivo de facturación electrónica que exporta el SAT y calculamos tu ISR Opcional Simplificado del mes automáticamente — todo en este equipo.</div>
            <a href="{{ route('importar.index') }}" class="btn btn--primario">Importar mi primer archivo</a>
            @if($periodosDisponibles->isNotEmpty())
                <a href="{{ route('dashboard', ['periodo' => $periodosDisponibles->last()]) }}" class="enlace-discreto">Volver a un período existente</a>
            @endif
        </div>
    @endif

    @if($periodoSeleccionado !== null)
        <div style="margin-top:10px;">
            <a href="{{ route('dashboard', ['periodo' => '__none__']) }}" class="enlace-discreto">Ver pantalla de bienvenida (primer uso) →</a>
        </div>
    @endif
@endsection
