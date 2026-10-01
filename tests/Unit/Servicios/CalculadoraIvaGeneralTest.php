<?php

use App\Servicios\CalculadoraIvaGeneral;
use App\Servicios\Dto\LineaIva;

it('calcula el débito de las emitidas cuando no hay recibidas', function () {
    $resultado = (new CalculadoraIvaGeneral)->calcular(
        lineasDebito: [new LineaIva(iva: '500.00', signo: 1)],
        lineasCredito: [],
    );

    expect($resultado->debito)->toBe('500.00')
        ->and($resultado->credito)->toBe('0.00')
        ->and($resultado->ivaAPagar)->toBe('500.00');
});

it('calcula el crédito de las recibidas cuando no hay emitidas', function () {
    $resultado = (new CalculadoraIvaGeneral)->calcular(
        lineasDebito: [],
        lineasCredito: [new LineaIva(iva: '300.00', signo: 1)],
    );

    expect($resultado->debito)->toBe('0.00')
        ->and($resultado->credito)->toBe('300.00')
        ->and($resultado->ivaAPagar)->toBe('0.00')
        ->and($resultado->remanenteCredito)->toBe('300.00');
});

it('una nota de crédito recibida reduce el crédito fiscal', function () {
    $resultado = (new CalculadoraIvaGeneral)->calcular(
        lineasDebito: [],
        lineasCredito: [
            new LineaIva(iva: '300.00', signo: 1),
            new LineaIva(iva: '50.00', signo: -1),
        ],
    );

    expect($resultado->credito)->toBe('250.00');
});

it('cuando el débito supera al crédito, el resultado es IVA a pagar y no hay remanente', function () {
    $resultado = (new CalculadoraIvaGeneral)->calcular(
        lineasDebito: [new LineaIva(iva: '1000.00', signo: 1)],
        lineasCredito: [new LineaIva(iva: '400.00', signo: 1)],
    );

    expect($resultado->resultado)->toBe('600.00')
        ->and($resultado->ivaAPagar)->toBe('600.00')
        ->and($resultado->remanenteCredito)->toBe('0.00');
});

it('cuando el crédito supera al débito, no se paga nada y el excedente se arrastra como remanente', function () {
    $resultado = (new CalculadoraIvaGeneral)->calcular(
        lineasDebito: [new LineaIva(iva: '400.00', signo: 1)],
        lineasCredito: [new LineaIva(iva: '1000.00', signo: 1)],
    );

    expect($resultado->resultado)->toBe('-600.00')
        ->and($resultado->ivaAPagar)->toBe('0.00')
        ->and($resultado->remanenteCredito)->toBe('600.00');
});

it('resta el remanente del período anterior antes de determinar el resultado', function () {
    $resultado = (new CalculadoraIvaGeneral)->calcular(
        lineasDebito: [new LineaIva(iva: '1000.00', signo: 1)],
        lineasCredito: [new LineaIva(iva: '400.00', signo: 1)],
        remanenteAnterior: '600.00',
    );

    // débito 1000 - crédito 400 - remanente 600 = 0
    expect($resultado->resultado)->toBe('0.00')
        ->and($resultado->ivaAPagar)->toBe('0.00')
        ->and($resultado->remanenteCredito)->toBe('0.00');
});

it('retorna todo en cero cuando no hay documentos ni remanente', function () {
    $resultado = (new CalculadoraIvaGeneral)->calcular(lineasDebito: [], lineasCredito: []);

    expect($resultado->debito)->toBe('0.00')
        ->and($resultado->credito)->toBe('0.00')
        ->and($resultado->resultado)->toBe('0.00')
        ->and($resultado->ivaAPagar)->toBe('0.00')
        ->and($resultado->remanenteCredito)->toBe('0.00');
});

it('acumula las retenciones de IVA cuando no hay impuesto contra el cual usarlas', function () {
    // Agosto 2026 real: el crédito supera al débito y las retenciones solo se suman.
    $resultado = (new CalculadoraIvaGeneral)->calcular(
        lineasDebito: [new LineaIva(iva: '642.86', signo: 1)],
        lineasCredito: [new LineaIva(iva: '587.72', signo: 1)],
        remanenteAnterior: '7054.00',
        remanenteRetencionesAnterior: '1585.00',
        retencionesPeriodo: '96.43',
    );

    expect($resultado->impuestoDeterminado)->toBe('0.00')
        ->and($resultado->remanenteRetenciones)->toBe('1585.00')
        ->and($resultado->retencionesAplicadas)->toBe('0.00')
        ->and($resultado->saldoRetenciones)->toBe('1681.43')
        ->and($resultado->ivaAPagar)->toBe('0.00');
});

it('descuenta las retenciones de IVA del impuesto determinado', function () {
    $resultado = (new CalculadoraIvaGeneral)->calcular(
        lineasDebito: [new LineaIva(iva: '1000.00', signo: 1)],
        lineasCredito: [new LineaIva(iva: '200.00', signo: 1)],
        remanenteRetencionesAnterior: '300.00',
        retencionesPeriodo: '100.00',
    );

    expect($resultado->impuestoDeterminado)->toBe('800.00')
        ->and($resultado->retencionesAplicadas)->toBe('400.00')
        ->and($resultado->ivaAPagar)->toBe('400.00')
        ->and($resultado->saldoRetenciones)->toBe('0.00');
});

it('arrastra la parte de las retenciones que excede al impuesto', function () {
    $resultado = (new CalculadoraIvaGeneral)->calcular(
        lineasDebito: [new LineaIva(iva: '100.00', signo: 1)],
        lineasCredito: [],
        remanenteRetencionesAnterior: '300.00',
        retencionesPeriodo: '100.00',
    );

    expect($resultado->retencionesAplicadas)->toBe('100.00')
        ->and($resultado->ivaAPagar)->toBe('0.00')
        ->and($resultado->saldoRetenciones)->toBe('300.00');
});

it('resta el acreditamiento en cuenta bancaria del remanente de retenciones', function () {
    $resultado = (new CalculadoraIvaGeneral)->calcular(
        lineasDebito: [],
        lineasCredito: [],
        remanenteRetencionesAnterior: '1585.00',
        acreditamientoRetenciones: '1000.00',
        retencionesPeriodo: '96.00',
    );

    expect($resultado->acreditamientoRetenciones)->toBe('1000.00')
        ->and($resultado->remanenteRetenciones)->toBe('585.00')
        ->and($resultado->saldoRetenciones)->toBe('681.00');
});

it('nunca deja negativo el remanente de retenciones aunque el acreditamiento lo supere', function () {
    $resultado = (new CalculadoraIvaGeneral)->calcular(
        lineasDebito: [],
        lineasCredito: [],
        remanenteRetencionesAnterior: '100.00',
        acreditamientoRetenciones: '150.00',
    );

    expect($resultado->remanenteRetenciones)->toBe('0.00')
        ->and($resultado->saldoRetenciones)->toBe('0.00');
});
