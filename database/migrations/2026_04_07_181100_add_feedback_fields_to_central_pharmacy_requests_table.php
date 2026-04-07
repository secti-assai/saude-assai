<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('central_pharmacy_requests', function (Blueprint $table) {
            $table->unsignedTinyInteger('feedback_score')->nullable()->after('equivalent_concentration');
            $table->text('feedback_comment')->nullable()->after('feedback_score');
            $table->timestamp('feedback_submitted_at')->nullable()->after('feedback_comment');
        });
    }

    public function down(): void
    {
        Schema::table('central_pharmacy_requests', function (Blueprint $table) {
            $table->dropColumn([
                'feedback_score',
                'feedback_comment',
                'feedback_submitted_at',
            ]);
        });
    }
};
