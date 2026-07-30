<?php

namespace Database\Factories;

use App\Models\Contribuyente;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Contribuyente>
 */
class ContribuyenteFactory extends Factory
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
        ];
    }
}
