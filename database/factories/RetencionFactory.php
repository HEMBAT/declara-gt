<?php

namespace Database\Factories;

use App\Models\Retencion;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Retencion>
 */
class RetencionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'periodo' => fake()->date('Y-m'),
            'monto_isr' => fake()->randomFloat(2, 50, 1000),
            'descripcion' => 'Constancia de retención '.fake()->bothify('C-####'),
        ];
    }
}
