<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Constancia de retención de IVA recibida en un período. Se acredita contra
 * el IVA a pagar del SAT-2237; lo que no se usa se arrastra como remanente de
 * retenciones (ver CalculadoraIvaGeneral).
 */
#[Table('retenciones_iva')]
#[Fillable(['periodo', 'monto', 'descripcion'])]
class RetencionIva extends Model
{
    use HasFactory;

    /**
     * Suma de las constancias del período en formato "0.00", sumada en
     * centavos para no pasar por punto flotante.
     */
    public static function totalDelPeriodo(string $periodo): string
    {
        $centavos = self::query()
            ->where('periodo', $periodo)
            ->pluck('monto')
            ->sum(fn (string $monto) => (int) str_replace('.', '', $monto));

        return sprintf('%d.%02d', intdiv($centavos, 100), $centavos % 100);
    }

    protected function casts(): array
    {
        return [
            'monto' => 'decimal:2',
        ];
    }
}
