<?php

use App\Models\Cliente;
use App\Models\Documento;
use App\Models\TipoDte;

it('rechaza marcar crédito fiscal en un documento FPEQ', function () {
    $fpeq = TipoDte::factory()->pequenoContribuyente()->create();
    $cliente = Cliente::factory()->create();

    $documento = Documento::factory()->create([
        'tipo_dte_id' => $fpeq->id,
        'cliente_id' => $cliente->id,
        'direccion' => 'recibida',
        'genera_credito' => false,
    ]);

    $respuesta = $this->postJson(route('documentos.actualizarCredito', $documento), [
        'genera_credito' => true,
    ]);

    $respuesta->assertStatus(422);
    expect($documento->fresh()->genera_credito)->toBeFalse();
});

it('permite marcar crédito fiscal en un documento recibido que no es FPEQ', function () {
    $factura = TipoDte::factory()->factura()->create();
    $cliente = Cliente::factory()->create();

    $documento = Documento::factory()->create([
        'tipo_dte_id' => $factura->id,
        'cliente_id' => $cliente->id,
        'direccion' => 'recibida',
        'genera_credito' => false,
    ]);

    $respuesta = $this->postJson(route('documentos.actualizarCredito', $documento), [
        'genera_credito' => true,
    ]);

    $respuesta->assertOk();
    expect($documento->fresh()->genera_credito)->toBeTrue();
});

it('rechaza cambiar el tipo de un documento con IDP', function () {
    $factura = TipoDte::factory()->factura()->create();
    $cliente = Cliente::factory()->create();

    $documento = Documento::factory()->create([
        'tipo_dte_id' => $factura->id,
        'cliente_id' => $cliente->id,
        'tipo' => 'combustible',
        'idp' => '150.00',
    ]);

    $respuesta = $this->postJson(route('documentos.actualizarTipo', $documento), [
        'tipo' => 'bien',
    ]);

    $respuesta->assertStatus(422);
    expect($documento->fresh()->tipo->value)->toBe('combustible');
});

it('permite cambiar el tipo de un documento sin IDP', function () {
    $factura = TipoDte::factory()->factura()->create();
    $cliente = Cliente::factory()->create();

    $documento = Documento::factory()->create([
        'tipo_dte_id' => $factura->id,
        'cliente_id' => $cliente->id,
        'tipo' => 'bien',
        'idp' => '0.00',
    ]);

    $respuesta = $this->postJson(route('documentos.actualizarTipo', $documento), [
        'tipo' => 'servicio',
    ]);

    $respuesta->assertOk();
    expect($documento->fresh()->tipo->value)->toBe('servicio');
});

it('rechaza marcar crédito fiscal en un documento FCAP', function () {
    $fcap = TipoDte::factory()->cambiariaPequenoContribuyente()->create();
    $cliente = Cliente::factory()->create();

    $documento = Documento::factory()->create([
        'tipo_dte_id' => $fcap->id,
        'cliente_id' => $cliente->id,
        'direccion' => 'recibida',
        'genera_credito' => false,
    ]);

    $respuesta = $this->postJson(route('documentos.actualizarCredito', $documento), [
        'genera_credito' => true,
    ]);

    $respuesta->assertStatus(422);
    expect($documento->fresh()->genera_credito)->toBeFalse();
});
