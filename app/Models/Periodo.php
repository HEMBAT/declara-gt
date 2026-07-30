<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\WithoutIncrementing;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Bitácora de estado de un período (pendiente/calculado/declarado). Es
 * exclusivamente metadata de flujo de trabajo del usuario: nunca la toca
 * CalculadoraIsrOpcional ni CalculoIsrPeriodoService, que siempre recalculan
 * a partir de documentos, retenciones y parametros_impuesto.
 */
#[WithoutIncrementing]
#[Fillable(['periodo', 'estado', 'fecha_presentacion'])]
class Periodo extends Model
{
    use HasFactory;

    protected $primaryKey = 'periodo';

    protected $keyType = 'string';

    protected function casts(): array
    {
        return [
            'fecha_presentacion' => 'date',
        ];
    }

    public static function paraPeriodo(string $periodo): self
    {
        return self::query()->firstOrCreate(['periodo' => $periodo]);
    }
}
