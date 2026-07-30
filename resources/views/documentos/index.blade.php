@extends('layouts.app')

@section('titulo', 'Documentos — declara·gt')

@section('contenido')
    <div class="flex-entre" style="margin-bottom:20px;">
        <div class="encabezado-pantalla__titulo" style="margin-bottom:0;">Documentos del período</div>

        <form method="GET" action="{{ route('documentos.index') }}" style="display:flex; gap:8px; flex-wrap:wrap;">
            <select name="periodo" onchange="this.form.submit()" class="mono" style="border:1px solid rgba(10,10,10,0.15); border-radius:8px; padding:8px 12px; background:#fff;">
                @foreach($periodosDisponibles as $opcion)
                    <option value="{{ $opcion }}" @selected($opcion === $periodo)>{{ \App\Support\Formato::periodoLabel($opcion) }}</option>
                @endforeach
            </select>
            <select name="direccion" onchange="this.form.submit()" style="border:1px solid rgba(10,10,10,0.15); border-radius:8px; padding:8px 12px; background:#fff;">
                <option value="">Todas las direcciones</option>
                <option value="emitida" @selected($direccion === 'emitida')>Emitidas</option>
                <option value="recibida" @selected($direccion === 'recibida')>Recibidas</option>
            </select>
            <select name="tipo" onchange="this.form.submit()" style="border:1px solid rgba(10,10,10,0.15); border-radius:8px; padding:8px 12px; background:#fff;">
                <option value="">Todos los tipos</option>
                <option value="bien" @selected($tipo === 'bien')>Bien</option>
                <option value="servicio" @selected($tipo === 'servicio')>Servicio</option>
                <option value="combustible" @selected($tipo === 'combustible')>Combustible</option>
            </select>
        </form>
    </div>

    @if($periodo === null)
        <div class="estado-vacio">Selecciona un período para ver sus facturas.</div>
    @elseif($documentos->isEmpty())
        <div class="estado-vacio">
            <div style="font-family:var(--fuente-titulo); font-size:17px; font-weight:700;">Sin facturas para {{ \App\Support\Formato::periodoLabel($periodo) }}</div>
            <div style="font-size:13.5px; color:rgba(10,10,10,0.55); max-width:340px;">Importa el archivo del SAT para este período.</div>
            <a href="{{ route('importar.index') }}" class="btn btn--primario">Importar facturas</a>
        </div>
    @else
        <div class="tabla-envoltura">
            <div class="tabla-scroll">
                <table class="tabla" style="min-width:900px;">
                    <thead>
                        <tr>
                            <th>Fecha</th><th>Cliente</th><th>DTE</th><th>Tipo</th>
                            <th class="num">Monto</th><th class="num">IVA</th><th class="num">Base sin IVA</th>
                            <th>Crédito fiscal</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($documentos as $documento)
                            @php
                                $signo = $documento->tipoDte->signo;
                                $colorMonto = $documento->anulado ? 'rgba(10,10,10,0.4)' : ($signo === -1 ? 'var(--alerta)' : 'inherit');
                            @endphp
                            <tr data-fila-documento class="{{ $documento->anulado ? 'fila-anulada' : '' }}">
                                <td class="mono">{{ \App\Support\Formato::fecha($documento->fecha_emision) }}</td>
                                <td>
                                    <div>{{ $documento->cliente->nombre }}</div>
                                    <div class="mono" style="font-size:11px; color:rgba(10,10,10,0.45);">{{ $documento->cliente->nit }}</div>
                                </td>
                                <td class="mono">{{ $documento->tipoDte->codigo }}</td>
                                <td>
                                    @if((float) $documento->idp > 0)
                                        <span style="color:rgba(10,10,10,0.6); font-size:12.5px;" title="Tiene IDP (impuesto de petróleo) — siempre es combustible">Combustible (IDP)</span>
                                    @else
                                        <select data-tipo-documento="{{ route('documentos.actualizarTipo', $documento) }}" style="border:1px solid rgba(10,10,10,0.12); border-radius:6px; padding:5px 6px; font-size:12.5px;">
                                            <option value="bien" @selected($documento->tipo?->value === 'bien')>Bien</option>
                                            <option value="servicio" @selected($documento->tipo?->value === 'servicio')>Servicio</option>
                                            <option value="combustible" @selected($documento->tipo?->value === 'combustible')>Combustible</option>
                                        </select>
                                        <span data-guardado style="font-size:11px; color:var(--verde-quetzal); opacity:0; margin-left:4px;">Guardado</span>
                                    @endif
                                </td>
                                <td class="mono num" style="color:{{ $colorMonto }};">{{ \App\Support\Formato::moneda((float) $documento->gran_total * $signo) }}</td>
                                <td class="mono num" style="color:{{ $colorMonto }};">{{ \App\Support\Formato::moneda((float) $documento->iva * $signo) }}</td>
                                <td class="mono num" style="color:{{ $colorMonto }};">{{ \App\Support\Formato::moneda((float) $documento->base_sin_iva * $signo) }}</td>
                                <td>
                                    @if($documento->direccion->value === 'recibida')
                                        @if($documento->tipoDte->codigo === 'FPEQ')
                                            <span style="color:rgba(10,10,10,0.4); font-size:12px;" title="Régimen de Pequeño Contribuyente — nunca genera crédito fiscal">No genera crédito (FPEQ)</span>
                                        @else
                                            <label style="display:inline-flex; align-items:center; gap:6px; cursor:pointer; font-size:12.5px;">
                                                <input type="checkbox" data-credito-documento="{{ route('documentos.actualizarCredito', $documento) }}" @checked($documento->genera_credito)>
                                                Genera crédito
                                            </label>
                                            <span data-guardado-credito style="font-size:11px; color:var(--verde-quetzal); opacity:0; margin-left:4px;">Guardado</span>
                                        @endif
                                    @else
                                        <span style="color:rgba(10,10,10,0.35);">—</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif
@endsection
