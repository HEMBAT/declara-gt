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
        Schema::create('parametros_impuesto', function (Blueprint $table) {
            $table->id();
            $table->enum('tipo', ['IVA', 'ISR_TRAMO']);
            $table->decimal('tasa', 6, 4);
            $table->decimal('limite_inferior', 12, 2)->nullable();
            $table->decimal('limite_superior', 12, 2)->nullable();
            $table->date('vigente_desde');
            $table->date('vigente_hasta')->nullable();
            $table->timestamps();

            $table->index(['tipo', 'vigente_desde', 'vigente_hasta']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('parametros_impuesto');
    }
};
