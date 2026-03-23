<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use App\Models\Medication;
use Illuminate\Database\Seeder;

class MedicationSeeder extends Seeder
{
    public function run(): void
    {
        $items = [
            ['code' => 'REM-001', 'name' => 'Dipirona', 'presentation' => 'Comprimido', 'concentration' => '500mg'],
            ['code' => 'REM-002', 'name' => 'Losartana', 'presentation' => 'Comprimido', 'concentration' => '50mg'],
            ['code' => 'REM-003', 'name' => 'Metformina', 'presentation' => 'Comprimido', 'concentration' => '850mg'],
            ['code' => 'REM-004', 'name' => 'Amoxicilina', 'presentation' => 'Capsula', 'concentration' => '500mg'],
        ];

        foreach ($items as $item) {
            Medication::updateOrCreate(['code' => $item['code']], $item + ['is_remume' => true]);
        }
    }
}
