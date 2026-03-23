<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dispensations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('prescription_id');
            $table->unsignedBigInteger('citizen_id');
            $table->unsignedBigInteger('pharmacist_user_id')->nullable();
            $table->string('residence_status')->default('PENDENTE');
            $table->boolean('blocked')->default(false);
            $table->text('justification')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->timestamps();

            $table->index('prescription_id');
            $table->index('citizen_id');
            $table->index('pharmacist_user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dispensations');
    }
};
