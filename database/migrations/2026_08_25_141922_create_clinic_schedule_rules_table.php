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
        Schema::create('clinic_schedule_rules', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('clinic_type');
            $table->string('specialty');
            $table->tinyInteger('day_of_week')->comment('0=Sun, 1=Mon, ..., 6=Sat');
            $table->json('weeks_of_month')->comment('Array like [1,2,3,4,5]');
            $table->time('time');
            $table->integer('capacity')->default(1);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            // Index for faster queries
            $table->index(['clinic_type', 'specialty', 'day_of_week', 'time']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('clinic_schedule_rules');
    }
};
