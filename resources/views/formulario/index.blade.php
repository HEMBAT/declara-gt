@extends('layouts.app')

@section('titulo', 'Formulario SAT-2237 — declara·gt')

@section('contenido')
    <div class="flex-entre" style="margin-bottom:20px;">
        <div>
            <div class="encabezado-pantalla__titulo" style="margin-bottom:6px;">Llenar formulario</div>
            <div class="encabezado-pantalla__subtitulo">Subtotales por casilla para el IVA general (SAT-2237) y el desglose de ingresos del ISR (SAT-1311).</div>
        </div>
        @if($periodo !== null)
            <form method="GET" action="{{ route('formulario.index') }}">
                <select name="periodo" onchange="this.form.submit()" class="mono" style="border:1px solid rgba(10,10,10,0.15); border-radius:8px; padding:8px 12px; background:#fff;">
                    @foreach($periodosDisponibles as $opcion)
                        <option value="{{ $opcion }}" @selected($opcion === $periodo)>{{ \App\Support\Formato::periodoLabel($opcion) }}</option>
                    @endforeach
                </select>
            </form>
        @endif
    </div>

    @if($periodo === null)
        <div class="estado-vacio">Selecciona un período para ver su formulario. Importa facturas primero.</div>
    @else
        <div class="alerta-flash" style="margin-bottom:20px;">
            Declaraguate pre-carga sus propias casillas a partir de lo que reportaste al SAT. Verifica que estos montos cuadren con esa pre-carga antes de presentar — esta pantalla es un apoyo para llenar el formulario, no lo sustituye.
        </div>

        @include('partials.conteo-documentos')

        <div class="panel" style="margin-bottom:18px;">
            <div style="font-size:14.5px; font-weight:700; margin-bottom:14px;">Cuadro 3 — Débito fiscal</div>
            <div class="tarjeta__fila tarjeta__fila--tres tarjeta__fila--encabezado">
                <span>Casilla</span>
                <span>Base</span>
                <span>Débitos</span>
            </div>
            <div class="tarjeta__fila tarjeta__fila--tres">
                <span>Ventas gravadas con tarifa general — Bienes</span>
                <span class="mono">{{ \App\Support\Formato::moneda($desglose->ingresosBienes) }}</span>
                <span class="mono">{{ \App\Support\Formato::moneda($desglose->ventasGravadasBien) }}</span>
            </div>
            <div class="tarjeta__fila tarjeta__fila--tres">
                <span>Prestación de servicios gravados con tarifa general</span>
                <span class="mono">{{ \App\Support\Formato::moneda($desglose->ingresosServicios) }}</span>
                <span class="mono">{{ \App\Support\Formato::moneda($desglose->serviciosGravados) }}</span>
            </div>
            <div class="tarjeta__fila tarjeta__fila--tres tarjeta__fila--total">
                <span>Sumatoria de las columnas Base y Débitos</span>
                <span class="mono">{{ \App\Support\Formato::moneda($desglose->baseDebitoTotal) }}</span>
                <span class="mono">{{ \App\Support\Formato::moneda($desglose->debitoTotal) }}</span>
            </div>
        </div>

        <div class="panel" style="margin-bottom:18px;">
            <div style="font-size:14.5px; font-weight:700; margin-bottom:4px;">Cuadro 5 — Crédito fiscal por operaciones locales</div>
            <div class="encabezado-pantalla__subtitulo" style="margin-bottom:14px;">Declaraguate pide las dos columnas por casilla: la base es el valor de tus facturas sin IVA (y sin IDP en los combustibles).</div>
            <div class="tarjeta__fila tarjeta__fila--tres tarjeta__fila--encabezado">
                <span>Casilla</span>
                <span>Base</span>
                <span>Créditos</span>
            </div>
            <div class="tarjeta__fila tarjeta__fila--tres">
                <span>Compras de combustibles</span>
                <span class="mono">{{ \App\Support\Formato::moneda($desglose->baseCombustibles) }}</span>
                <span class="mono">{{ \App\Support\Formato::moneda($desglose->creditoCombustibles) }}</span>
            </div>
            <div class="tarjeta__fila tarjeta__fila--tres">
                <span>Otras compras de bienes</span>
                <span class="mono">{{ \App\Support\Formato::moneda($desglose->baseOtrasCompras) }}</span>
                <span class="mono">{{ \App\Support\Formato::moneda($desglose->creditoOtrasCompras) }}</span>
            </div>
            <div class="tarjeta__fila tarjeta__fila--tres">
                <span>Servicios adquiridos</span>
                <span class="mono">{{ \App\Support\Formato::moneda($desglose->baseServiciosAdquiridos) }}</span>
                <span class="mono">{{ \App\Support\Formato::moneda($desglose->creditoServiciosAdquiridos) }}</span>
            </div>
            <div class="tarjeta__fila tarjeta__fila--tres tarjeta__fila--solo-base">
                <span>Compras y servicios adquiridos de pequeños contribuyentes</span>
                <span class="mono">{{ \App\Support\Formato::moneda($desglose->basePequenosContribuyentes) }}</span>
                <span class="mono">—</span>
            </div>
            <div class="tarjeta__fila tarjeta__fila--tres tarjeta__fila--solo-base">
                <span>Compras que no generan derecho a compensación del crédito fiscal</span>
                <span class="mono">{{ \App\Support\Formato::moneda($desglose->baseNoDeducible) }}</span>
                <span class="mono">—</span>
            </div>
            <div class="tarjeta__fila tarjeta__fila--tres tarjeta__fila--total">
                <span>Total crédito fiscal</span>
                <span class="mono">{{ \App\Support\Formato::moneda($desglose->baseCreditoTotal) }}</span>
                <span class="mono">{{ \App\Support\Formato::moneda($desglose->creditoTotal) }}</span>
            </div>

            <div style="margin-top:16px; padding-top:12px; border-top:1px dashed rgba(10,10,10,0.12);">
                <div class="tarjeta__fila tarjeta__fila--solo-base">
                    <span>Remanente de crédito del período anterior</span>
                    <span class="mono">{{ \App\Support\Formato::moneda($desglose->remanenteAnterior) }}</span>
                </div>
            </div>
        </div>

        @php
            $ivaAPagar = (float) $resultadoIva->ivaAPagar;
            $remanenteCredito = (float) $resultadoIva->remanenteCredito;
        @endphp
        <div class="monto-final" style="margin-bottom:18px;">
            <div>
                <div class="monto-final__etiqueta">{{ $ivaAPagar > 0 ? 'IVA a pagar' : 'Remanente de crédito para el siguiente período' }}</div>
                <div class="monto-final__valor @if($ivaAPagar > 0) monto-final__valor--pendiente @endif mono">{{ \App\Support\Formato::moneda($ivaAPagar > 0 ? $resultadoIva->ivaAPagar : $resultadoIva->remanenteCredito) }}</div>
            </div>
            <div class="monto-final__nota">Débito − crédito − remanente anterior.</div>
        </div>

        <div class="panel">
            <div style="font-size:14.5px; font-weight:700; margin-bottom:14px;">Desglose de ingresos — SAT-1311</div>
            <div class="tarjeta__fila">
                <span>Ingresos por venta de bienes</span>
                <span class="mono">{{ \App\Support\Formato::moneda($desglose->ingresosBienes) }}</span>
            </div>
            <div class="tarjeta__fila">
                <span>Ingresos por prestación de servicios</span>
                <span class="mono">{{ \App\Support\Formato::moneda($desglose->ingresosServicios) }}</span>
            </div>
            <div class="tarjeta__fila tarjeta__fila--total">
                <span>Total base gravable ISR</span>
                <span class="mono">{{ \App\Support\Formato::moneda($desglose->baseDebitoTotal) }}</span>
            </div>
        </div>
    @endif
@endsection
