<?php

namespace App\Models;

use App\Enums\TipoDeclaracion;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Registro de los montos calculados de un período (ISR o IVA). Para IVA es
 * indispensable: el remanente_credito de un período es la entrada del
 * siguiente (§10 del brief), así que CalculoIvaPeriodoService lo persiste
 * aquí cada vez que calcula. Para ISR, esta tabla no se usa todavía —
 * CalculoIsrPeriodoService sigue calculando siempre al vuelo.
 */
#[Table('declaraciones')]
#[Fillable(['tipo', 'periodo', 'montos', 'remanente_credito', 'estado'])]
class Declaracion extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'tipo' => TipoDeclaracion::class,
            'montos' => 'array',
            'remanente_credito' => 'decimal:2',
        ];
    }
}
