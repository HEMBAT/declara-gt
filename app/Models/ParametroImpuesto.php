<?php

namespace App\Models;

use App\Enums\TipoParametroImpuesto;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

#[Table('parametros_impuesto')]
#[Fillable([
    'tipo', 'tasa', 'limite_inferior', 'limite_superior',
    'vigente_desde', 'vigente_hasta',
])]
class ParametroImpuesto extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'tipo' => TipoParametroImpuesto::class,
            'tasa' => 'decimal:4',
            'limite_inferior' => 'decimal:2',
            'limite_superior' => 'decimal:2',
            'vigente_desde' => 'date',
            'vigente_hasta' => 'date',
        ];
    }

    /**
     * Filtra los parámetros vigentes en la fecha dada (vigente_desde <= fecha
     * y vigente_hasta nulo o >= fecha).
     */
    #[Scope]
    protected function vigenteEn(Builder $query, CarbonInterface $fecha): void
    {
        $query->where('vigente_desde', '<=', $fecha)
            ->where(function (Builder $query) use ($fecha) {
                $query->whereNull('vigente_hasta')
                    ->orWhere('vigente_hasta', '>=', $fecha);
            });
    }

    /**
     * Tramos de ISR Opcional Simplificado vigentes en la fecha dada, ordenados
     * de menor a mayor límite inferior.
     *
     * @return Collection<int, self>
     */
    public static function tramosIsrVigentesEn(CarbonInterface $fecha): Collection
    {
        return self::query()
            ->where('tipo', TipoParametroImpuesto::IsrTramo)
            ->vigenteEn($fecha)
            ->orderBy('limite_inferior')
            ->get();
    }
}
