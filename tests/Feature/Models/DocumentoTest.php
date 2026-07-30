<?php

use App\Models\Documento;

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
