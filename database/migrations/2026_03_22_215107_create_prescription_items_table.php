<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('prescription_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('prescription_id');
            $table->unsignedBigInteger('medication_id');
            $table->string('dosage')->nullable();
            $table->string('frequency')->nullable();
            $table->string('administration_route')->nullable();
            $table->integer('duration_days')->default(1);
            $table->integer('quantity')->default(1);
            $table->timestamps();

            $table->index('prescription_id');
            $table->index('medication_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('prescription_items');
    }
};
