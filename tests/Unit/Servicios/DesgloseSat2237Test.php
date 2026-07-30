<?php

use App\Servicios\CalculadoraIvaGeneral;
use App\Servicios\DesgloseSat2237;
use App\Servicios\Dto\LineaIva;
use App\Servicios\Dto\LineaSat2237;

it('la suma de los subtotales de débito iguala el débito total de CalculadoraIvaGeneral', function () {
    $lineasEmitidas = [
        new LineaSat2237(iva: '480.00', baseSinIva: '4000.00', signo: 1, tipo: 'bien', generaCredito: null),
        new LineaSat2237(iva: '240.00', baseSinIva: '2000.00', signo: 1, tipo: 'servicio', generaCredito: null),
        new LineaSat2237(iva: '60.00', baseSinIva: '500.00', signo: -1, tipo: 'servicio', generaCredito: null), // NCRE de servicio
    ];

    $desglose = (new DesgloseSat2237)->calcular($lineasEmitidas, []);

    $debitoEsperado = (new CalculadoraIvaGeneral)->calcular(
        lineasDebito: array_map(fn (LineaSat2237 $l) => new LineaIva($l->iva, $l->signo), $lineasEmitidas),
        lineasCredito: [],
    )->debito;

    expect($desglose->ventasGravadasBien)->toBe('480.00')
        ->and($desglose->serviciosGravados)->toBe('180.00') // 240 - 60
        ->and($desglose->debitoTotal)->toBe('660.00')
        ->and($desglose->debitoTotal)->toBe($debitoEsperado)
        ->and($desglose->ingresosBienes)->toBe('4000.00')
        ->and($desglose->ingresosServicios)->toBe('1500.00');
});

it('la suma de los subtotales de crédito iguala el crédito total de CalculadoraIvaGeneral', function () {
    $lineasRecibidas = [
        new LineaSat2237(iva: '60.00', baseSinIva: '910.00', signo: 1, tipo: 'combustible', generaCredito: true),
        new LineaSat2237(iva: '120.00', baseSinIva: '1000.00', signo: 1, tipo: 'bien', generaCredito: true),
        new LineaSat2237(iva: '240.00', baseSinIva: '2000.00', signo: 1, tipo: 'servicio', generaCredito: true),
    ];

    $desglose = (new DesgloseSat2237)->calcular([], $lineasRecibidas);

    $creditoEsperado = (new CalculadoraIvaGeneral)->calcular(
        lineasDebito: [],
        lineasCredito: array_map(fn (LineaSat2237 $l) => new LineaIva($l->iva, $l->signo), $lineasRecibidas),
    )->credito;

    expect($desglose->creditoCombustibles)->toBe('60.00')
        ->and($desglose->creditoOtrasCompras)->toBe('120.00')
        ->and($desglose->creditoServiciosAdquiridos)->toBe('240.00')
        ->and($desglose->creditoTotal)->toBe('420.00')
        ->and($desglose->creditoTotal)->toBe($creditoEsperado);
});

it('una nota de crédito recibida resta dentro de su propia categoría, no de otras', function () {
    $lineasRecibidas = [
        new LineaSat2237(iva: '240.00', baseSinIva: '2000.00', signo: 1, tipo: 'servicio', generaCredito: true),
        new LineaSat2237(iva: '50.00', baseSinIva: '400.00', signo: -1, tipo: 'servicio', generaCredito: true), // NCRE de servicio
        new LineaSat2237(iva: '120.00', baseSinIva: '1000.00', signo: 1, tipo: 'bien', generaCredito: true),
    ];

    $desglose = (new DesgloseSat2237)->calcular([], $lineasRecibidas);

    expect($desglose->creditoServiciosAdquiridos)->toBe('190.00') // 240 - 50
        ->and($desglose->creditoOtrasCompras)->toBe('120.00'); // sin afectar
});

it('las compras que no generan crédito solo aparecen en la base informativa, no en el crédito', function () {
    $lineasRecibidas = [
        new LineaSat2237(iva: '240.00', baseSinIva: '2000.00', signo: 1, tipo: 'servicio', generaCredito: true),
        new LineaSat2237(iva: '999.00', baseSinIva: '5000.00', signo: 1, tipo: 'servicio', generaCredito: false),
    ];

    $desglose = (new DesgloseSat2237)->calcular([], $lineasRecibidas);

    expect($desglose->creditoServiciosAdquiridos)->toBe('240.00')
        ->and($desglose->creditoTotal)->toBe('240.00')
        ->and($desglose->baseNoDeducible)->toBe('5000.00');
});

it('retorna 0 en el subtotal de un tipo que no tiene documentos en el período', function () {
    $lineasEmitidas = [
        new LineaSat2237(iva: '480.00', baseSinIva: '4000.00', signo: 1, tipo: 'bien', generaCredito: null),
    ];

    $desglose = (new DesgloseSat2237)->calcular($lineasEmitidas, []);

    expect($desglose->serviciosGravados)->toBe('0.00')
        ->and($desglose->ingresosServicios)->toBe('0.00')
        ->and($desglose->creditoCombustibles)->toBe('0.00')
        ->and($desglose->creditoOtrasCompras)->toBe('0.00')
        ->and($desglose->creditoServiciosAdquiridos)->toBe('0.00')
        ->and($desglose->baseNoDeducible)->toBe('0.00');
});

it('retorna todo en cero cuando no hay documentos ni remanente', function () {
    $desglose = (new DesgloseSat2237)->calcular([], []);

    expect($desglose->debitoTotal)->toBe('0.00')
        ->and($desglose->creditoTotal)->toBe('0.00')
        ->and($desglose->remanenteAnterior)->toBe('0.00');
});
