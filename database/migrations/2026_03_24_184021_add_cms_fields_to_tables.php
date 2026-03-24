<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::table('health_units', function (Blueprint $table) {
            $table->string('photo_path')->nullable();
            $table->text('description')->nullable();
            
            if (!Schema::hasColumn('health_units', 'deleted_at')) {
                $table->softDeletes();
            }
        });
        
        Schema::table('portal_contents', function (Blueprint $table) {
            if (!Schema::hasColumn('portal_contents', 'deleted_at')) {
                $table->softDeletes();
            }
        });
    }

    public function down(): void {
        Schema::table('health_units', function (Blueprint $table) {
            $table->dropColumn(['photo_path', 'description', 'deleted_at']);
        });
        Schema::table('portal_contents', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });
    }
};
