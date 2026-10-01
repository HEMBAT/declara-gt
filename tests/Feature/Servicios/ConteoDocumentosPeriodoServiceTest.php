<?php

use App\Models\Documento;
use App\Models\TipoDte;
use App\Servicios\ConteoDocumentosPeriodoService;

it('cuenta los documentos del período por dirección, sin excluir anulados ni FPEQ', function () {
    $factura = TipoDte::factory()->factura()->create();
    $fpeq = TipoDte::factory()->pequenoContribuyente()->create();

    Documento::factory()->count(2)->create([
        'tipo_dte_id' => $factura->id,
        'periodo' => '2026-05',
        'direccion' => 'recibida',
    ]);

    Documento::factory()->create([
        'tipo_dte_id' => $factura->id,
        'periodo' => '2026-05',
        'direccion' => 'recibida',
        'anulado' => true,
        'estado' => 'Anulado',
    ]);

    Documento::factory()->create([
        'tipo_dte_id' => $fpeq->id,
        'periodo' => '2026-05',
        'direccion' => 'recibida',
    ]);

    Documento::factory()->create([
        'tipo_dte_id' => $factura->id,
        'periodo' => '2026-05',
        'direccion' => 'emitida',
    ]);

    $conteo = (new ConteoDocumentosPeriodoService)->contarPeriodo('2026-05');

    expect($conteo['recibida']['total'])->toBe(4) // 3 FACT (una anulada) + 1 FPEQ
        ->and($conteo['recibida']['porTipo'])->toBe(['FACT' => 3, 'FPEQ' => 1])
        ->and($conteo['recibida']['anulados'])->toBe(1)
        ->and($conteo['emitida']['total'])->toBe(1)
        ->and($conteo['emitida']['anulados'])->toBe(0)
        ->and($conteo['emitida']['porTipo'])->toBe(['FACT' => 1]);
});

it('no cuenta documentos de otros períodos', function () {
    $factura = TipoDte::factory()->factura()->create();

    Documento::factory()->create([
        'tipo_dte_id' => $factura->id,
        'periodo' => '2026-04',
        'direccion' => 'emitida',
    ]);

    $conteo = (new ConteoDocumentosPeriodoService)->contarPeriodo('2026-05');

    expect($conteo['emitida']['total'])->toBe(0)
        ->and($conteo['recibida']['total'])->toBe(0);
});

it('devuelve ambas direcciones en cero cuando el período no tiene documentos', function () {
    $conteo = (new ConteoDocumentosPeriodoService)->contarPeriodo('2030-01');

    expect($conteo)->toBe([
        'emitida' => ['total' => 0, 'anulados' => 0, 'porTipo' => []],
        'recibida' => ['total' => 0, 'anulados' => 0, 'porTipo' => []],
    ]);
});

it('cuenta los anulados de cada dirección sumando todos los tipos de DTE', function () {
    $factura = TipoDte::factory()->factura()->create();
    $notaCredito = TipoDte::factory()->notaDeCredito()->create();

    Documento::factory()->count(2)->create([
        'tipo_dte_id' => $factura->id,
        'periodo' => '2026-05',
        'direccion' => 'recibida',
        'anulado' => true,
        'estado' => 'Anulado',
    ]);

    Documento::factory()->create([
        'tipo_dte_id' => $notaCredito->id,
        'periodo' => '2026-05',
        'direccion' => 'recibida',
        'anulado' => true,
        'estado' => 'Anulado',
    ]);

    // Anulado de otro período: no debe contarse.
    Documento::factory()->create([
        'tipo_dte_id' => $factura->id,
        'periodo' => '2026-04',
        'direccion' => 'recibida',
        'anulado' => true,
        'estado' => 'Anulado',
    ]);

    $conteo = (new ConteoDocumentosPeriodoService)->contarPeriodo('2026-05');

    expect($conteo['recibida']['total'])->toBe(3)
        ->and($conteo['recibida']['anulados'])->toBe(3)
        ->and($conteo['emitida']['anulados'])->toBe(0);
});
