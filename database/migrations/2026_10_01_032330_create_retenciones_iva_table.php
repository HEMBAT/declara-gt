<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Constancias de retención de IVA que recibe el contribuyente cuando un
     * agente de retención le paga. Separadas de `retenciones` (ISR) porque
     * se acreditan contra otro impuesto y su saldo se arrastra mes a mes.
     */
    public function up(): void
    {
        Schema::create('retenciones_iva', function (Blueprint $table) {
            $table->id();
            $table->string('periodo', 7);
            $table->decimal('monto', 12, 2);
            $table->string('descripcion')->nullable();
            $table->timestamps();

            $table->index('periodo');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('retenciones_iva');
    }
};
