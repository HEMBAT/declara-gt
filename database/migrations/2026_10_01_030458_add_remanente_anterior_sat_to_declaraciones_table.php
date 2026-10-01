<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Remanente de crédito del período anterior tal como lo muestra
     * Declaraguate. Cuando existe, manda sobre el remanente encadenado: el
     * SAT es la fuente de verdad (saldo previo al primer mes cargado,
     * redondeo a enteros de cada declaración).
     */
    public function up(): void
    {
        Schema::table('declaraciones', function (Blueprint $table) {
            $table->decimal('remanente_anterior_sat', 12, 2)->nullable()->after('remanente_credito');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('declaraciones', function (Blueprint $table) {
            $table->dropColumn('remanente_anterior_sat');
        });
    }
};
