<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Registro de cada intento de envío al webhook (también los fallidos y las pruebas).
     */
    public function up(): void
    {
        Schema::create('webhook_deliveries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('landing_id')->constrained()->cascadeOnDelete();
            // Nulo en los envíos de prueba (no hay lead real).
            $table->foreignId('lead_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('event', 40);                         // lead.created | webhook.test
            $table->string('url', 500);                          // a dónde se envió
            $table->boolean('success')->default(false);
            $table->unsignedSmallInteger('status_code')->nullable(); // código HTTP de la respuesta
            $table->text('response_body')->nullable();           // respuesta recortada a 1000 caracteres
            $table->string('error', 500)->nullable();            // motivo del fallo, si lo hubo
            $table->unsignedInteger('duration_ms')->default(0);  // cuánto tardó
            $table->timestamps();

            $table->index(['landing_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('webhook_deliveries');
    }
};
