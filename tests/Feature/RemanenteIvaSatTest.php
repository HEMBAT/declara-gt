<?php

use App\Models\Declaracion;
use App\Models\Documento;
use App\Models\ParametroImpuesto;
use App\Models\TipoDte;

beforeEach(function () {
    ParametroImpuesto::factory()->create([
        'tipo' => 'ISR_TRAMO', 'tasa' => '0.0500', 'limite_inferior' => '0.00', 'limite_superior' => '30000.00', 'vigente_desde' => '2013-01-01',
    ]);
    ParametroImpuesto::factory()->create([
        'tipo' => 'ISR_TRAMO', 'tasa' => '0.0700', 'limite_inferior' => '30000.01', 'limite_superior' => null, 'vigente_desde' => '2013-01-01',
    ]);

    $factura = TipoDte::factory()->factura()->create();

    foreach (['2026-06', '2026-07', '2026-08'] as $periodo) {
        Documento::factory()->create([
            'tipo_dte_id' => $factura->id,
            'periodo' => $periodo,
            'direccion' => 'emitida',
            'iva' => '100.00',
            'base_sin_iva' => '833.33',
        ]);
    }
});

it('guarda el remanente tal como se copia de Declaraguate y lo propaga a los meses siguientes', function () {
    $this->get(route('dashboard', ['periodo' => '2026-08']))->assertOk();

    $respuesta = $this->post(route('dashboard.remanenteIva', '2026-07'), [
        'remanente_anterior_sat' => 'Q1,858',
    ]);

    $respuesta->assertRedirect(route('dashboard', ['periodo' => '2026-07']));

    $julio = Declaracion::where('periodo', '2026-07')->first();
    $agosto = Declaracion::where('periodo', '2026-08')->first();

    expect($julio->remanente_anterior_sat)->toBe('1858.00')
        ->and($julio->remanente_credito)->toBe('1758.00')
        ->and($agosto->remanente_credito)->toBe('1658.00');
});

it('muestra en el dashboard que el remanente viene de Declaraguate y cuánto calculó el programa', function () {
    $this->post(route('dashboard.remanenteIva', '2026-07'), ['remanente_anterior_sat' => '1858']);

    $respuesta = $this->get(route('dashboard', ['periodo' => '2026-07']));

    $respuesta->assertOk();
    $respuesta->assertSee('Q1,858.00', false);
    $respuesta->assertSee('Según Declaraguate · el programa calculó Q0.00', false);
});

it('vuelve al remanente calculado cuando el campo se envía vacío', function () {
    $this->post(route('dashboard.remanenteIva', '2026-07'), ['remanente_anterior_sat' => '1858']);
    $this->post(route('dashboard.remanenteIva', '2026-07'), ['remanente_anterior_sat' => '']);

    $julio = Declaracion::where('periodo', '2026-07')->first();

    expect($julio->remanente_anterior_sat)->toBeNull()
        ->and($julio->montos['remanenteAnterior'])->toBe('0.00');
});

it('acepta un remanente de cero', function () {
    $this->post(route('dashboard.remanenteIva', '2026-07'), ['remanente_anterior_sat' => '0']);

    expect(Declaracion::where('periodo', '2026-07')->first()->remanente_anterior_sat)->toBe('0.00');
});

it('rechaza un remanente que no es un monto positivo', function (string $valor) {
    $respuesta = $this->from(route('dashboard', ['periodo' => '2026-07']))
        ->post(route('dashboard.remanenteIva', '2026-07'), ['remanente_anterior_sat' => $valor]);

    $respuesta->assertSessionHasErrors('remanente_anterior_sat');
    expect(Declaracion::where('periodo', '2026-07')->whereNotNull('remanente_anterior_sat')->exists())->toBeFalse();
})->with(['negativo' => '-50', 'texto' => 'mil', 'tres decimales' => '10.555']);
