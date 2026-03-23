<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attendances', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('citizen_id');
            $table->unsignedBigInteger('health_unit_id');
            $table->unsignedBigInteger('reception_user_id')->nullable();
            $table->string('care_type');
            $table->string('queue_password')->nullable();
            $table->string('priority_color')->default('AZUL');
            $table->string('residence_status')->default('PENDENTE');
            $table->string('summary_reason', 200)->nullable();
            $table->boolean('work_accident')->default(false);
            $table->string('status')->default('RECEPCAO');
            $table->timestamp('arrived_at')->nullable();
            $table->timestamps();

            $table->index('citizen_id');
            $table->index('health_unit_id');
            $table->index('reception_user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attendances');
    }
};
