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
        Schema::create('retenciones', function (Blueprint $table) {
            $table->id();
            $table->string('periodo', 7);
            $table->decimal('monto_isr', 12, 2);
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
        Schema::dropIfExists('retenciones');
    }
};
