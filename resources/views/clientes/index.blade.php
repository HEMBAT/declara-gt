@extends('layouts.app')

@section('titulo', 'Clientes — declara·gt')

@section('contenido')
    <div class="encabezado-pantalla">
        <div class="encabezado-pantalla__titulo">Clientes</div>
        <div class="encabezado-pantalla__subtitulo">NIT, nombre y tipo por defecto. Se usan al clasificar facturas nuevas durante la importación.</div>
    </div>

    <div class="panel" style="margin-bottom:20px;">
        <form method="POST" action="{{ route('clientes.guardar') }}" style="display:flex; gap:10px; flex-wrap:wrap; align-items:flex-end;">
            @csrf
            <div class="campo" style="margin-bottom:0;">
                <label class="campo__etiqueta" for="nuevo-nit">NIT</label>
                <input type="text" id="nuevo-nit" name="nit" class="mono" value="{{ old('nit') }}" required>
            </div>
            <div class="campo" style="margin-bottom:0;">
                <label class="campo__etiqueta" for="nuevo-nombre">Nombre</label>
                <input type="text" id="nuevo-nombre" name="nombre" value="{{ old('nombre') }}" required>
            </div>
            <div class="campo" style="margin-bottom:0;">
                <label class="campo__etiqueta" for="nuevo-tipo">Tipo por defecto</label>
                <select id="nuevo-tipo" name="tipo_default">
                    <option value="bien">Bien</option>
                    <option value="servicio">Servicio</option>
                    <option value="combustible">Combustible</option>
                </select>
            </div>
            <button type="submit" class="btn btn--primario">+ Agregar cliente</button>
        </form>
        @error('nit') <div class="error-campo">{{ $message }}</div> @enderror
    </div>

    @if($clientes->isNotEmpty())
        <div class="tabla-envoltura">
            <div class="tabla-scroll">
                <table class="tabla">
                    <thead>
                        <tr><th style="width:140px;">NIT</th><th>Nombre</th><th style="width:220px;">Tipo por defecto</th></tr>
                    </thead>
                    <tbody>
                        @foreach($clientes as $cliente)
                            <tr>
                                <td class="mono" style="color:rgba(10,10,10,0.7);">{{ $cliente->nit }}</td>
                                <td colspan="2">
                                    <form method="POST" action="{{ route('clientes.actualizar', $cliente) }}" style="display:flex; gap:8px; align-items:center;">
                                        @csrf
                                        @method('PUT')
                                        <input type="text" name="nombre" value="{{ $cliente->nombre }}" style="flex:1; border:1px solid transparent; background:transparent; padding:6px 8px; border-radius:6px;">
                                        <select name="tipo_default" style="border:1px solid rgba(10,10,10,0.12); border-radius:6px; padding:6px 8px; font-size:12.5px;">
                                            <option value="bien" @selected($cliente->tipo_default->value === 'bien')>Bien</option>
                                            <option value="servicio" @selected($cliente->tipo_default->value === 'servicio')>Servicio</option>
                                            <option value="combustible" @selected($cliente->tipo_default->value === 'combustible')>Combustible</option>
                                        </select>
                                        <button type="submit" class="btn btn--fantasma" style="padding:6px 12px; font-size:12px;">Guardar</button>
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @else
        <div class="estado-vacio">Aún no tienes clientes registrados. Se agregan automáticamente al importar facturas nuevas.</div>
    @endif
@endsection
