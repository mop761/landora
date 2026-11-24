<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Añade a cada landing la URL de su webhook y la clave secreta con la que se firman los envíos.
     */
    public function up(): void
    {
        Schema::table('landings', function (Blueprint $table) {
            $table->string('webhook_url', 500)->nullable()->after('layout');
            $table->string('webhook_secret', 64)->nullable()->after('webhook_url');
        });
    }

    public function down(): void
    {
        Schema::table('landings', function (Blueprint $table) {
            $table->dropColumn(['webhook_url', 'webhook_secret']);
        });
    }
};
