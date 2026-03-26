<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('calls', function (Blueprint $table) {
            $table->id();

            $table->foreignId('attendance_id')->constrained()->cascadeOnDelete();

            $table->string('type'); // TRIAGEM | ATENDIMENTO
            $table->string('room')->nullable();

            $table->timestamp('called_at')->nullable();
            $table->timestamp('finished_at')->nullable();

            $table->string('status')->default('AGUARDANDO');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('calls');
    }
};
