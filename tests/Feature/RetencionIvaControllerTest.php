<?php

use App\Models\Declaracion;
use App\Models\RetencionIva;

it('registra una constancia de retención de IVA y recalcula el saldo del período', function () {
    $respuesta = $this->post(route('retencionesIva.guardar'), [
        'periodo' => '2026-08',
        'monto' => 'Q96.43',
        'descripcion' => 'Constancia del cliente',
    ]);

    $respuesta->assertRedirect(route('retenciones.index', ['periodo' => '2026-08']));

    expect(RetencionIva::first()->monto)->toBe('96.43')
        ->and(Declaracion::where('periodo', '2026-08')->first()->remanente_retenciones)->toBe('96.43');
});

it('rechaza una constancia con monto cero o inválido', function (string $monto) {
    $this->post(route('retencionesIva.guardar'), ['periodo' => '2026-08', 'monto' => $monto])
        ->assertSessionHasErrors('monto');

    expect(RetencionIva::count())->toBe(0);
})->with(['cero' => '0', 'negativo' => '-5', 'texto' => 'noventa']);

it('al eliminar una constancia recalcula el saldo de ese período y los siguientes', function () {
    $this->post(route('retencionesIva.guardar'), ['periodo' => '2026-07', 'monto' => '96.43']);
    $this->post(route('retencionesIva.guardar'), ['periodo' => '2026-08', 'monto' => '96.43']);

    expect(Declaracion::where('periodo', '2026-08')->first()->remanente_retenciones)->toBe('192.86');

    $this->delete(route('retencionesIva.eliminar', RetencionIva::where('periodo', '2026-07')->first()));

    expect(Declaracion::where('periodo', '2026-07')->first()->remanente_retenciones)->toBe('0.00')
        ->and(Declaracion::where('periodo', '2026-08')->first()->remanente_retenciones)->toBe('96.43');
});

it('guarda el saldo según Declaraguate y la devolución con su resolución', function () {
    RetencionIva::factory()->create(['periodo' => '2026-08', 'monto' => '96.00']);

    $respuesta = $this->post(route('retencionesIva.saldo', '2026-08'), [
        'remanente_retenciones_anterior_sat' => '1,585',
        'acreditamiento_retenciones' => '1000',
        'resolucion_acreditamiento' => 'R-2026-123',
    ]);

    $respuesta->assertRedirect(route('retenciones.index', ['periodo' => '2026-08']));

    $agosto = Declaracion::where('periodo', '2026-08')->first();

    expect($agosto->remanente_retenciones_anterior_sat)->toBe('1585.00')
        ->and($agosto->acreditamiento_retenciones)->toBe('1000.00')
        ->and($agosto->resolucion_acreditamiento)->toBe('R-2026-123')
        ->and($agosto->remanente_retenciones)->toBe('681.00');
});

it('exige el número de resolución cuando se registra un acreditamiento', function () {
    $this->post(route('retencionesIva.saldo', '2026-08'), ['acreditamiento_retenciones' => '1000'])
        ->assertSessionHasErrors('resolucion_acreditamiento');
});

it('borra los datos según Declaraguate cuando se envían vacíos', function () {
    $this->post(route('retencionesIva.saldo', '2026-08'), ['remanente_retenciones_anterior_sat' => '1585']);
    $this->post(route('retencionesIva.saldo', '2026-08'), ['remanente_retenciones_anterior_sat' => '']);

    $agosto = Declaracion::where('periodo', '2026-08')->first();

    expect($agosto->remanente_retenciones_anterior_sat)->toBeNull()
        ->and($agosto->remanente_retenciones)->toBe('0.00');
});

it('muestra el cuadro 7 y avisa que las retenciones se acumulan cuando no hay IVA a pagar', function () {
    $this->post(route('retencionesIva.saldo', '2026-08'), ['remanente_retenciones_anterior_sat' => '1585']);
    RetencionIva::factory()->create(['periodo' => '2026-08', 'monto' => '96.00', 'descripcion' => 'Constancia de agosto']);

    $respuesta = $this->get(route('retenciones.index', ['periodo' => '2026-08']));

    $respuesta->assertOk();
    $respuesta->assertSee('Constancia de agosto');
    $respuesta->assertSeeInOrder([
        '(=) Remanente de retenciones IVA recibidas en el período', 'Q1,585.00',
        'Saldo de retenciones para el período siguiente', 'Q1,681.00',
    ], false);
    $respuesta->assertSee('tus retenciones no se usan y se siguen acumulando', false);
});

it('incluye el cuadro 7 en la pantalla de formulario', function () {
    RetencionIva::factory()->create(['periodo' => '2026-08', 'monto' => '96.00']);

    $respuesta = $this->get(route('formulario.index', ['periodo' => '2026-08']));

    $respuesta->assertOk();
    $respuesta->assertSee('Cuadro 7 — Retenciones de IVA', false);
    $respuesta->assertSee('Q96.00', false);
});
