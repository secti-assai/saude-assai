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
        Schema::table('deliveries', function (Blueprint $table) {
            // Remove as colunas antigas e pesadas
            $table->dropColumn(['gps_lat', 'gps_lng', 'signature_data']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('deliveries', function (Blueprint $table) {
            // Caso precise reverter (rollback), recria as colunas antigas
            $table->string('gps_lat')->nullable();
            $table->string('gps_lng')->nullable();
            $table->text('signature_data')->nullable();
        });
    }
};