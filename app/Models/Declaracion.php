<?php

namespace App\Models;

use App\Enums\TipoDeclaracion;
use Carbon\Carbon;
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
 *
 * remanente_anterior_sat es lo único que escribe el usuario: el remanente
 * del período anterior que muestra Declaraguate. Los recálculos nunca lo
 * tocan.
 */
#[Table('declaraciones')]
#[Fillable(['tipo', 'periodo', 'montos', 'remanente_credito', 'remanente_anterior_sat', 'estado'])]
class Declaracion extends Model
{
    use HasFactory;

    /**
     * Remanente de crédito con que arranca el IVA de un período: el que el
     * usuario copió de Declaraguate si lo registró, o si no el que dejó
     * calculado el período anterior. Lo leen tanto CalculoIvaPeriodoService
     * como CalculoDesgloseSat2237PeriodoService, para que nunca diverjan.
     */
    public static function remanenteIvaAnterior(string $periodo): string
    {
        $segunSat = self::query()
            ->where('tipo', TipoDeclaracion::Iva)
            ->where('periodo', $periodo)
            ->first()
            ?->remanente_anterior_sat;

        return (string) ($segunSat ?? self::remanenteIvaCalculadoAnterior($periodo));
    }

    /**
     * Remanente que dejó calculado el período anterior, sin considerar lo
     * registrado según Declaraguate. "0.00" si ese período no se ha calculado.
     */
    public static function remanenteIvaCalculadoAnterior(string $periodo): string
    {
        $periodoAnterior = Carbon::createFromFormat('Y-m-d', $periodo.'-01')
            ->subMonthNoOverflow()
            ->format('Y-m');

        $remanente = self::query()
            ->where('tipo', TipoDeclaracion::Iva)
            ->where('periodo', $periodoAnterior)
            ->first()
            ?->remanente_credito;

        return (string) ($remanente ?? '0.00');
    }

    protected function casts(): array
    {
        return [
            'tipo' => TipoDeclaracion::class,
            'montos' => 'array',
            'remanente_credito' => 'decimal:2',
            'remanente_anterior_sat' => 'decimal:2',
        ];
    }
}
