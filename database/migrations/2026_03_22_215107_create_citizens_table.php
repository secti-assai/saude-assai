<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('citizens', function (Blueprint $table) {
            $table->id();
            $table->string('cpf', 11)->unique();
            $table->string('cns', 15)->nullable();
            $table->string('full_name');
            $table->string('social_name')->nullable();
            $table->date('birth_date');
            $table->string('gender', 1)->nullable();
            $table->string('address')->nullable();
            $table->boolean('is_resident_assai')->default(false);
            $table->timestamp('residence_validated_at')->nullable();
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('citizens');
    }
};
