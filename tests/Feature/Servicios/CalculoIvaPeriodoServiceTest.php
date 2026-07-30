<?php

use App\Models\Documento;
use App\Models\TipoDte;
use App\Servicios\CalculoIvaPeriodoService;

it('excluye del crédito fiscal los documentos recibidos marcados genera_credito=false', function () {
    $factura = TipoDte::factory()->factura()->create();

    Documento::factory()->create([
        'tipo_dte_id' => $factura->id,
        'periodo' => '2026-05',
        'direccion' => 'emitida',
        'iva' => '100.00',
    ]);

    Documento::factory()->create([
        'tipo_dte_id' => $factura->id,
        'periodo' => '2026-05',
        'direccion' => 'recibida',
        'iva' => '40.00',
        'genera_credito' => true,
    ]);

    Documento::factory()->create([
        'tipo_dte_id' => $factura->id,
        'periodo' => '2026-05',
        'direccion' => 'recibida',
        'iva' => '999.00',
        'genera_credito' => false,
    ]);

    $resultado = (new CalculoIvaPeriodoService)->calcularPeriodo('2026-05');

    expect($resultado->debito)->toBe('100.00')
        ->and($resultado->credito)->toBe('40.00');
});

it('excluye siempre las FPEQ del crédito fiscal aunque genera_credito sea true', function () {
    $fpeq = TipoDte::factory()->pequenoContribuyente()->create();

    Documento::factory()->create([
        'tipo_dte_id' => $fpeq->id,
        'periodo' => '2026-05',
        'direccion' => 'recibida',
        'iva' => '500.00',
        'genera_credito' => true, // aunque quede marcado true, la FPEQ nunca cuenta
    ]);

    $resultado = (new CalculoIvaPeriodoService)->calcularPeriodo('2026-05');

    expect($resultado->credito)->toBe('0.00');
});

it('incluye en el crédito fiscal un documento recibido de combustible con IDP', function () {
    $factura = TipoDte::factory()->factura()->create();

    Documento::factory()->create([
        'tipo_dte_id' => $factura->id,
        'periodo' => '2026-05',
        'direccion' => 'recibida',
        'tipo' => 'combustible',
        'gran_total' => '1120.00',
        'iva' => '60.00',
        'idp' => '150.00',
        'base_sin_iva' => '910.00',
        'genera_credito' => true,
    ]);

    $resultado = (new CalculoIvaPeriodoService)->calcularPeriodo('2026-05');

    expect($resultado->credito)->toBe('60.00');
});

it('el remanente de crédito de un período se arrastra al período siguiente', function () {
    $factura = TipoDte::factory()->factura()->create();

    // 2026-05: crédito supera al débito, se genera un remanente.
    Documento::factory()->create([
        'tipo_dte_id' => $factura->id,
        'periodo' => '2026-05',
        'direccion' => 'emitida',
        'iva' => '400.00',
    ]);

    Documento::factory()->create([
        'tipo_dte_id' => $factura->id,
        'periodo' => '2026-05',
        'direccion' => 'recibida',
        'iva' => '1000.00',
        'genera_credito' => true,
    ]);

    $resultadoMayo = (new CalculoIvaPeriodoService)->calcularPeriodo('2026-05');
    expect($resultadoMayo->ivaAPagar)->toBe('0.00')
        ->and($resultadoMayo->remanenteCredito)->toBe('600.00');

    // 2026-06: sin el remanente, el débito de 1000 pagaría 1000; con el
    // remanente de 600 arrastrado desde mayo, solo debe pagar 400.
    Documento::factory()->create([
        'tipo_dte_id' => $factura->id,
        'periodo' => '2026-06',
        'direccion' => 'emitida',
        'iva' => '1000.00',
    ]);

    $resultadoJunio = (new CalculoIvaPeriodoService)->calcularPeriodo('2026-06');

    expect($resultadoJunio->remanenteAnterior)->toBe('600.00')
        ->and($resultadoJunio->ivaAPagar)->toBe('400.00')
        ->and($resultadoJunio->remanenteCredito)->toBe('0.00');
});

it('retorna todo en cero cuando el período no tiene documentos ni remanente previo', function () {
    $resultado = (new CalculoIvaPeriodoService)->calcularPeriodo('2030-01');

    expect($resultado->debito)->toBe('0.00')
        ->and($resultado->credito)->toBe('0.00')
        ->and($resultado->ivaAPagar)->toBe('0.00');
});
