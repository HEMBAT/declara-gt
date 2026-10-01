<?php

use App\Http\Controllers\ClienteController;
use App\Http\Controllers\ConfiguracionController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DocumentoController;
use App\Http\Controllers\FormularioController;
use App\Http\Controllers\ImportacionController;
use App\Http\Controllers\ParametroImpuestoController;
use App\Http\Controllers\RetencionController;
use App\Http\Controllers\RetencionIvaController;
use Illuminate\Support\Facades\Route;

Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
Route::post('/periodos/{periodo}/calculado', [DashboardController::class, 'marcarCalculado'])
    ->where('periodo', '\d{4}-\d{2}')
    ->name('dashboard.calcular');
Route::post('/periodos/{periodo}/declarado', [DashboardController::class, 'marcarDeclarado'])
    ->where('periodo', '\d{4}-\d{2}')
    ->name('dashboard.declarar');
Route::post('/periodos/{periodo}/remanente-iva', [DashboardController::class, 'guardarRemanenteSat'])
    ->where('periodo', '\d{4}-\d{2}')
    ->name('dashboard.remanenteIva');

Route::get('/configuracion', [ConfiguracionController::class, 'editar'])->name('configuracion.editar');
Route::post('/configuracion', [ConfiguracionController::class, 'guardar'])->name('configuracion.guardar');
Route::post('/configuracion/reiniciar', [ConfiguracionController::class, 'reiniciar'])->name('configuracion.reiniciar');
Route::get('/configuracion/respaldo', [ConfiguracionController::class, 'respaldo'])->name('configuracion.respaldo');

Route::get('/importar', [ImportacionController::class, 'formulario'])->name('importar.index');
Route::post('/importar', [ImportacionController::class, 'procesar'])->name('importar.procesar');
Route::post('/importar/continuar', [ImportacionController::class, 'continuar'])->name('importar.continuar');
Route::get('/importar/clasificar', [ImportacionController::class, 'clasificar'])->name('importar.clasificar');
Route::post('/importar/clasificar', [ImportacionController::class, 'guardarClasificacion'])->name('importar.guardarClasificacion');
Route::post('/importar/cancelar', [ImportacionController::class, 'cancelar'])->name('importar.cancelar');

Route::get('/documentos', [DocumentoController::class, 'index'])->name('documentos.index');
Route::post('/documentos/{documento}/tipo', [DocumentoController::class, 'actualizarTipo'])->name('documentos.actualizarTipo');
Route::post('/documentos/{documento}/credito', [DocumentoController::class, 'actualizarCredito'])->name('documentos.actualizarCredito');

Route::get('/clientes', [ClienteController::class, 'index'])->name('clientes.index');
Route::post('/clientes', [ClienteController::class, 'guardar'])->name('clientes.guardar');
Route::put('/clientes/{cliente}', [ClienteController::class, 'actualizar'])->name('clientes.actualizar');

Route::get('/retenciones', [RetencionController::class, 'index'])->name('retenciones.index');
Route::post('/retenciones', [RetencionController::class, 'guardar'])->name('retenciones.guardar');
Route::delete('/retenciones/{retencion}', [RetencionController::class, 'eliminar'])->name('retenciones.eliminar');
Route::post('/retenciones-iva', [RetencionIvaController::class, 'guardar'])->name('retencionesIva.guardar');
Route::delete('/retenciones-iva/{retencionIva}', [RetencionIvaController::class, 'eliminar'])->name('retencionesIva.eliminar');
Route::post('/periodos/{periodo}/retenciones-iva', [RetencionIvaController::class, 'guardarSaldo'])
    ->where('periodo', '\d{4}-\d{2}')
    ->name('retencionesIva.saldo');

Route::get('/parametros', [ParametroImpuestoController::class, 'index'])->name('parametros.index');
Route::post('/parametros/iva', [ParametroImpuestoController::class, 'nuevaVigenciaIva'])->name('parametros.iva');
Route::post('/parametros/isr', [ParametroImpuestoController::class, 'nuevaVigenciaIsr'])->name('parametros.isr');

Route::get('/formulario', [FormularioController::class, 'index'])->name('formulario.index');
