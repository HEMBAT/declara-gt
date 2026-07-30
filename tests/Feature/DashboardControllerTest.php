<?php

use App\Models\Documento;
use App\Models\ParametroImpuesto;
use App\Models\TipoDte;

it('muestra los subtotales de bienes/servicios y del IVA general bajo las tarjetas del período', function () {
    ParametroImpuesto::factory()->create([
        'tipo' => 'ISR_TRAMO', 'tasa' => '0.0500', 'limite_inferior' => '0.00', 'limite_superior' => '30000.00', 'vigente_desde' => '2013-01-01',
    ]);
    ParametroImpuesto::factory()->create([
        'tipo' => 'ISR_TRAMO', 'tasa' => '0.0700', 'limite_inferior' => '30000.01', 'limite_superior' => null, 'vigente_desde' => '2013-01-01',
    ]);

    $factura = TipoDte::factory()->factura()->create();

    Documento::factory()->create([
        'tipo_dte_id' => $factura->id,
        'periodo' => '2026-05',
        'direccion' => 'emitida',
        'tipo' => 'servicio',
        'iva' => '240.00',
        'base_sin_iva' => '2000.00',
    ]);

    Documento::factory()->create([
        'tipo_dte_id' => $factura->id,
        'periodo' => '2026-05',
        'direccion' => 'recibida',
        'tipo' => 'combustible',
        'iva' => '60.00',
        'idp' => '150.00',
        'genera_credito' => true,
    ]);

    $respuesta = $this->get(route('dashboard', ['periodo' => '2026-05']));

    $respuesta->assertOk();
    $respuesta->assertSee('IVA General — SAT-2237', false);
    $respuesta->assertSee('Ver formulario completo', false);
    $respuesta->assertSee('Q240.00', false); // servicios gravados (débito)
    $respuesta->assertSee('Q60.00', false); // combustibles (crédito)
});
