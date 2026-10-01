{{--
    Conteo de documentos del período, emitidos vs recibidos.

    Se muestra igual en el dashboard y en la pantalla de formulario, así que
    vive aquí para no duplicarlo. Espera $conteoDocumentos tal como lo
    devuelve ConteoDocumentosPeriodoService.
--}}
<div class="panel" style="margin-bottom:18px;">
    <div style="font-size:14.5px; font-weight:700; margin-bottom:4px;">Cantidad de documentos del período</div>
    <div class="encabezado-pantalla__subtitulo" style="margin-bottom:14px;">Cuenta todo lo del período, sin excluir anulados ni facturas de Pequeño Contribuyente.</div>
    <div class="tarjetas" style="margin-bottom:0;">
        @foreach(['emitida' => 'Emitidos', 'recibida' => 'Recibidos'] as $direccion => $etiqueta)
            <div class="tarjeta">
                <div class="tarjeta__etiqueta">{{ $etiqueta }}</div>
                <div class="tarjeta__valor mono">{{ $conteoDocumentos[$direccion]['total'] }}</div>
                @if($conteoDocumentos[$direccion]['anulados'] > 0)
                    <div class="tarjeta__nota">{{ $conteoDocumentos[$direccion]['anulados'] }} {{ $conteoDocumentos[$direccion]['anulados'] === 1 ? 'anulado' : 'anulados' }} — no entran al cálculo</div>
                @endif
                @if($conteoDocumentos[$direccion]['porTipo'] !== [])
                    <div style="margin-top:10px; padding-top:8px; border-top:1px solid rgba(10,10,10,0.06);">
                        @foreach($conteoDocumentos[$direccion]['porTipo'] as $codigo => $cantidad)
                            <div class="tarjeta__fila"><span>{{ $codigo }}</span><span class="mono">{{ $cantidad }}</span></div>
                        @endforeach
                    </div>
                @else
                    <div class="tarjeta__nota">Ningún documento este período.</div>
                @endif
            </div>
        @endforeach
    </div>
</div>
