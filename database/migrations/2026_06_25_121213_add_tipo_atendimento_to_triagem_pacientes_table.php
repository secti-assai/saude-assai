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
        Schema::table('triagem_pacientes', function (Blueprint $table) {
            $table->string('tipo_atendimento')->nullable()->after('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('triagem_pacientes', function (Blueprint $table) {
            $table->dropColumn('tipo_atendimento');
        });
    }
};
