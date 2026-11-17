<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Crea la tabla de leads (contactos que dejan los visitantes
     * a través del formulario de una landing).
     */
    public function up(): void
    {
        Schema::create('leads', function (Blueprint $table) {
            $table->id();
            // Cada lead pertenece a una landing; si se borra la landing, se borran sus leads.
            $table->foreignId('landing_id')->constrained()->cascadeOnDelete();
            // Los datos enviados en el formulario (campos variables) se guardan como JSON.
            $table->json('data');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('leads');
    }
};
