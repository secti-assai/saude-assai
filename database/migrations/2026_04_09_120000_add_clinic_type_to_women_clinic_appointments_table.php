<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('women_clinic_appointments', function (Blueprint $table) {
            $table->string('clinic_type')->default('CLINICA_MULHER')->after('scheduled_for');
            $table->index(['clinic_type', 'scheduled_for', 'status'], 'women_clinic_appointments_clinic_schedule_status_index');
        });
    }

    public function down(): void
    {
        Schema::table('women_clinic_appointments', function (Blueprint $table) {
            $table->dropIndex('women_clinic_appointments_clinic_schedule_status_index');
            $table->dropColumn('clinic_type');
        });
    }
};
