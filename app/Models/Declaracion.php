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
 * Las columnas que escribe el usuario (los remanentes "según Declaraguate"
 * y el acreditamiento de retenciones) nunca las tocan los recálculos.
 */
#[Table('declaraciones')]
#[Fillable([
    'tipo', 'periodo', 'montos', 'remanente_credito', 'remanente_anterior_sat',
    'remanente_retenciones', 'remanente_retenciones_anterior_sat',
    'acreditamiento_retenciones', 'resolucion_acreditamiento', 'estado',
])]
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
        return self::delPeriodo($periodo)?->remanente_anterior_sat
            ?? self::remanenteIvaCalculadoAnterior($periodo);
    }

    /**
     * Remanente que dejó calculado el período anterior, sin considerar lo
     * registrado según Declaraguate. "0.00" si ese período no se ha calculado.
     */
    public static function remanenteIvaCalculadoAnterior(string $periodo): string
    {
        return self::delPeriodo(self::periodoAnterior($periodo))?->remanente_credito ?? '0.00';
    }

    /**
     * Remanente de retenciones de IVA con que arranca un período: el de
     * Declaraguate si el usuario lo registró, o el saldo que dejó calculado
     * el período anterior. Misma regla que remanenteIvaAnterior().
     */
    public static function remanenteRetencionesIvaAnterior(string $periodo): string
    {
        return self::delPeriodo($periodo)?->remanente_retenciones_anterior_sat
            ?? self::remanenteRetencionesIvaCalculadoAnterior($periodo);
    }

    public static function remanenteRetencionesIvaCalculadoAnterior(string $periodo): string
    {
        return self::delPeriodo(self::periodoAnterior($periodo))?->remanente_retenciones ?? '0.00';
    }

    /**
     * Declaración de IVA de un período, o null si aún no se ha calculado.
     */
    public static function delPeriodo(string $periodo): ?self
    {
        return self::query()
            ->where('tipo', TipoDeclaracion::Iva)
            ->where('periodo', $periodo)
            ->first();
    }

    private static function periodoAnterior(string $periodo): string
    {
        return Carbon::createFromFormat('Y-m-d', $periodo.'-01')
            ->subMonthNoOverflow()
            ->format('Y-m');
    }

    protected function casts(): array
    {
        return [
            'tipo' => TipoDeclaracion::class,
            'montos' => 'array',
            'remanente_credito' => 'decimal:2',
            'remanente_anterior_sat' => 'decimal:2',
            'remanente_retenciones' => 'decimal:2',
            'remanente_retenciones_anterior_sat' => 'decimal:2',
            'acreditamiento_retenciones' => 'decimal:2',
        ];
    }
}
