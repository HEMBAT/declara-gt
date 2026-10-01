<?php

use App\Models\Documento;
use App\Models\TipoDte;

it('el scope paraCalculoIsr solo incluye documentos emitidos, vigentes, no anulados y en GTQ del período', function () {
    Documento::factory()->create(['periodo' => '2026-05', 'direccion' => 'emitida', 'estado' => 'Vigente', 'anulado' => false, 'moneda' => 'GTQ']);
    Documento::factory()->create(['periodo' => '2026-05', 'direccion' => 'recibida', 'estado' => 'Vigente', 'anulado' => false, 'moneda' => 'GTQ']);
    Documento::factory()->create(['periodo' => '2026-05', 'direccion' => 'emitida', 'estado' => 'Anulado', 'anulado' => false, 'moneda' => 'GTQ']);
    Documento::factory()->create(['periodo' => '2026-05', 'direccion' => 'emitida', 'estado' => 'Vigente', 'anulado' => true, 'moneda' => 'GTQ']);
    Documento::factory()->create(['periodo' => '2026-05', 'direccion' => 'emitida', 'estado' => 'Vigente', 'anulado' => false, 'moneda' => 'USD']);
    Documento::factory()->create(['periodo' => '2026-06', 'direccion' => 'emitida', 'estado' => 'Vigente', 'anulado' => false, 'moneda' => 'GTQ']);

    $resultado = Documento::query()->paraCalculoIsr('2026-05')->get();

    expect($resultado)->toHaveCount(1);
});

it('ningún scope de cálculo incluye las notas de abono', function () {
    $notaDeAbono = TipoDte::factory()->notaDeAbono()->create();

    Documento::factory()->create(['tipo_dte_id' => $notaDeAbono->id, 'periodo' => '2026-05', 'direccion' => 'emitida']);
    Documento::factory()->create(['tipo_dte_id' => $notaDeAbono->id, 'periodo' => '2026-05', 'direccion' => 'recibida', 'genera_credito' => true]);

    expect(Documento::query()->paraCalculoIsr('2026-05')->count())->toBe(0)
        ->and(Documento::query()->paraDebitoIva('2026-05')->count())->toBe(0)
        ->and(Documento::query()->paraCreditoIva('2026-05')->count())->toBe(0)
        ->and(Documento::query()->paraRecibidasIva('2026-05')->count())->toBe(0);
});

it('excluye las FCAP del crédito fiscal pero las deja en las recibidas del SAT-2237', function () {
    $fcap = TipoDte::factory()->cambiariaPequenoContribuyente()->create();

    Documento::factory()->create(['tipo_dte_id' => $fcap->id, 'periodo' => '2026-05', 'direccion' => 'recibida', 'genera_credito' => true]);

    expect(Documento::query()->paraCreditoIva('2026-05')->count())->toBe(0)
        ->and(Documento::query()->paraRecibidasIva('2026-05')->count())->toBe(1);
});
