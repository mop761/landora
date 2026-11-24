<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Datos de origen de cada lead: de qué campaña viene (UTM), IP y navegador.
     * Se usarán en la analítica de leads.
     */
    public function up(): void
    {
        Schema::table('leads', function (Blueprint $table) {
            $table->string('utm_source', 100)->nullable()->after('data');
            $table->string('utm_medium', 100)->nullable()->after('utm_source');
            $table->string('utm_campaign', 100)->nullable()->after('utm_medium');
            $table->string('ip', 45)->nullable()->after('utm_campaign');
            $table->string('user_agent', 255)->nullable()->after('ip');
        });
    }

    public function down(): void
    {
        Schema::table('leads', function (Blueprint $table) {
            $table->dropColumn(['utm_source', 'utm_medium', 'utm_campaign', 'ip', 'user_agent']);
        });
    }
};
