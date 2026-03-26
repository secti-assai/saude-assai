<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public $withinTransaction = false;

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        try {
            DB::statement('CREATE EXTENSION IF NOT EXISTS unaccent');
        } catch (\Exception $e) {
            DB::statement('CREATE OR REPLACE FUNCTION unaccent(text) RETURNS text AS $$ SELECT $1; $$ LANGUAGE SQL IMMUTABLE;');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        try {
            DB::statement('DROP EXTENSION IF EXISTS unaccent');
        } catch (\Exception $e) {
            DB::statement('DROP FUNCTION IF EXISTS unaccent(text)');
        }
    }
};
