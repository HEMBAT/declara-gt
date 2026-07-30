<?php

namespace App\Models;

use App\Enums\Direccion;
use App\Enums\TipoDocumento;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'numero_autorizacion', 'fecha_emision', 'periodo', 'tipo_dte_id',
    'serie', 'numero', 'cliente_id', 'direccion', 'gran_total', 'iva',
    'base_sin_iva', 'idp', 'tipo', 'genera_credito', 'moneda', 'estado', 'anulado',
])]
class Documento extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'fecha_emision' => 'date',
            'gran_total' => 'decimal:2',
            'iva' => 'decimal:2',
            'base_sin_iva' => 'decimal:2',
            'idp' => 'decimal:2',
            'direccion' => Direccion::class,
            'tipo' => TipoDocumento::class,
            'anulado' => 'boolean',
            'genera_credito' => 'boolean',
        ];
    }

    public function tipoDte(): BelongsTo
    {
        return $this->belongsTo(TipoDte::class);
    }

    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class);
    }

    /**
     * Documentos que entran al cálculo del ISR Opcional Simplificado del período:
     * emitidos, vigentes, no anulados y en GTQ.
     */
    #[Scope]
    protected function paraCalculoIsr(Builder $query, string $periodo): void
    {
        $query->where('periodo', $periodo)
            ->where('direccion', Direccion::Emitida)
            ->where('estado', 'Vigente')
            ->where('anulado', false)
            ->where('moneda', 'GTQ');
    }

    /**
     * Documentos que entran al débito fiscal del IVA general del período:
     * emitidos, vigentes, no anulados y en GTQ.
     */
    #[Scope]
    protected function paraDebitoIva(Builder $query, string $periodo): void
    {
        $query->where('periodo', $periodo)
            ->where('direccion', Direccion::Emitida)
            ->where('estado', 'Vigente')
            ->where('anulado', false)
            ->where('moneda', 'GTQ');
    }

    /**
     * Documentos que entran al crédito fiscal del IVA general del período:
     * recibidos, vigentes, no anulados, en GTQ y marcados genera_credito.
     *
     * Las FPEQ (facturas de Pequeño Contribuyente) se excluyen siempre, sin
     * importar el valor de genera_credito: ese régimen paga un 5% fijo sobre
     * ingresos en vez de trasladar IVA, así que sus facturas nunca generan
     * crédito fiscal para quien las recibe — es una regla de la ley, no una
     * preferencia editable por el usuario.
     */
    #[Scope]
    protected function paraCreditoIva(Builder $query, string $periodo): void
    {
        $query->where('periodo', $periodo)
            ->where('direccion', Direccion::Recibida)
            ->where('estado', 'Vigente')
            ->where('anulado', false)
            ->where('moneda', 'GTQ')
            ->where('genera_credito', true)
            ->whereRelation('tipoDte', 'codigo', '!=', 'FPEQ');
    }

    /**
     * Documentos recibidos vigentes del período en GTQ, sin filtrar por
     * genera_credito — a diferencia de paraCreditoIva, incluye también los
     * que no generan crédito. Los usa el desglose del SAT-2237 para calcular
     * la base informativa de compras no deducibles (§11 del brief).
     */
    #[Scope]
    protected function paraRecibidasIva(Builder $query, string $periodo): void
    {
        $query->where('periodo', $periodo)
            ->where('direccion', Direccion::Recibida)
            ->where('estado', 'Vigente')
            ->where('anulado', false)
            ->where('moneda', 'GTQ');
    }
}
