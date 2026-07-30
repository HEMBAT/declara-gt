<?php

namespace Database\Factories;

use App\Models\Declaracion;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Declaracion>
 */
class DeclaracionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'tipo' => 'IVA',
            'periodo' => fake()->date('Y-m'),
            'montos' => [],
            'remanente_credito' => '0.00',
            'estado' => 'pendiente',
        ];
    }
}
