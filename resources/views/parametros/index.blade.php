@extends('layouts.app')

@section('titulo', 'Parámetros — declara·gt')

@section('contenido')
    <div class="encabezado-pantalla">
        <div class="encabezado-pantalla__titulo">Parámetros</div>
        <div class="encabezado-pantalla__subtitulo">Tasas vigentes usadas en el cálculo. Solo lectura — agrega una nueva vigencia si la ley cambia.</div>
    </div>

    <div class="panel" style="margin-bottom:18px;">
        <div class="flex-entre" style="margin-bottom:14px;">
            <div style="font-size:14.5px; font-weight:700;">IVA</div>
        </div>

        <div class="tabla-scroll">
            <table class="tabla" style="min-width:480px;">
                <thead>
                    <tr><th>Tasa</th><th>Vigente desde</th><th>Vigente hasta</th></tr>
                </thead>
                <tbody>
                    @foreach($iva as $fila)
                        <tr>
                            <td class="mono" style="font-weight:600;">{{ number_format($fila->tasa * 100, 2) }}%</td>
                            <td class="mono">{{ \App\Support\Formato::fecha($fila->vigente_desde) }}</td>
                            <td class="mono">{{ $fila->vigente_hasta ? \App\Support\Formato::fecha($fila->vigente_hasta) : 'Vigente' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <details style="margin-top:16px;">
            <summary class="btn--contorno" style="display:inline-block;">+ Agregar nueva vigencia</summary>
            <form method="POST" action="{{ route('parametros.iva') }}" style="margin-top:16px; padding:16px; background:rgba(14,107,79,0.04); border-radius:10px; display:flex; flex-wrap:wrap; gap:12px; align-items:flex-end;">
                @csrf
                <div class="campo" style="margin-bottom:0;">
                    <label class="campo__etiqueta" for="iva-tasa">Tasa (%)</label>
                    <input type="number" step="0.01" id="iva-tasa" name="tasa" class="mono" style="width:90px;" required>
                </div>
                <div class="campo" style="margin-bottom:0;">
                    <label class="campo__etiqueta" for="iva-desde">Vigente desde</label>
                    <input type="date" id="iva-desde" name="vigente_desde" class="mono" required>
                </div>
                <button type="submit" class="btn btn--primario">Guardar</button>
            </form>
        </details>
    </div>

    <div class="panel">
        <div class="flex-entre" style="margin-bottom:14px;">
            <div style="font-size:14.5px; font-weight:700;">ISR Opcional Simplificado — tramos mensuales</div>
        </div>

        <div class="tabla-scroll">
            <table class="tabla" style="min-width:560px;">
                <thead>
                    <tr><th>Tramo</th><th>Tasa</th><th>Límites</th><th>Vigente desde</th><th>Vigente hasta</th></tr>
                </thead>
                <tbody>
                    @foreach($isr as $fila)
                        <tr>
                            <td class="mono">{{ $fila->limite_superior !== null ? '1' : '2' }}</td>
                            <td class="mono" style="font-weight:600;">{{ number_format($fila->tasa * 100, 2) }}%</td>
                            <td class="mono">{{ \App\Support\Formato::moneda($fila->limite_inferior) }} – {{ $fila->limite_superior !== null ? \App\Support\Formato::moneda($fila->limite_superior) : '∞' }}</td>
                            <td class="mono">{{ \App\Support\Formato::fecha($fila->vigente_desde) }}</td>
                            <td class="mono">{{ $fila->vigente_hasta ? \App\Support\Formato::fecha($fila->vigente_hasta) : 'Vigente' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <details style="margin-top:16px;">
            <summary class="btn--contorno" style="display:inline-block;">+ Agregar nueva vigencia</summary>
            <form method="POST" action="{{ route('parametros.isr') }}" style="margin-top:16px; padding:16px; background:rgba(14,107,79,0.04); border-radius:10px; display:flex; flex-wrap:wrap; gap:12px; align-items:flex-end;">
                @csrf
                <div class="campo" style="margin-bottom:0;">
                    <label class="campo__etiqueta" for="isr-t1-tasa">Tramo 1 (%)</label>
                    <input type="number" step="0.01" id="isr-t1-tasa" name="tramo1_tasa" class="mono" style="width:80px;" required>
                </div>
                <div class="campo" style="margin-bottom:0;">
                    <label class="campo__etiqueta" for="isr-t1-limite">Límite tramo 1 (Q)</label>
                    <input type="number" step="0.01" id="isr-t1-limite" name="tramo1_limite" class="mono" style="width:120px;" required>
                </div>
                <div class="campo" style="margin-bottom:0;">
                    <label class="campo__etiqueta" for="isr-t2-tasa">Tramo 2 (%)</label>
                    <input type="number" step="0.01" id="isr-t2-tasa" name="tramo2_tasa" class="mono" style="width:80px;" required>
                </div>
                <div class="campo" style="margin-bottom:0;">
                    <label class="campo__etiqueta" for="isr-desde">Vigente desde</label>
                    <input type="date" id="isr-desde" name="vigente_desde" class="mono" required>
                </div>
                <button type="submit" class="btn btn--primario">Guardar</button>
            </form>
        </details>
    </div>
@endsection
