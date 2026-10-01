<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Saldo de retenciones de IVA del SAT-2237 (cuadro 7).
     *
     * - remanente_retenciones: saldo calculado para el período siguiente
     *   (entrada del siguiente, igual que remanente_credito).
     * - remanente_retenciones_anterior_sat: el remanente anterior tal como lo
     *   muestra Declaraguate; cuando existe, manda sobre el encadenado.
     * - acreditamiento_retenciones / resolucion_acreditamiento: devolución del
     *   remanente que el SAT acreditó en cuenta bancaria en este período.
     *
     * Solo remanente_retenciones lo escribe el cálculo; los demás, el usuario.
     */
    public function up(): void
    {
        Schema::table('declaraciones', function (Blueprint $table) {
            $table->decimal('remanente_retenciones', 12, 2)->nullable()->after('remanente_anterior_sat');
            $table->decimal('remanente_retenciones_anterior_sat', 12, 2)->nullable()->after('remanente_retenciones');
            $table->decimal('acreditamiento_retenciones', 12, 2)->nullable()->after('remanente_retenciones_anterior_sat');
            $table->string('resolucion_acreditamiento', 50)->nullable()->after('acreditamiento_retenciones');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('declaraciones', function (Blueprint $table) {
            $table->dropColumn([
                'remanente_retenciones',
                'remanente_retenciones_anterior_sat',
                'acreditamiento_retenciones',
                'resolucion_acreditamiento',
            ]);
        });
    }
};
