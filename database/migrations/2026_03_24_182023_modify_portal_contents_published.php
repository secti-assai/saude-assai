<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table("portal_contents", function (Blueprint $table) {
            if(!Schema::hasColumn("portal_contents", "published")){
                $table->boolean("published")->default(true)->after("body");
            }
        });
    }

    public function down(): void
    {
        Schema::table("portal_contents", function (Blueprint $table) {
            $table->dropColumn("published");
        });
    }
};
