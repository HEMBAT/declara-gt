<?php

namespace Database\Seeders;

use App\Models\TipoDte;
use Illuminate\Database\Seeder;

class TipoDteSeeder extends Seeder
{
    /**
     * Catálogo inicial de tipos de DTE del FEL guatemalteco.
     *
     * Signos: NCRE (nota de crédito) resta de la base gravable, es el único
     * signo negativo explícito en el brief. NDEB (nota de débito) es +1 por
     * ser el opuesto simétrico de NCRE: aumenta lo que debe el receptor.
     * NABN (nota de abono) queda +1 pero marcada "revisar" porque su
     * semántica en el catálogo FEL no es un ajuste crédito/débito estándar
     * y amerita confirmación de un contador antes del release público.
     */
    public function run(): void
    {
        $tipos = [
            ['codigo' => 'FACT', 'nombre' => 'Factura', 'signo' => 1, 'revisar' => false],
            ['codigo' => 'FCAM', 'nombre' => 'Factura Cambiaria', 'signo' => 1, 'revisar' => false],
            ['codigo' => 'FPEQ', 'nombre' => 'Factura Pequeño Contribuyente', 'signo' => 1, 'revisar' => false],
            ['codigo' => 'FESP', 'nombre' => 'Factura Especial', 'signo' => 1, 'revisar' => false],
            ['codigo' => 'RECI', 'nombre' => 'Recibo', 'signo' => 1, 'revisar' => false],
            ['codigo' => 'NDEB', 'nombre' => 'Nota de Débito', 'signo' => 1, 'revisar' => false],
            ['codigo' => 'NABN', 'nombre' => 'Nota de Abono', 'signo' => 1, 'revisar' => true],
            ['codigo' => 'NCRE', 'nombre' => 'Nota de Crédito', 'signo' => -1, 'revisar' => false],
        ];

        foreach ($tipos as $tipo) {
            TipoDte::query()->updateOrCreate(['codigo' => $tipo['codigo']], $tipo);
        }
    }
}
