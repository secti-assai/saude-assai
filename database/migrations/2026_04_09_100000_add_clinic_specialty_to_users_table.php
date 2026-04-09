<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('clinic_specialty')->nullable()->after('crm');
            $table->index(['role', 'clinic_specialty'], 'users_role_clinic_specialty_index');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex('users_role_clinic_specialty_index');
            $table->dropColumn('clinic_specialty');
        });
    }
};
