<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('deliveries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('prescription_id')->constrained()->cascadeOnDelete();
            $table->foreignId('delivery_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('status')->default('PENDENTE');
            $table->string('address')->nullable();
            $table->string('gps_lat')->nullable();
            $table->string('gps_lng')->nullable();
            $table->string('failure_reason')->nullable();
            $table->text('signature_data')->nullable();
            $table->timestamp('confirmed_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('deliveries');
    }
};
