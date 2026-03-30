<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('central_pharmacy_requests', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignId('citizen_id')->constrained('citizens')->cascadeOnDelete();
            $table->foreignId('reception_user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('attendant_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('prescription_code')->nullable();
            $table->string('medication_name');
            $table->unsignedInteger('quantity');
            $table->string('gov_assai_level')->nullable();
            $table->string('residence_status')->default('PENDENTE');
            $table->string('status')->default('RECEPCAO_VALIDADA');
            $table->text('notes')->nullable();
            $table->timestamp('dispensed_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['status', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('central_pharmacy_requests');
    }
};
