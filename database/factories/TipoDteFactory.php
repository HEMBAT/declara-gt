<?php

namespace Database\Factories;

use App\Models\TipoDte;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TipoDte>
 */
class TipoDteFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'codigo' => strtoupper(fake()->unique()->lexify('????')),
            'nombre' => fake()->words(2, true),
            'signo' => 1,
            'revisar' => false,
        ];
    }

    public function factura(): static
    {
        return $this->state(fn () => ['codigo' => 'FACT', 'nombre' => 'Factura', 'signo' => 1]);
    }

    public function notaDeCredito(): static
    {
        return $this->state(fn () => ['codigo' => 'NCRE', 'nombre' => 'Nota de Crédito', 'signo' => -1]);
    }

    public function pequenoContribuyente(): static
    {
        return $this->state(fn () => ['codigo' => 'FPEQ', 'nombre' => 'Factura Pequeño Contribuyente', 'signo' => 1]);
    }
}
