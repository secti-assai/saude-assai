<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('citizens', 'cpf_hash')) {
            Schema::table('citizens', function (Blueprint $table) {
                $table->string('cpf_hash', 64)->nullable()->after('cpf');
            });
        }

        $driver = DB::getDriverName();

        if ($driver === 'pgsql') {
            DB::statement('ALTER TABLE citizens DROP CONSTRAINT IF EXISTS citizens_cpf_unique');
            DB::statement('ALTER TABLE citizens ALTER COLUMN cpf TYPE text');
            DB::statement('ALTER TABLE citizens ALTER COLUMN cns TYPE text');
            DB::statement('ALTER TABLE citizens ALTER COLUMN email TYPE text');
            DB::statement('CREATE UNIQUE INDEX IF NOT EXISTS citizens_cpf_hash_unique ON citizens (cpf_hash) WHERE deleted_at IS NULL');

            return;
        }

        try {
            Schema::table('citizens', function (Blueprint $table) {
                $table->dropUnique('citizens_cpf_unique');
            });
        } catch (Throwable) {
            // ignore when the unique does not exist for the current driver
        }

        try {
            Schema::table('citizens', function (Blueprint $table) {
                $table->unique('cpf_hash', 'citizens_cpf_hash_unique');
            });
        } catch (Throwable) {
            // ignore when the unique already exists
        }
    }

    public function down(): void
    {
        $driver = DB::getDriverName();

        if ($driver === 'pgsql') {
            DB::statement('DROP INDEX IF EXISTS citizens_cpf_hash_unique');
            DB::statement('ALTER TABLE citizens ADD CONSTRAINT citizens_cpf_unique UNIQUE (cpf)');
        } else {
            try {
                Schema::table('citizens', function (Blueprint $table) {
                    $table->dropUnique('citizens_cpf_hash_unique');
                });
            } catch (Throwable) {
                // ignore when the unique does not exist
            }

            try {
                Schema::table('citizens', function (Blueprint $table) {
                    $table->unique('cpf', 'citizens_cpf_unique');
                });
            } catch (Throwable) {
                // ignore when the unique already exists
            }
        }

        if (Schema::hasColumn('citizens', 'cpf_hash')) {
            Schema::table('citizens', function (Blueprint $table) {
                $table->dropColumn('cpf_hash');
            });
        }
    }
};
