<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('titulo', 'declara·gt')</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>
<div class="pagina">

    <div class="banner-privacidad" id="banner-privacidad" style="display:none;">
        <span class="banner-privacidad__punto"></span>
        <span>Tus datos se guardan solo en este equipo — nunca se suben a un servidor.</span>
        <button type="button" class="banner-privacidad__cerrar" id="cerrar-banner-privacidad">&times;</button>
    </div>

    <div class="topbar">
        <a href="{{ route('dashboard') }}" class="topbar__marca">
            <div class="topbar__icono"><span></span></div>
            <div class="topbar__nombre">declara<span>·gt</span></div>
        </a>

        <nav class="topbar__nav">
            <a href="{{ route('dashboard') }}" class="topbar__nav-item @if(request()->routeIs('dashboard')) activo @endif">Dashboard</a>
            <a href="{{ route('importar.index') }}" class="topbar__nav-item @if(request()->routeIs('importar.*')) activo @endif">Importación</a>
            <a href="{{ route('documentos.index') }}" class="topbar__nav-item @if(request()->routeIs('documentos.*')) activo @endif">Documentos</a>
            <a href="{{ route('clientes.index') }}" class="topbar__nav-item @if(request()->routeIs('clientes.*')) activo @endif">Clientes</a>
            <a href="{{ route('retenciones.index') }}" class="topbar__nav-item @if(request()->routeIs('retenciones.*')) activo @endif">Retenciones</a>
            <a href="{{ route('formulario.index') }}" class="topbar__nav-item @if(request()->routeIs('formulario.*')) activo @endif">Formulario</a>
            <a href="{{ route('parametros.index') }}" class="topbar__nav-item @if(request()->routeIs('parametros.*')) activo @endif">Parámetros</a>
            <a href="{{ route('configuracion.editar') }}" class="topbar__nav-item @if(request()->routeIs('configuracion.*')) activo @endif">Configuración</a>
        </nav>

        @if(!empty($periodoActual))
            <div class="topbar__estado">
                <span class="topbar__estado-punto"></span>
                {{ \App\Support\Formato::periodoLabel($periodoActual) }}
            </div>
        @endif
    </div>

    <div class="contenido">
        @if(session('exito'))
            <div class="exito-flash">{{ session('exito') }}</div>
        @endif
        @if(session('error'))
            <div class="alerta-flash">{{ session('error') }}</div>
        @endif

        @yield('contenido')
    </div>

    <div class="pie">
        <div class="pie__disclaimer">Herramienta de apoyo — no constituye asesoría fiscal, verifica con tu contador.</div>
        <div class="pie__hembat">
            Un proyecto libre de <a href="https://hembat.com" target="_blank" rel="noopener">HEMBAT</a>, hecho con ♥ ·
            <a href="https://github.com/HEMBAT/declara-gt" target="_blank" rel="noopener" class="secundario">código en GitHub</a>
        </div>
    </div>

</div>
</body>
</html>
