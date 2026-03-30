<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('women_clinic_appointments', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignId('citizen_id')->constrained('citizens')->cascadeOnDelete();
            $table->foreignId('scheduler_user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('reception_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('doctor_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('scheduled_for');
            $table->string('specialty')->default('CLINICA_DA_MULHER');
            $table->string('gov_assai_level')->nullable();
            $table->string('residence_status')->default('PENDENTE');
            $table->string('status')->default('AGENDADO');
            $table->text('notes')->nullable();
            $table->timestamp('checked_in_at')->nullable();
            $table->timestamp('checked_out_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['scheduled_for', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('women_clinic_appointments');
    }
};
