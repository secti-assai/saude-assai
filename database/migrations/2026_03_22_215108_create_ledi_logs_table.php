<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ledi_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('ledi_queue_id')->nullable();
            $table->string('status');
            $table->text('response')->nullable();
            $table->timestamps();

            $table->index('ledi_queue_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ledi_logs');
    }
};
