<?php

namespace Database\Factories;

use App\Models\Cliente;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Cliente>
 */
class ClienteFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'nit' => fake()->unique()->numerify('#######-#'),
            'nombre' => fake()->company().' S.A.',
            'tipo_default' => fake()->randomElement(['bien', 'servicio', 'combustible']),
        ];
    }
}
