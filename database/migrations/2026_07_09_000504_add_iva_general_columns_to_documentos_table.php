<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('documentos', function (Blueprint $table) {
            // Columna "Petróleo" del XLS del SAT (IDP). No lleva IVA ni genera crédito.
            $table->decimal('idp', 12, 2)->default(0)->after('base_sin_iva');
            // Solo aplica cuando direccion=recibida; null para emitidas.
            $table->boolean('genera_credito')->nullable()->after('tipo');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('documentos', function (Blueprint $table) {
            $table->dropColumn(['idp', 'genera_credito']);
        });
    }
};
