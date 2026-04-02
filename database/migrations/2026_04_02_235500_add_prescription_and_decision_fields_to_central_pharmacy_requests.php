<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('central_pharmacy_requests', function (Blueprint $table) {
            $table->date('prescription_date')->nullable()->after('prescription_code');
            $table->string('prescriber_name')->nullable()->after('prescription_date');
            $table->string('concentration')->nullable()->after('medication_name');
            $table->text('dosage')->nullable()->after('quantity');

            $table->text('refusal_reason')->nullable()->after('notes');
            $table->string('equivalent_medication_name')->nullable()->after('refusal_reason');
            $table->string('equivalent_concentration')->nullable()->after('equivalent_medication_name');
        });
    }

    public function down(): void
    {
        Schema::table('central_pharmacy_requests', function (Blueprint $table) {
            $table->dropColumn([
                'prescription_date',
                'prescriber_name',
                'concentration',
                'dosage',
                'refusal_reason',
                'equivalent_medication_name',
                'equivalent_concentration',
            ]);
        });
    }
};
