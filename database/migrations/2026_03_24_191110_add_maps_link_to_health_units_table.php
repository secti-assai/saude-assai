<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('health_units', function (Blueprint $table) {
            $table->string('maps_link')->nullable()->after('address');
        });
    }

    public function down(): void
    {
        Schema::table('health_units', function (Blueprint $table) {
            $table->dropColumn('maps_link');
        });
    }
};
