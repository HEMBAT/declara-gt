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
     * emitidos, vigentes, no anulados, en GTQ y de un tipo de DTE que cuenta
     * para el cálculo (sin notas de abono).
     */
    #[Scope]
    protected function paraCalculoIsr(Builder $query, string $periodo): void
    {
        $query->where('periodo', $periodo)
            ->where('direccion', Direccion::Emitida)
            ->where('estado', 'Vigente')
            ->where('anulado', false)
            ->where('moneda', 'GTQ')
            ->whereHas('tipoDte', fn (Builder $tipoDte) => $tipoDte->whereNotIn('codigo', TipoDte::CODIGOS_FUERA_DE_CALCULO));
    }

    /**
     * Documentos que entran al débito fiscal del IVA general del período:
     * emitidos, vigentes, no anulados, en GTQ y sin notas de abono.
     */
    #[Scope]
    protected function paraDebitoIva(Builder $query, string $periodo): void
    {
        $query->where('periodo', $periodo)
            ->where('direccion', Direccion::Emitida)
            ->where('estado', 'Vigente')
            ->where('anulado', false)
            ->where('moneda', 'GTQ')
            ->whereHas('tipoDte', fn (Builder $tipoDte) => $tipoDte->whereNotIn('codigo', TipoDte::CODIGOS_FUERA_DE_CALCULO));
    }

    /**
     * Documentos que entran al crédito fiscal del IVA general del período:
     * recibidos, vigentes, no anulados, en GTQ, sin notas de abono y marcados
     * genera_credito.
     *
     * Las facturas de Pequeño Contribuyente (FPEQ y FCAP) se excluyen siempre,
     * sin importar el valor de genera_credito: ese régimen paga un 5% fijo
     * sobre ingresos en vez de trasladar IVA, así que sus facturas nunca
     * generan crédito fiscal para quien las recibe — es una regla de la ley,
     * no una preferencia editable por el usuario.
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
            ->whereHas('tipoDte', fn (Builder $tipoDte) => $tipoDte->whereNotIn(
                'codigo',
                [...TipoDte::CODIGOS_PEQUENO_CONTRIBUYENTE, ...TipoDte::CODIGOS_FUERA_DE_CALCULO],
            ));
    }

    /**
     * Documentos recibidos vigentes del período en GTQ, sin filtrar por
     * genera_credito — a diferencia de paraCreditoIva, incluye también los
     * que no generan crédito (pero no las notas de abono). Los usa el desglose
     * del SAT-2237 para calcular la base informativa de compras no deducibles
     * (§11 del brief).
     */
    #[Scope]
    protected function paraRecibidasIva(Builder $query, string $periodo): void
    {
        $query->where('periodo', $periodo)
            ->where('direccion', Direccion::Recibida)
            ->where('estado', 'Vigente')
            ->where('anulado', false)
            ->where('moneda', 'GTQ')
            ->whereHas('tipoDte', fn (Builder $tipoDte) => $tipoDte->whereNotIn('codigo', TipoDte::CODIGOS_FUERA_DE_CALCULO));
    }
}
