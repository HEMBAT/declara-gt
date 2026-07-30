<?php

use App\Models\Documento;
use App\Models\ParametroImpuesto;
use App\Models\TipoDte;
use App\Servicios\CalculoIsrPeriodoService;

function sembrarTramosIsr(string $vigenteDesde, string $tramo1Tasa, string $tramo2Tasa, ?string $vigenteHasta = null): void
{
    ParametroImpuesto::factory()->create([
        'tipo' => 'ISR_TRAMO',
        'tasa' => $tramo1Tasa,
        'limite_inferior' => '0.00',
        'limite_superior' => '30000.00',
        'vigente_desde' => $vigenteDesde,
        'vigente_hasta' => $vigenteHasta,
    ]);

    ParametroImpuesto::factory()->create([
        'tipo' => 'ISR_TRAMO',
        'tasa' => $tramo2Tasa,
        'limite_inferior' => '30000.01',
        'limite_superior' => null,
        'vigente_desde' => $vigenteDesde,
        'vigente_hasta' => $vigenteHasta,
    ]);
}

it('selecciona la tasa de ISR vigente en la fecha del período', function () {
    sembrarTramosIsr(vigenteDesde: '2013-01-01', tramo1Tasa: '0.0500', tramo2Tasa: '0.0700', vigenteHasta: '2020-12-31');
    sembrarTramosIsr(vigenteDesde: '2021-01-01', tramo1Tasa: '0.1000', tramo2Tasa: '0.1500');

    $facturaFicticia = TipoDte::factory()->factura()->create();

    Documento::factory()->create([
        'tipo_dte_id' => $facturaFicticia->id,
        'periodo' => '2019-06',
        'fecha_emision' => '2019-06-15',
        'base_sin_iva' => '10000.00',
    ]);

    Documento::factory()->create([
        'tipo_dte_id' => $facturaFicticia->id,
        'periodo' => '2022-01',
        'fecha_emision' => '2022-01-15',
        'base_sin_iva' => '10000.00',
    ]);

    $servicio = new CalculoIsrPeriodoService;

    expect($servicio->calcularPeriodo('2019-06')->isrDeterminado)->toBe('500.00')
        ->and($servicio->calcularPeriodo('2022-01')->isrDeterminado)->toBe('1000.00');
});

it('excluye del cálculo los documentos recibidos', function () {
    sembrarTramosIsr(vigenteDesde: '2013-01-01', tramo1Tasa: '0.0500', tramo2Tasa: '0.0700');
    $facturaFicticia = TipoDte::factory()->factura()->create();

    Documento::factory()->create([
        'tipo_dte_id' => $facturaFicticia->id,
        'periodo' => '2026-05',
        'fecha_emision' => '2026-05-10',
        'direccion' => 'emitida',
        'base_sin_iva' => '10000.00',
    ]);

    Documento::factory()->create([
        'tipo_dte_id' => $facturaFicticia->id,
        'periodo' => '2026-05',
        'fecha_emision' => '2026-05-11',
        'direccion' => 'recibida',
        'base_sin_iva' => '50000.00',
    ]);

    $resultado = (new CalculoIsrPeriodoService)->calcularPeriodo('2026-05');

    expect($resultado->baseGravable)->toBe('10000.00');
});

it('excluye del cálculo los documentos anulados y los de moneda distinta a GTQ', function () {
    sembrarTramosIsr(vigenteDesde: '2013-01-01', tramo1Tasa: '0.0500', tramo2Tasa: '0.0700');
    $facturaFicticia = TipoDte::factory()->factura()->create();

    Documento::factory()->create([
        'tipo_dte_id' => $facturaFicticia->id,
        'periodo' => '2026-05',
        'fecha_emision' => '2026-05-10',
        'base_sin_iva' => '10000.00',
    ]);

    Documento::factory()->create([
        'tipo_dte_id' => $facturaFicticia->id,
        'periodo' => '2026-05',
        'fecha_emision' => '2026-05-11',
        'base_sin_iva' => '5000.00',
        'anulado' => true,
    ]);

    Documento::factory()->create([
        'tipo_dte_id' => $facturaFicticia->id,
        'periodo' => '2026-05',
        'fecha_emision' => '2026-05-12',
        'base_sin_iva' => '8000.00',
        'moneda' => 'USD',
    ]);

    $resultado = (new CalculoIsrPeriodoService)->calcularPeriodo('2026-05');

    expect($resultado->baseGravable)->toBe('10000.00');
});

it('retorna todo en cero cuando el período no tiene documentos', function () {
    sembrarTramosIsr(vigenteDesde: '2013-01-01', tramo1Tasa: '0.0500', tramo2Tasa: '0.0700');

    $resultado = (new CalculoIsrPeriodoService)->calcularPeriodo('2030-01');

    expect($resultado->baseGravable)->toBe('0.00')
        ->and($resultado->isrAPagar)->toBe('0.00');
});
