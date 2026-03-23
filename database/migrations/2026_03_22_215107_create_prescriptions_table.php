<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('prescriptions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('attendance_id')->nullable();
            $table->unsignedBigInteger('citizen_id');
            $table->unsignedBigInteger('doctor_user_id')->nullable();
            $table->string('delivery_type')->default('RETIRADA');
            $table->string('status')->default('PENDENTE');
            $table->text('notes')->nullable();
            $table->timestamp('signed_at')->nullable();
            $table->timestamps();

            $table->index('attendance_id');
            $table->index('citizen_id');
            $table->index('doctor_user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('prescriptions');
    }
};
