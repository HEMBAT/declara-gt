@extends('layouts.app')

@section('titulo', 'Configuración — declara·gt')

@section('contenido')
    <div class="encabezado-pantalla">
        <div class="encabezado-pantalla__titulo">Configuración</div>
        <div class="encabezado-pantalla__subtitulo">El NIT de tu empresa se usa para detectar si cada documento importado fue emitido o recibido.</div>
    </div>

    <div class="panel" style="max-width:480px;">
        <form method="POST" action="{{ route('configuracion.guardar') }}">
            @csrf

            <div class="campo">
                <label class="campo__etiqueta" for="nit">NIT del contribuyente</label>
                <input type="text" id="nit" name="nit" class="mono" value="{{ old('nit', $contribuyente->nit ?? '') }}" @if($nitBloqueado) readonly @endif required>
                @if($nitBloqueado)
                    <div class="campo__ayuda">El NIT no se puede cambiar porque ya hay documentos importados. Usa "Reiniciar datos" para empezar con otro contribuyente.</div>
                @endif
                @error('nit') <div class="error-campo">{{ $message }}</div> @enderror
            </div>

            <div class="campo">
                <label class="campo__etiqueta" for="nombre">Nombre o razón social</label>
                <input type="text" id="nombre" name="nombre" value="{{ old('nombre', $contribuyente->nombre ?? '') }}" required>
                @error('nombre') <div class="error-campo">{{ $message }}</div> @enderror
            </div>

            <button type="submit" class="btn btn--primario">Guardar</button>
        </form>
    </div>

    @if($contribuyente)
        <div class="panel panel--peligro" style="max-width:480px; margin-top:24px;">
            <div style="font-size:14.5px; font-weight:700; margin-bottom:6px;">Zona de peligro</div>
            <p style="font-size:13px; color:rgba(10,10,10,0.6); margin-top:0;">Estas acciones no se pueden deshacer.</p>

            <div class="panel__acciones" style="justify-content:flex-start; margin-top:14px;">
                <a href="{{ route('configuracion.respaldo') }}" class="btn btn--contorno">Descargar respaldo</a>
            </div>

            <hr style="border:none; border-top:1px solid rgba(10,10,10,0.08); margin:20px 0;">

            <form method="POST" action="{{ route('configuracion.reiniciar') }}" data-form-reiniciar data-nit-actual="{{ $contribuyente->nit }}">
                @csrf
                <div class="alerta-flash" style="margin-bottom:14px;">¿Ya descargaste tu respaldo? Esta acción es irreversible y borra todos los datos del contribuyente actual.</div>
                <div class="campo">
                    <label class="campo__etiqueta" for="confirmar_nit">Escribe el NIT actual ({{ $contribuyente->nit }}) para confirmar</label>
                    <input type="text" id="confirmar_nit" name="confirmar_nit" class="mono" data-confirmar-nit autocomplete="off">
                    @error('confirmar_nit') <div class="error-campo">{{ $message }}</div> @enderror
                </div>
                <button type="submit" class="btn btn--oscuro" data-btn-reiniciar disabled>Reiniciar datos</button>
            </form>
        </div>
    @endif
@endsection
