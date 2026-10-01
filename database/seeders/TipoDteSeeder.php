<?php

namespace Database\Seeders;

use App\Models\TipoDte;
use Illuminate\Database\Seeder;

class TipoDteSeeder extends Seeder
{
    /**
     * Catálogo inicial de tipos de DTE del FEL guatemalteco. La lista y la
     * justificación de cada signo viven en TipoDte::CATALOGO.
     */
    public function run(): void
    {
        foreach (TipoDte::CATALOGO as $codigo => $atributos) {
            TipoDte::query()->updateOrCreate(['codigo' => $codigo], $atributos);
        }
    }
}
