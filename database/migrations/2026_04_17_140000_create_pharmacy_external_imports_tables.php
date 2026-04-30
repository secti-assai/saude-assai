<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pharmacy_external_imports', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignId('uploaded_by_user_id')->constrained('users')->cascadeOnDelete();
            $table->string('betha_filename');
            $table->string('daily_filename');
            $table->string('betha_sha256', 64);
            $table->string('daily_sha256', 64);
            $table->timestamp('imported_at')->nullable();
            $table->json('stats')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['uploaded_by_user_id', 'created_at'], 'pharmacy_external_imports_uploader_created_idx');
        });

        Schema::create('pharmacy_external_import_rows', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('import_id');
            $table->foreign('import_id', 'pharmacy_external_import_rows_import_fk')
                ->references('id')
                ->on('pharmacy_external_imports')
                ->cascadeOnDelete();

            $table->string('row_hash', 64)->unique();
            $table->string('external_dispense_number')->nullable();
            $table->timestamp('dispensed_at')->nullable();

            $table->string('customer_name_raw')->nullable();
            $table->string('customer_name_normalized')->nullable();
            $table->string('pharmacist_name_raw')->nullable();
            $table->string('pharmacist_name_normalized')->nullable();

            $table->foreignId('pharmacist_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('citizen_id')->nullable()->constrained('citizens')->nullOnDelete();
            $table->foreignUuid('central_pharmacy_request_id')->nullable()->constrained('central_pharmacy_requests')->nullOnDelete();

            $table->string('medication_name_raw')->nullable();
            $table->unsignedInteger('quantity')->nullable();
            $table->boolean('bypass_detected')->default(false);

            $table->unsignedInteger('recurrence_interval_days')->nullable();
            $table->string('recurrence_alert_level', 20)->nullable();
            $table->json('payload')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['external_dispense_number'], 'pharmacy_external_import_rows_dispense_idx');
            $table->index(['dispensed_at'], 'pharmacy_external_import_rows_dispensed_at_idx');
            $table->index(['citizen_id', 'bypass_detected'], 'pharmacy_external_import_rows_citizen_bypass_idx');
            $table->index(['recurrence_alert_level'], 'pharmacy_external_import_rows_alert_level_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pharmacy_external_import_rows');
        Schema::dropIfExists('pharmacy_external_imports');
    }
};
