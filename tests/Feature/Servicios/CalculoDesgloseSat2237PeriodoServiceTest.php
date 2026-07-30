<?php

use App\Models\Declaracion;
use App\Models\Documento;
use App\Models\TipoDte;
use App\Servicios\CalculoDesgloseSat2237PeriodoService;

it('separa el débito del período en ventas gravadas (bien) y servicios gravados', function () {
    $factura = TipoDte::factory()->factura()->create();

    Documento::factory()->create([
        'tipo_dte_id' => $factura->id,
        'periodo' => '2026-05',
        'direccion' => 'emitida',
        'tipo' => 'bien',
        'iva' => '480.00',
        'base_sin_iva' => '4000.00',
    ]);

    Documento::factory()->create([
        'tipo_dte_id' => $factura->id,
        'periodo' => '2026-05',
        'direccion' => 'emitida',
        'tipo' => 'servicio',
        'iva' => '240.00',
        'base_sin_iva' => '2000.00',
    ]);

    $resultado = (new CalculoDesgloseSat2237PeriodoService)->calcularPeriodo('2026-05');

    expect($resultado->ventasGravadasBien)->toBe('480.00')
        ->and($resultado->serviciosGravados)->toBe('240.00')
        ->and($resultado->debitoTotal)->toBe('720.00')
        ->and($resultado->ingresosBienes)->toBe('4000.00')
        ->and($resultado->ingresosServicios)->toBe('2000.00');
});

it('separa el crédito del período en combustibles, otras compras y servicios adquiridos', function () {
    $factura = TipoDte::factory()->factura()->create();

    Documento::factory()->create([
        'tipo_dte_id' => $factura->id,
        'periodo' => '2026-05',
        'direccion' => 'recibida',
        'tipo' => 'combustible',
        'iva' => '60.00',
        'idp' => '150.00',
        'genera_credito' => true,
    ]);

    Documento::factory()->create([
        'tipo_dte_id' => $factura->id,
        'periodo' => '2026-05',
        'direccion' => 'recibida',
        'tipo' => 'bien',
        'iva' => '120.00',
        'genera_credito' => true,
    ]);

    Documento::factory()->create([
        'tipo_dte_id' => $factura->id,
        'periodo' => '2026-05',
        'direccion' => 'recibida',
        'tipo' => 'servicio',
        'iva' => '240.00',
        'genera_credito' => true,
    ]);

    $resultado = (new CalculoDesgloseSat2237PeriodoService)->calcularPeriodo('2026-05');

    expect($resultado->creditoCombustibles)->toBe('60.00')
        ->and($resultado->creditoOtrasCompras)->toBe('120.00')
        ->and($resultado->creditoServiciosAdquiridos)->toBe('240.00')
        ->and($resultado->creditoTotal)->toBe('420.00');
});

it('incluye en la base no deducible las compras que no generan crédito, incluidas las FPEQ', function () {
    $fpeq = TipoDte::factory()->pequenoContribuyente()->create();
    $factura = TipoDte::factory()->factura()->create();

    Documento::factory()->create([
        'tipo_dte_id' => $fpeq->id,
        'periodo' => '2026-05',
        'direccion' => 'recibida',
        'tipo' => 'servicio',
        'iva' => '0.00',
        'base_sin_iva' => '350.00',
        'genera_credito' => false,
    ]);

    Documento::factory()->create([
        'tipo_dte_id' => $factura->id,
        'periodo' => '2026-05',
        'direccion' => 'recibida',
        'tipo' => 'bien',
        'iva' => '999.00',
        'base_sin_iva' => '1500.00',
        'genera_credito' => false,
    ]);

    $resultado = (new CalculoDesgloseSat2237PeriodoService)->calcularPeriodo('2026-05');

    expect($resultado->baseNoDeducible)->toBe('1850.00')
        ->and($resultado->creditoTotal)->toBe('0.00');
});

it('lee el remanente anterior ya guardado en declaraciones sin volver a persistir nada', function () {
    Declaracion::factory()->create([
        'tipo' => 'IVA',
        'periodo' => '2026-04',
        'remanente_credito' => '600.00',
    ]);

    $resultado = (new CalculoDesgloseSat2237PeriodoService)->calcularPeriodo('2026-05');

    expect($resultado->remanenteAnterior)->toBe('600.00')
        ->and(Declaracion::where('periodo', '2026-05')->exists())->toBeFalse();
});

it('retorna todo en cero cuando el período no tiene documentos', function () {
    $resultado = (new CalculoDesgloseSat2237PeriodoService)->calcularPeriodo('2030-01');

    expect($resultado->debitoTotal)->toBe('0.00')
        ->and($resultado->creditoTotal)->toBe('0.00')
        ->and($resultado->baseNoDeducible)->toBe('0.00')
        ->and($resultado->remanenteAnterior)->toBe('0.00');
});
