<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hospital_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('attendance_id')->constrained()->cascadeOnDelete();
            $table->foreignId('doctor_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('soap_objective')->nullable();
            $table->text('soap_assessment')->nullable();
            $table->text('diagnosis')->nullable();
            $table->string('cid_10')->nullable();
            $table->json('secondary_cids')->nullable();
            $table->text('procedures')->nullable();
            $table->text('exams')->nullable();
            $table->text('guidance')->nullable();
            $table->string('outcome')->default('ALTA');
            $table->timestamp('signed_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hospital_records');
    }
};
