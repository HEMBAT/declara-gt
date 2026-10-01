<?php

use App\Models\Declaracion;
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

it('excluye siempre las FCAP del crédito fiscal aunque genera_credito sea true', function () {
    $fcap = TipoDte::factory()->cambiariaPequenoContribuyente()->create();

    Documento::factory()->create([
        'tipo_dte_id' => $fcap->id,
        'periodo' => '2026-05',
        'direccion' => 'recibida',
        'iva' => '500.00',
        'genera_credito' => true,
    ]);

    $resultado = (new CalculoIvaPeriodoService)->calcularPeriodo('2026-05');

    expect($resultado->credito)->toBe('0.00');
});

it('usa el remanente según Declaraguate en vez del encadenado cuando está registrado', function () {
    $factura = TipoDte::factory()->factura()->create();

    Declaracion::factory()->create(['tipo' => 'IVA', 'periodo' => '2026-06', 'remanente_credito' => '627.06']);
    Declaracion::factory()->create(['tipo' => 'IVA', 'periodo' => '2026-07', 'remanente_anterior_sat' => '1858.00']);

    Documento::factory()->create([
        'tipo_dte_id' => $factura->id,
        'periodo' => '2026-07',
        'direccion' => 'emitida',
        'iva' => '642.86',
    ]);

    $resultado = (new CalculoIvaPeriodoService)->calcularPeriodo('2026-07');

    expect($resultado->remanenteAnterior)->toBe('1858.00')
        ->and($resultado->remanenteCredito)->toBe('1215.14')
        // El recálculo nunca pisa lo que el usuario copió de Declaraguate.
        ->and(Declaracion::where('periodo', '2026-07')->first()->remanente_anterior_sat)->toBe('1858.00');
});

it('recalcularDesde propaga el remanente a los períodos posteriores ya calculados', function () {
    $factura = TipoDte::factory()->factura()->create();
    $servicio = new CalculoIvaPeriodoService;

    foreach (['2026-07', '2026-08'] as $periodo) {
        Documento::factory()->create([
            'tipo_dte_id' => $factura->id,
            'periodo' => $periodo,
            'direccion' => 'emitida',
            'iva' => '100.00',
        ]);
        $servicio->calcularPeriodo($periodo);
    }

    Declaracion::where('periodo', '2026-07')->update(['remanente_anterior_sat' => '1000.00']);
    $servicio->recalcularDesde('2026-07');

    // Julio: 1000 − 100 = 900; agosto arranca con esos 900: 900 − 100 = 800.
    expect(Declaracion::where('periodo', '2026-07')->first()->remanente_credito)->toBe('900.00')
        ->and(Declaracion::where('periodo', '2026-08')->first()->remanente_credito)->toBe('800.00');
});
