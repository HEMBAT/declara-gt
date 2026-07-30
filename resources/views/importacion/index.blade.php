@extends('layouts.app')

@section('titulo', 'Importación — declara·gt')

@section('contenido')
    <div class="encabezado-pantalla">
        <div class="encabezado-pantalla__titulo">Importación</div>
        <div class="encabezado-pantalla__subtitulo">Trae el archivo de facturación electrónica que exporta la Agencia Virtual del SAT.</div>
    </div>

    @error('clasificaciones')
        <div class="alerta-flash">{{ $message }}</div>
    @enderror

    @if($paso === 'idle')
        <form method="POST" action="{{ route('importar.procesar') }}" enctype="multipart/form-data" id="form-importar">
            @csrf
            <label for="archivo-input" class="dropzone" id="dropzone">
                <div class="dropzone__icono"><span></span></div>
                <div style="font-family:var(--fuente-titulo); font-size:17px; font-weight:700;">Arrastra tu archivo .xlsx aquí</div>
                <div style="font-size:13px; color:rgba(10,10,10,0.5); max-width:360px;">o haz clic para elegir el archivo exportado desde la Agencia Virtual SAT</div>
                <div class="mono" style="font-size:12px; color:rgba(10,10,10,0.4); margin-top:6px;">.xlsx · .xls</div>
            </label>
            <input type="file" name="archivo" id="archivo-input" accept=".xls,.xlsx" style="display:none;" onchange="document.getElementById('form-importar').submit()">
        </form>
    @endif

    @if($paso === 'preview')
        @php $totalPreview = collect($draft->filas)->sum(fn($f) => (float) $f->granTotal); @endphp
        <div class="panel">
            <div class="flex-entre" style="margin-bottom:16px;">
                <div style="font-size:14.5px; font-weight:600;">{{ count($draft->filas) }} facturas detectadas</div>
                <div class="mono" style="font-size:14.5px; font-weight:600;">{{ \App\Support\Formato::moneda($totalPreview) }}</div>
            </div>

            @if(!empty($draft->advertencias))
                <div class="alerta-flash">
                    @foreach($draft->advertencias as $advertencia)
                        <div>{{ $advertencia }}</div>
                    @endforeach
                </div>
            @endif

            <div class="tabla-scroll">
                <table class="tabla">
                    <thead>
                        <tr><th>Fecha</th><th>Cliente</th><th>Tipo DTE</th><th class="num">Monto</th></tr>
                    </thead>
                    <tbody>
                        @php $nitsNuevos = collect($draft->clientesPorClasificar)->pluck('nit'); @endphp
                        @foreach($draft->filas as $fila)
                            <tr>
                                <td class="mono">{{ \App\Support\Formato::fecha($fila->fechaEmision) }}</td>
                                <td>
                                    {{ $fila->nombreContraparte }}
                                    @if($nitsNuevos->contains($fila->nitContraparte))
                                        <span class="etiqueta-nuevo">cliente nuevo</span>
                                    @endif
                                </td>
                                <td class="mono">{{ $fila->tipoDteCodigo }}</td>
                                <td class="mono num">{{ \App\Support\Formato::moneda($fila->granTotal) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="panel__acciones">
                <form method="POST" action="{{ route('importar.cancelar') }}">
                    @csrf
                    <button type="submit" class="btn btn--fantasma">Cancelar</button>
                </form>
                <form method="POST" action="{{ route('importar.continuar') }}">
                    @csrf
                    <button type="submit" class="btn btn--primario">Continuar</button>
                </form>
            </div>
        </div>
    @endif

    @if($paso === 'clasificar')
        <div class="panel">
            <div style="font-size:15px; font-weight:700; margin-bottom:4px;">Clientes nuevos por clasificar</div>
            <div style="font-size:13px; color:rgba(10,10,10,0.55); margin-bottom:18px;">{{ count($draft->clientesPorClasificar) }} NIT no reconocidos. Asigna un tipo para calcular el ISR correctamente.</div>

            <form method="POST" action="{{ route('importar.guardarClasificacion') }}">
                @csrf
                @foreach($draft->clientesPorClasificar as $indice => $candidato)
                    @php
                        $tipoPrevio = old('clasificaciones.'.$candidato->nit, $candidato->tipoSugerido);
                        $creditoPrevio = old('credito.'.$candidato->nit, $candidato->generaCreditoSugerido ? '1' : '0');
                        $faltante = isset($nitsFaltantes) && $nitsFaltantes->contains($candidato->nit);
                    @endphp
                    <div class="fila-clasificar" @if($faltante) style="border-left:3px solid var(--alerta); padding-left:10px; background:rgba(200,53,79,0.04);" @endif>
                        <div>
                            <div style="font-size:13.5px; font-weight:600;">{{ $candidato->nombre }}</div>
                            <div class="mono" style="font-size:12px; color:rgba(10,10,10,0.5);">{{ $candidato->nit }}</div>
                        </div>
                        @if($candidato->tipoBloqueado)
                            <div>
                                <input type="hidden" name="clasificaciones[{{ $candidato->nit }}]" value="combustible">
                                <span style="font-size:12px; color:rgba(10,10,10,0.4);" title="Todas sus facturas en este archivo tienen IDP (impuesto de petróleo) — siempre es combustible">Combustible (por IDP)</span>
                            </div>
                        @else
                            <div class="opciones-tipo">
                                @foreach(['bien' => 'Bien', 'servicio' => 'Servicio', 'combustible' => 'Combustible'] as $valor => $etiqueta)
                                    @php $idOpcion = 'tipo-'.$indice.'-'.$valor; @endphp
                                    <input type="radio" class="pastilla-radio" id="{{ $idOpcion }}"
                                           name="clasificaciones[{{ $candidato->nit }}]" value="{{ $valor }}"
                                           @checked($tipoPrevio === $valor)>
                                    <label for="{{ $idOpcion }}" class="pastilla-tipo">{{ $etiqueta }}</label>
                                @endforeach
                            </div>
                        @endif
                    </div>
                    @if($candidato->esProveedor)
                        <div class="fila-clasificar" style="padding-top:0; border-top:none;">
                            <div style="font-size:12.5px; color:rgba(10,10,10,0.6);">¿Genera crédito fiscal?</div>
                            @if($candidato->creditoBloqueado)
                                <div style="font-size:12px; color:rgba(10,10,10,0.4);" title="Todas sus facturas en este archivo son FPEQ (Pequeño Contribuyente) — nunca generan crédito fiscal">No genera crédito (FPEQ)</div>
                            @else
                                <div class="opciones-tipo">
                                    @foreach(['1' => 'Sí', '0' => 'No'] as $valor => $etiqueta)
                                        @php $idCredito = 'credito-'.$indice.'-'.$valor; @endphp
                                        <input type="radio" class="pastilla-radio" id="{{ $idCredito }}"
                                               name="credito[{{ $candidato->nit }}]" value="{{ $valor }}"
                                               @checked($creditoPrevio === (string) $valor)>
                                        <label for="{{ $idCredito }}" class="pastilla-tipo">{{ $etiqueta }}</label>
                                    @endforeach
                                </div>
                            @endif
                        </div>
                    @endif
                @endforeach

                <div class="panel__acciones">
                    <button type="submit" formaction="{{ route('importar.cancelar') }}" class="btn btn--fantasma">Cancelar</button>
                    <button type="submit" class="btn btn--primario">Guardar y finalizar importación</button>
                </div>
            </form>
        </div>
    @endif

    @if($paso === 'done')
        @php $periodosImportados = collect($draft->filas)->pluck('periodo')->unique()->sort()->values(); @endphp
        <div class="panel paso-completo">
            <div class="paso-completo__icono"><span></span></div>
            <div style="font-family:var(--fuente-titulo); font-size:18px; font-weight:700;">{{ $resultado->nuevos }} facturas nuevas, {{ $resultado->actualizados }} actualizadas</div>
            <div style="font-size:13.5px; color:rgba(10,10,10,0.55);">
                {{ count($draft->clientesPorClasificar) }} clientes nuevos agregados a tu directorio.
                @if($resultado->ignorados > 0)
                    {{ $resultado->ignorados }} documentos no cuentan para el cálculo del ISR (anulados o en otra moneda).
                @endif
            </div>
            <div style="display:flex; gap:10px; margin-top:8px;">
                <a href="{{ route('importar.index') }}" class="btn btn--fantasma">Importar otro archivo</a>
                <a href="{{ route('documentos.index', $periodosImportados->isNotEmpty() ? ['periodo' => $periodosImportados->last()] : []) }}" class="btn btn--primario">Ver facturas importadas</a>
            </div>
        </div>
    @endif
@endsection
