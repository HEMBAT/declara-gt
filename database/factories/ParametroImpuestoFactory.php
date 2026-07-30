<?php

namespace Database\Factories;

use App\Models\ParametroImpuesto;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ParametroImpuesto>
 */
class ParametroImpuestoFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'tipo' => 'ISR_TRAMO',
            'tasa' => '0.0500',
            'limite_inferior' => '0.00',
            'limite_superior' => '30000.00',
            'vigente_desde' => '2013-01-01',
            'vigente_hasta' => null,
        ];
    }
}
