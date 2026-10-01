@extends('layouts.app')

@section('titulo', 'Retenciones — declara·gt')

@section('contenido')
    <div class="flex-entre" style="margin-bottom:20px;">
        <div>
            <div class="encabezado-pantalla__titulo" style="margin-bottom:6px;">Retenciones</div>
            <div class="encabezado-pantalla__subtitulo">Registra las constancias de retención de ISR y de IVA que te acreditan contra el impuesto del período.</div>
        </div>
        <form method="GET" action="{{ route('retenciones.index') }}">
            <select name="periodo" onchange="this.form.submit()" class="mono" style="border:1px solid rgba(10,10,10,0.15); border-radius:8px; padding:8px 12px; background:#fff;">
                @foreach($periodosDisponibles as $opcion)
                    <option value="{{ $opcion }}" @selected($opcion === $periodo)>{{ \App\Support\Formato::periodoLabel($opcion) }}</option>
                @endforeach
            </select>
        </form>
    </div>

    <div class="panel" style="margin-bottom:20px;">
        <div style="font-size:14.5px; font-weight:700; margin-bottom:14px;">Nueva retención de ISR — {{ \App\Support\Formato::periodoLabel($periodo) }}</div>
        <form method="POST" action="{{ route('retenciones.guardar') }}" style="display:flex; gap:12px; flex-wrap:wrap; align-items:flex-end;">
            @csrf
            <input type="hidden" name="periodo" value="{{ $periodo }}">
            <div class="campo" style="margin-bottom:0;">
                <label class="campo__etiqueta" for="monto_isr">Monto (Q)</label>
                <input type="number" step="0.01" min="0.01" id="monto_isr" name="monto_isr" class="mono" value="{{ old('monto_isr') }}" required>
            </div>
            <div class="campo" style="margin-bottom:0; flex:1; min-width:220px;">
                <label class="campo__etiqueta" for="descripcion">Descripción de la constancia</label>
                <input type="text" id="descripcion" name="descripcion" value="{{ old('descripcion') }}" placeholder="Ej. constancia C-1234 de Transportes Quetzal, S.A.">
            </div>
            <button type="submit" class="btn btn--primario">Guardar</button>
        </form>
        @error('monto_isr') <div class="error-campo">{{ $message }}</div> @enderror
    </div>

    @if($retenciones->isNotEmpty())
        <div class="tabla-envoltura">
            <div class="tabla-scroll">
                <table class="tabla">
                    <thead>
                        <tr><th>Descripción</th><th class="num">Monto</th><th style="width:60px;"></th></tr>
                    </thead>
                    <tbody>
                        @foreach($retenciones as $retencion)
                            <tr>
                                <td>{{ $retencion->descripcion ?: '—' }}</td>
                                <td class="mono num">{{ \App\Support\Formato::moneda($retencion->monto_isr) }}</td>
                                <td>
                                    <form method="POST" action="{{ route('retenciones.eliminar', $retencion) }}" onsubmit="return confirm('¿Eliminar esta retención?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn--fantasma" style="padding:5px 10px; font-size:12px;">Eliminar</button>
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot>
                        <tr style="border-top:1px solid rgba(10,10,10,0.08); font-weight:600;">
                            <td>Total</td>
                            <td class="mono num">{{ \App\Support\Formato::moneda($totalRetenciones) }}</td>
                            <td></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    @else
        <div class="estado-vacio">Aún no hay retenciones de ISR registradas para {{ \App\Support\Formato::periodoLabel($periodo) }}.</div>
    @endif

    <div class="encabezado-pantalla__titulo" style="font-size:20px; margin:32px 0 6px;">Retenciones de IVA</div>
    <div class="encabezado-pantalla__subtitulo" style="margin-bottom:16px;">Constancias que te entregan los agentes de retención al pagarte. Se acreditan contra el IVA a pagar del SAT-2237; lo que no se usa se arrastra al mes siguiente.</div>

    <div class="panel" style="margin-bottom:20px;">
        <div style="font-size:14.5px; font-weight:700; margin-bottom:14px;">Nueva retención de IVA — {{ \App\Support\Formato::periodoLabel($periodo) }}</div>
        <form method="POST" action="{{ route('retencionesIva.guardar') }}" style="display:flex; gap:12px; flex-wrap:wrap; align-items:flex-end;">
            @csrf
            <input type="hidden" name="periodo" value="{{ $periodo }}">
            <div class="campo" style="margin-bottom:0;">
                <label class="campo__etiqueta" for="monto_iva">Monto (Q)</label>
                <input type="text" inputmode="decimal" id="monto_iva" name="monto" class="mono" value="{{ old('monto') }}" placeholder="Ej. 96.43" required>
            </div>
            <div class="campo" style="margin-bottom:0; flex:1; min-width:220px;">
                <label class="campo__etiqueta" for="descripcion_iva">Descripción de la constancia</label>
                <input type="text" id="descripcion_iva" name="descripcion" value="{{ old('descripcion') }}" placeholder="Ej. constancia de retención de IVA de tu cliente">
            </div>
            <button type="submit" class="btn btn--primario">Guardar</button>
        </form>
        @error('monto') <div class="error-campo">{{ $message }}</div> @enderror
    </div>

    @if($retencionesIva->isNotEmpty())
        <div class="tabla-envoltura" style="margin-bottom:20px;">
            <div class="tabla-scroll">
                <table class="tabla">
                    <thead>
                        <tr><th>Descripción</th><th class="num">Monto</th><th style="width:60px;"></th></tr>
                    </thead>
                    <tbody>
                        @foreach($retencionesIva as $retencionIva)
                            <tr>
                                <td>{{ $retencionIva->descripcion ?: '—' }}</td>
                                <td class="mono num">{{ \App\Support\Formato::moneda($retencionIva->monto) }}</td>
                                <td>
                                    <form method="POST" action="{{ route('retencionesIva.eliminar', $retencionIva) }}" onsubmit="return confirm('¿Eliminar esta retención de IVA?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn--fantasma" style="padding:5px 10px; font-size:12px;">Eliminar</button>
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif

    <div class="panel">
        <div style="font-size:14.5px; font-weight:700; margin-bottom:14px;">Saldo de retenciones de IVA — cuadro 7 del SAT-2237</div>
        @include('partials.retenciones-iva-cuadro7')

        <form method="POST" action="{{ route('retencionesIva.saldo', $periodo) }}" style="margin-top:18px; padding-top:14px; border-top:1px solid rgba(10,10,10,0.06);">
            @csrf
            <div style="font-size:13px; font-weight:600; margin-bottom:4px;">Datos según Declaraguate</div>
            <div class="tarjeta__nota" style="margin-bottom:12px;">Si el remanente no coincide con el programa, copia aquí el de Declaraguate. Anota también la devolución si el SAT te acreditó retenciones en cuenta bancaria. Deja vacío lo que no aplique.</div>
            <div style="display:flex; gap:12px; flex-wrap:wrap; align-items:flex-end;">
                <div class="campo" style="margin-bottom:0;">
                    <label class="campo__etiqueta" for="remanente_retenciones_anterior_sat">Remanente del período anterior (Q)</label>
                    <input type="text" inputmode="decimal" id="remanente_retenciones_anterior_sat" name="remanente_retenciones_anterior_sat" class="mono" value="{{ old('remanente_retenciones_anterior_sat', $declaracionIva?->remanente_retenciones_anterior_sat) }}" placeholder="Ej. 1585">
                </div>
                <div class="campo" style="margin-bottom:0;">
                    <label class="campo__etiqueta" for="acreditamiento_retenciones">Monto acreditado (Q)</label>
                    <input type="text" inputmode="decimal" id="acreditamiento_retenciones" name="acreditamiento_retenciones" class="mono" value="{{ old('acreditamiento_retenciones', $declaracionIva?->acreditamiento_retenciones) }}">
                </div>
                <div class="campo" style="margin-bottom:0;">
                    <label class="campo__etiqueta" for="resolucion_acreditamiento">Número de resolución</label>
                    <input type="text" id="resolucion_acreditamiento" name="resolucion_acreditamiento" value="{{ old('resolucion_acreditamiento', $declaracionIva?->resolucion_acreditamiento) }}">
                </div>
                <button type="submit" class="btn btn--contorno">Guardar</button>
            </div>
            @error('remanente_retenciones_anterior_sat') <div class="error-campo">{{ $message }}</div> @enderror
            @error('acreditamiento_retenciones') <div class="error-campo">{{ $message }}</div> @enderror
            @error('resolucion_acreditamiento') <div class="error-campo">{{ $message }}</div> @enderror
        </form>
    </div>
@endsection
