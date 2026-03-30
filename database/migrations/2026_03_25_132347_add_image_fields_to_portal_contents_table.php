<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('portal_contents', function (Blueprint $table) {
            $table->string('cover_image')->nullable()->after('body');
            $table->string('excerpt')->nullable()->after('type'); // Um resumo curto pra não quebrar o layout
        });
    }

    public function down(): void
    {
        Schema::table('portal_contents', function (Blueprint $table) {
            $table->dropColumn(['cover_image', 'excerpt']);
        });
    }
};