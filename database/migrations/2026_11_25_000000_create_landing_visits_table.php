<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Visitas a las landings publicadas. Es la base de la analítica (tasa de conversión).
     * No se guarda la IP: solo una huella anónima que cambia cada día.
     */
    public function up(): void
    {
        Schema::create('landing_visits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('landing_id')->constrained()->cascadeOnDelete();
            $table->char('visitor_hash', 64);                  // huella anónima del visitante (SHA-256)
            $table->string('utm_source', 100)->nullable();     // de qué campaña viene la visita
            $table->string('utm_medium', 100)->nullable();
            $table->string('utm_campaign', 100)->nullable();
            $table->string('referrer', 100)->nullable();       // solo el dominio de procedencia
            $table->string('device', 10);                      // mobile | desktop
            $table->timestamp('created_at')->useCurrent();

            // La analítica siempre filtra por landing y rango de fechas.
            $table->index(['landing_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('landing_visits');
    }
};
