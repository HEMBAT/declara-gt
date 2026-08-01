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

it('muestra la columna de base junto al crédito en cada casilla del cuadro 5', function () {
    $factura = TipoDte::factory()->factura()->create();
    $fpeq = TipoDte::factory()->pequenoContribuyente()->create();

    Documento::factory()->create([
        'tipo_dte_id' => $factura->id,
        'periodo' => '2026-05',
        'direccion' => 'recibida',
        'tipo' => 'servicio',
        'iva' => '851.16',
        'base_sin_iva' => '7093.00',
        'genera_credito' => true,
    ]);

    Documento::factory()->create([
        'tipo_dte_id' => $fpeq->id,
        'periodo' => '2026-05',
        'direccion' => 'recibida',
        'tipo' => 'bien',
        'iva' => '0.00',
        'base_sin_iva' => '425.00',
        'genera_credito' => false,
    ]);

    $respuesta = $this->get(route('formulario.index', ['periodo' => '2026-05']));

    $respuesta->assertOk();
    $respuesta->assertSee('Compras y servicios adquiridos de pequeños contribuyentes', false);
    $respuesta->assertSee('Compras que no generan derecho a compensación del crédito fiscal', false);
    $respuesta->assertSee('Q7,093.00', false); // base del servicio adquirido
    $respuesta->assertSee('Q851.16', false);   // su crédito, en la misma fila
    $respuesta->assertSee('Q425.00', false);   // base de la compra a Pequeño Contribuyente
});

it('muestra un estado vacío cuando no hay ningún período disponible', function () {
    $respuesta = $this->get(route('formulario.index'));

    $respuesta->assertOk();
    $respuesta->assertSee('Selecciona un período', false);
});
