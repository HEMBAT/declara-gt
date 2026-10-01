<?php

namespace Database\Factories;

use App\Models\RetencionIva;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<RetencionIva>
 */
class RetencionIvaFactory extends Factory
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
            'monto' => fake()->randomFloat(2, 10, 500),
            'descripcion' => 'Constancia de retención de IVA '.fake()->bothify('R-####'),
        ];
    }
}
