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
        Schema::create('declaraciones', function (Blueprint $table) {
            $table->id();
            $table->enum('tipo', ['ISR', 'IVA']);
            $table->string('periodo', 7);
            $table->json('montos');
            $table->decimal('remanente_credito', 12, 2)->nullable();
            $table->string('estado', 20)->default('pendiente');
            $table->timestamps();

            $table->unique(['tipo', 'periodo']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('declaraciones');
    }
};
