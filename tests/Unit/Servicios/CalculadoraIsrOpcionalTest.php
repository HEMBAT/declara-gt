<?php

use App\Servicios\CalculadoraIsrOpcional;
use App\Servicios\Dto\LineaDocumento;
use App\Servicios\Dto\TramoIsr;

function tramosIsrDePrueba(): array
{
    return [
        new TramoIsr(tasa: '0.0500', limiteInferior: '0.00', limiteSuperior: '30000.00'),
        new TramoIsr(tasa: '0.0700', limiteInferior: '30000.01', limiteSuperior: null),
    ];
}

it('calcula todo al 5% cuando la base es exactamente Q30,000.00', function () {
    $resultado = (new CalculadoraIsrOpcional)->calcular(
        lineas: [new LineaDocumento(baseSinIva: '30000.00', signo: 1)],
        tramosIsr: tramosIsrDePrueba(),
    );

    expect($resultado->baseGravable)->toBe('30000.00')
        ->and($resultado->tramo1Base)->toBe('30000.00')
        ->and($resultado->tramo1Monto)->toBe('1500.00')
        ->and($resultado->tramo2Base)->toBe('0.00')
        ->and($resultado->tramo2Monto)->toBe('0.00')
        ->and($resultado->isrDeterminado)->toBe('1500.00')
        ->and($resultado->isrAPagar)->toBe('1500.00');
});

it('separa un centavo al tramo del 7% cuando la base es Q30,000.01', function () {
    $resultado = (new CalculadoraIsrOpcional)->calcular(
        lineas: [new LineaDocumento(baseSinIva: '30000.01', signo: 1)],
        tramosIsr: tramosIsrDePrueba(),
    );

    expect($resultado->tramo1Base)->toBe('30000.00')
        ->and($resultado->tramo1Monto)->toBe('1500.00')
        ->and($resultado->tramo2Base)->toBe('0.01')
        // 0.01 x 7% = 0.0007, redondea a 0.00 al centavo más cercano.
        ->and($resultado->tramo2Monto)->toBe('0.00')
        ->and($resultado->isrDeterminado)->toBe('1500.00');
});

it('resta el monto de una nota de crédito de la base gravable', function () {
    $resultado = (new CalculadoraIsrOpcional)->calcular(
        lineas: [
            new LineaDocumento(baseSinIva: '10000.00', signo: 1),
            new LineaDocumento(baseSinIva: '2000.00', signo: -1),
        ],
        tramosIsr: tramosIsrDePrueba(),
    );

    expect($resultado->baseGravable)->toBe('8000.00')
        ->and($resultado->tramo1Monto)->toBe('400.00')
        ->and($resultado->isrDeterminado)->toBe('400.00');
});

it('acredita las retenciones del período contra el ISR determinado', function () {
    $resultado = (new CalculadoraIsrOpcional)->calcular(
        lineas: [new LineaDocumento(baseSinIva: '30000.00', signo: 1)],
        tramosIsr: tramosIsrDePrueba(),
        totalRetenciones: '500.00',
    );

    expect($resultado->isrDeterminado)->toBe('1500.00')
        ->and($resultado->totalRetenciones)->toBe('500.00')
        ->and($resultado->isrAPagar)->toBe('1000.00');
});

it('nunca resulta negativo cuando las retenciones superan el impuesto determinado', function () {
    $resultado = (new CalculadoraIsrOpcional)->calcular(
        lineas: [new LineaDocumento(baseSinIva: '30000.00', signo: 1)],
        tramosIsr: tramosIsrDePrueba(),
        totalRetenciones: '2000.00',
    );

    expect($resultado->isrDeterminado)->toBe('1500.00')
        ->and($resultado->isrAPagar)->toBe('0.00');
});

it('retorna cero en todos los campos cuando el período no tiene documentos', function () {
    $resultado = (new CalculadoraIsrOpcional)->calcular(
        lineas: [],
        tramosIsr: tramosIsrDePrueba(),
    );

    expect($resultado->baseGravable)->toBe('0.00')
        ->and($resultado->tramo1Monto)->toBe('0.00')
        ->and($resultado->tramo2Monto)->toBe('0.00')
        ->and($resultado->isrDeterminado)->toBe('0.00')
        ->and($resultado->isrAPagar)->toBe('0.00');
});

it('redondea de forma consistente al centavo sin arrastrar error de punto flotante', function (string $base, string $montoEsperado) {
    $resultado = (new CalculadoraIsrOpcional)->calcular(
        lineas: [new LineaDocumento(baseSinIva: $base, signo: 1)],
        tramosIsr: tramosIsrDePrueba(),
    );

    expect($resultado->tramo2Monto)->toBe($montoEsperado);
})->with([
    'Q0.01 de excedente redondea a la baja' => ['30000.01', '0.00'],
    'Q0.10 de excedente redondea a la alza' => ['30000.10', '0.01'],
    'Q0.50 de excedente cae justo en el punto medio (mitad hacia arriba)' => ['30000.50', '0.04'],
    'Q100.00 de excedente sin residuo' => ['30100.00', '7.00'],
]);
