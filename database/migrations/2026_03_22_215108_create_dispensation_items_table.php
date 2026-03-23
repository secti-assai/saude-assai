<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dispensation_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('dispensation_id');
            $table->unsignedBigInteger('medication_id');
            $table->string('batch')->nullable();
            $table->date('expiry_date')->nullable();
            $table->integer('quantity')->default(1);
            $table->timestamps();

            $table->index('dispensation_id');
            $table->index('medication_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dispensation_items');
    }
};
