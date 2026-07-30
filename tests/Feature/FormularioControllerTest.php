<?php

use App\Models\Documento;
use App\Models\TipoDte;

it('muestra el formulario con los subtotales por casilla del período', function () {
    $factura = TipoDte::factory()->factura()->create();

    Documento::factory()->create([
        'tipo_dte_id' => $factura->id,
        'periodo' => '2026-05',
        'direccion' => 'emitida',
        'tipo' => 'bien',
        'iva' => '480.00',
        'base_sin_iva' => '4000.00',
    ]);

    $respuesta = $this->get(route('formulario.index', ['periodo' => '2026-05']));

    $respuesta->assertOk();
    $respuesta->assertSee('Cuadro 3 — Débito fiscal', false);
    $respuesta->assertSee('Cuadro 5 — Crédito fiscal', false);
    $respuesta->assertSee('Q480.00', false);
});

it('muestra un estado vacío cuando no hay ningún período disponible', function () {
    $respuesta = $this->get(route('formulario.index'));

    $respuesta->assertOk();
    $respuesta->assertSee('Selecciona un período', false);
});
