<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('triages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('attendance_id')->constrained()->cascadeOnDelete();
            $table->foreignId('nurse_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('nursing_history')->nullable();
            $table->json('comorbidities')->nullable();
            $table->string('consciousness_level')->default('LUCIDO');
            $table->integer('systolic_pressure')->nullable();
            $table->integer('diastolic_pressure')->nullable();
            $table->decimal('temperature', 4, 1)->nullable();
            $table->integer('heart_rate')->nullable();
            $table->integer('spo2')->nullable();
            $table->integer('hgt')->nullable();
            $table->decimal('weight', 5, 2)->nullable();
            $table->string('risk_color')->default('AZUL');
            $table->string('risk_classification')->default('NAO_URGENTE');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('triages');
    }
};
