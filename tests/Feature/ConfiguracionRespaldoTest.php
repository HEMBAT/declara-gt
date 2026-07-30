<?php

use App\Models\Contribuyente;
use Illuminate\Support\Facades\DB;

it('descarga un respaldo consistente generado con VACUUM INTO', function () {
    $contribuyente = Contribuyente::factory()->create(['nit' => '1234567-8', 'nombre' => 'Empresa Ejemplo']);

    // SQLite no permite VACUUM dentro de una transacción, y RefreshDatabase
    // envuelve cada test en una. Salimos de ella para la llamada real y
    // volvemos a abrir una (vacía) para que el rollback de RefreshDatabase
    // al final del test siga funcionando como espera.
    DB::commit();

    try {
        $respuesta = $this->get(route('configuracion.respaldo'));

        $respuesta->assertOk();

        $nombreEsperado = 'declara-gt_12345678_'.now()->format('Y-m-d').'.sqlite';
        expect($respuesta->headers->get('content-disposition'))->toContain($nombreEsperado);

        $rutaGenerada = storage_path('app/respaldos/'.$nombreEsperado);
        expect(file_exists($rutaGenerada))->toBeTrue();

        $pdo = new PDO('sqlite:'.$rutaGenerada);
        $fila = $pdo->query('SELECT nit, nombre FROM contribuyente LIMIT 1')->fetch(PDO::FETCH_ASSOC);

        expect($fila['nit'])->toBe('1234567-8')
            ->and($fila['nombre'])->toBe('Empresa Ejemplo');

        unset($pdo);
        @unlink($rutaGenerada);
    } finally {
        $contribuyente->delete();
        DB::beginTransaction();
    }
});
