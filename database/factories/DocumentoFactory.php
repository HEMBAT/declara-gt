<?php

namespace Database\Factories;

use App\Models\Cliente;
use App\Models\Documento;
use App\Models\TipoDte;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Documento>
 */
class DocumentoFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $fechaEmision = fake()->dateTimeBetween('-1 year', 'now');
        $granTotal = fake()->randomFloat(2, 100, 20000);
        $iva = round($granTotal - ($granTotal / 1.12), 2);
        $baseSinIva = round($granTotal - $iva, 2);

        return [
            'numero_autorizacion' => fake()->unique()->uuid(),
            'fecha_emision' => $fechaEmision,
            'periodo' => $fechaEmision->format('Y-m'),
            'tipo_dte_id' => fn () => TipoDte::query()->firstOrCreate(
                ['codigo' => 'FACT'],
                ['nombre' => 'Factura', 'signo' => 1, 'revisar' => false]
            )->id,
            'serie' => strtoupper(fake()->bothify('??##')),
            'numero' => (string) fake()->unique()->numberBetween(1, 999999),
            'cliente_id' => Cliente::factory(),
            'direccion' => 'emitida',
            'gran_total' => $granTotal,
            'iva' => $iva,
            'base_sin_iva' => $baseSinIva,
            'tipo' => 'bien',
            'moneda' => 'GTQ',
            'estado' => 'Vigente',
            'anulado' => false,
        ];
    }
}
