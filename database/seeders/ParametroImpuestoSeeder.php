<?php

namespace Database\Seeders;

use App\Enums\TipoParametroImpuesto;
use App\Models\ParametroImpuesto;
use Illuminate\Database\Seeder;

class ParametroImpuestoSeeder extends Seeder
{
    /**
     * Vigencias iniciales. IVA usa 1992-08-01 (Decreto 27-92) según la
     * corrección del §9 del brief, no el 2013-01-01 mencionado en el §3.
     * Verificar con contador antes del release público.
     */
    public function run(): void
    {
        ParametroImpuesto::query()->firstOrCreate([
            'tipo' => TipoParametroImpuesto::Iva,
            'vigente_desde' => '1992-08-01',
        ], [
            'tasa' => '0.1200',
            'limite_inferior' => null,
            'limite_superior' => null,
            'vigente_hasta' => null,
        ]);

        ParametroImpuesto::query()->firstOrCreate([
            'tipo' => TipoParametroImpuesto::IsrTramo,
            'vigente_desde' => '2013-01-01',
            'limite_inferior' => '0.00',
        ], [
            'tasa' => '0.0500',
            'limite_superior' => '30000.00',
            'vigente_hasta' => null,
        ]);

        ParametroImpuesto::query()->firstOrCreate([
            'tipo' => TipoParametroImpuesto::IsrTramo,
            'vigente_desde' => '2013-01-01',
            'limite_inferior' => '30000.01',
        ], [
            'tasa' => '0.0700',
            'limite_superior' => null,
            'vigente_hasta' => null,
        ]);
    }
}
