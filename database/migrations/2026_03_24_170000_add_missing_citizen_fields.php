<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('citizens', function (Blueprint $table) {
            $table->char('sexo', 1)->nullable()->after('birth_date');
            $table->smallInteger('raca_cor')->nullable()->after('genero');
            $table->uuid('gov_assai_id')->nullable()->after('raca_cor');
        });

        // Renomear gender (CHAR 1) → genero (VARCHAR 50) para identidade de gênero
        Schema::table('citizens', function (Blueprint $table) {
            $table->renameColumn('gender', 'genero');
        });

        if (DB::getDriverName() !== 'sqlite') {
            Schema::table('citizens', function (Blueprint $table) {
                $table->string('genero', 50)->nullable()->change();
            });
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'sqlite') {
            Schema::table('citizens', function (Blueprint $table) {
                $table->string('genero', 1)->nullable()->change();
            });
        }

        Schema::table('citizens', function (Blueprint $table) {
            $table->renameColumn('genero', 'gender');
        });

        Schema::table('citizens', function (Blueprint $table) {
            $table->dropColumn(['sexo', 'raca_cor', 'gov_assai_id']);
        });
    }
};
