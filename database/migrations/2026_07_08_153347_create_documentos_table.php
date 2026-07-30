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
        Schema::create('documentos', function (Blueprint $table) {
            $table->id();
            $table->uuid('numero_autorizacion')->unique();
            $table->date('fecha_emision');
            $table->string('periodo', 7);
            $table->foreignId('tipo_dte_id')->constrained('tipos_dte')->restrictOnDelete();
            $table->string('serie')->nullable();
            $table->string('numero')->nullable();
            $table->foreignId('cliente_id')->constrained('clientes')->restrictOnDelete();
            $table->enum('direccion', ['emitida', 'recibida']);
            $table->decimal('gran_total', 12, 2);
            $table->decimal('iva', 12, 2);
            $table->decimal('base_sin_iva', 12, 2);
            $table->enum('tipo', ['bien', 'servicio', 'combustible'])->nullable();
            $table->string('moneda', 3)->default('GTQ');
            $table->string('estado', 20);
            $table->boolean('anulado')->default(false);
            $table->timestamps();

            $table->index('periodo');
            $table->index(['direccion', 'periodo']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('documentos');
    }
};
