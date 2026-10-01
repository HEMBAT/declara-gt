<?php

use App\Models\Cliente;
use App\Models\Contribuyente;
use App\Models\Declaracion;
use App\Models\Documento;
use App\Models\ParametroImpuesto;
use App\Models\Retencion;
use App\Models\RetencionIva;
use App\Models\TipoDte;

it('redirige a Importación después de guardar el contribuyente, en vez de dejar al usuario varado', function () {
    $respuesta = $this->post(route('configuracion.guardar'), [
        'nit' => '1234567-8',
        'nombre' => 'Empresa Ejemplo, S.A.',
    ]);

    $respuesta->assertRedirect(route('importar.index'));
    $respuesta->assertSessionHas('exito');
    expect(Contribuyente::actual()?->nit)->toBe('1234567-8');
});

it('permite corregir el NIT ya guardado', function () {
    Contribuyente::factory()->create(['nit' => '0000000-0', 'nombre' => 'Nombre Equivocado']);

    $respuesta = $this->post(route('configuracion.guardar'), [
        'nit' => '1234567-8',
        'nombre' => 'Nombre Correcto, S.A.',
    ]);

    $respuesta->assertRedirect(route('importar.index'));
    expect(Contribuyente::query()->count())->toBe(1)
        ->and(Contribuyente::actual()?->nit)->toBe('1234567-8')
        ->and(Contribuyente::actual()?->nombre)->toBe('Nombre Correcto, S.A.');
});

it('muestra un enlace a Configuración en la navegación para poder corregir el NIT después', function () {
    $respuesta = $this->get(route('dashboard'));

    $respuesta->assertOk();
    $respuesta->assertSee(route('configuracion.editar'), false);
});

it('bloquea el cambio de NIT si ya existen documentos', function () {
    $contribuyente = Contribuyente::factory()->create(['nit' => '1234567-8']);
    Documento::factory()->create();

    $respuesta = $this->post(route('configuracion.guardar'), [
        'nit' => '9999999-9',
        'nombre' => $contribuyente->nombre,
    ]);

    $respuesta->assertSessionHasErrors('nit');
    expect(Contribuyente::actual()?->nit)->toBe('1234567-8');
});

it('permite editar el nombre aunque existan documentos', function () {
    $contribuyente = Contribuyente::factory()->create(['nit' => '1234567-8']);
    Documento::factory()->create();

    $respuesta = $this->post(route('configuracion.guardar'), [
        'nit' => '1234567-8',
        'nombre' => 'Nombre Actualizado, S.A.',
    ]);

    $respuesta->assertSessionDoesntHaveErrors();
    expect(Contribuyente::actual()?->nombre)->toBe('Nombre Actualizado, S.A.');
});

it('reinicia los datos borrando documentos, clientes, retenciones, declaraciones y contribuyente', function () {
    $contribuyente = Contribuyente::factory()->create(['nit' => '1234567-8']);
    Documento::factory()->create();
    Retencion::factory()->create();
    RetencionIva::factory()->create();
    Declaracion::factory()->create();
    TipoDte::factory()->create();
    ParametroImpuesto::factory()->create();

    $respuesta = $this->post(route('configuracion.reiniciar'), [
        'confirmar_nit' => '1234567-8',
    ]);

    $respuesta->assertRedirect(route('configuracion.editar'));
    $respuesta->assertSessionHas('exito');

    expect(Documento::count())->toBe(0)
        ->and(Cliente::count())->toBe(0)
        ->and(Retencion::count())->toBe(0)
        ->and(RetencionIva::count())->toBe(0)
        ->and(Declaracion::count())->toBe(0)
        ->and(Contribuyente::count())->toBe(0)
        ->and(TipoDte::count())->toBeGreaterThan(0)
        ->and(ParametroImpuesto::count())->toBeGreaterThan(0);

    $siguiente = $this->get(route('configuracion.editar'));
    $siguiente->assertOk();
    $siguiente->assertDontSee('readonly', false);
});

it('no reinicia si el NIT de confirmación no coincide', function () {
    Contribuyente::factory()->create(['nit' => '1234567-8']);
    Documento::factory()->create();

    $respuesta = $this->post(route('configuracion.reiniciar'), [
        'confirmar_nit' => '0000000-0',
    ]);

    $respuesta->assertSessionHasErrors('confirmar_nit');
    expect(Contribuyente::count())->toBe(1)
        ->and(Documento::count())->toBe(1);
});

it('no reinicia si no hay contribuyente configurado', function () {
    $respuesta = $this->post(route('configuracion.reiniciar'), [
        'confirmar_nit' => '1234567-8',
    ]);

    $respuesta->assertSessionHasErrors('confirmar_nit');
});
