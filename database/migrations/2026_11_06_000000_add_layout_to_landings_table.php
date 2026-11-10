<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Añade el campo 'layout' (diseño de la landing en JSON) a la tabla landings.
     */
    public function up(): void
    {
        Schema::table('landings', function (Blueprint $table) {
            $table->longText('layout')->nullable()->after('status');
        });
    }

    public function down(): void
    {
        Schema::table('landings', function (Blueprint $table) {
            $table->dropColumn('layout');
        });
    }
};
