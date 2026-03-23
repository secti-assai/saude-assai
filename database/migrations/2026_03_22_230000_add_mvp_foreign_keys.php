<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('attendances', function (Blueprint $table) {
            $table->foreign('citizen_id')->references('id')->on('citizens')->cascadeOnDelete();
            $table->foreign('health_unit_id')->references('id')->on('health_units')->cascadeOnDelete();
            $table->foreign('reception_user_id')->references('id')->on('users')->nullOnDelete();
        });

        Schema::table('prescriptions', function (Blueprint $table) {
            $table->foreign('attendance_id')->references('id')->on('attendances')->nullOnDelete();
            $table->foreign('citizen_id')->references('id')->on('citizens')->cascadeOnDelete();
            $table->foreign('doctor_user_id')->references('id')->on('users')->nullOnDelete();
        });

        Schema::table('dispensations', function (Blueprint $table) {
            $table->foreign('prescription_id')->references('id')->on('prescriptions')->cascadeOnDelete();
            $table->foreign('citizen_id')->references('id')->on('citizens')->cascadeOnDelete();
            $table->foreign('pharmacist_user_id')->references('id')->on('users')->nullOnDelete();
        });

        Schema::table('prescription_items', function (Blueprint $table) {
            $table->foreign('prescription_id')->references('id')->on('prescriptions')->cascadeOnDelete();
            $table->foreign('medication_id')->references('id')->on('medications')->cascadeOnDelete();
        });

        Schema::table('dispensation_items', function (Blueprint $table) {
            $table->foreign('dispensation_id')->references('id')->on('dispensations')->cascadeOnDelete();
            $table->foreign('medication_id')->references('id')->on('medications')->cascadeOnDelete();
        });

        Schema::table('ledi_logs', function (Blueprint $table) {
            $table->foreign('ledi_queue_id')->references('id')->on('ledi_queues')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('ledi_logs', function (Blueprint $table) {
            $table->dropForeign(['ledi_queue_id']);
        });

        Schema::table('dispensation_items', function (Blueprint $table) {
            $table->dropForeign(['dispensation_id']);
            $table->dropForeign(['medication_id']);
        });

        Schema::table('prescription_items', function (Blueprint $table) {
            $table->dropForeign(['prescription_id']);
            $table->dropForeign(['medication_id']);
        });

        Schema::table('dispensations', function (Blueprint $table) {
            $table->dropForeign(['prescription_id']);
            $table->dropForeign(['citizen_id']);
            $table->dropForeign(['pharmacist_user_id']);
        });

        Schema::table('prescriptions', function (Blueprint $table) {
            $table->dropForeign(['attendance_id']);
            $table->dropForeign(['citizen_id']);
            $table->dropForeign(['doctor_user_id']);
        });

        Schema::table('attendances', function (Blueprint $table) {
            $table->dropForeign(['citizen_id']);
            $table->dropForeign(['health_unit_id']);
            $table->dropForeign(['reception_user_id']);
        });
    }
};
