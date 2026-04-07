<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('women_clinic_appointments', function (Blueprint $table) {
            $table->timestamp('cancelled_at')->nullable()->after('checked_out_at');
            $table->timestamp('reminder_24h_sent_at')->nullable()->after('cancelled_at');
            $table->unsignedTinyInteger('feedback_score')->nullable()->after('reminder_24h_sent_at');
            $table->text('feedback_comment')->nullable()->after('feedback_score');
            $table->timestamp('feedback_submitted_at')->nullable()->after('feedback_comment');
        });
    }

    public function down(): void
    {
        Schema::table('women_clinic_appointments', function (Blueprint $table) {
            $table->dropColumn([
                'cancelled_at',
                'reminder_24h_sent_at',
                'feedback_score',
                'feedback_comment',
                'feedback_submitted_at',
            ]);
        });
    }
};
