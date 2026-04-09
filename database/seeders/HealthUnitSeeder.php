<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use App\Models\HealthUnit;
use Illuminate\Database\Seeder;

class HealthUnitSeeder extends Seeder
{
    public function run(): void
    {
        $units = [
            ['name' => 'Clinica da Mulher', 'kind' => 'CLINICA_MULHER', 'code' => 'CLM-01', 'address' => 'Av. Principal, 120'],
            ['name' => 'Policlinica', 'kind' => 'POLICLINICA', 'code' => 'PLC-01', 'address' => 'Rua das Especialidades, 90'],
            ['name' => 'UBS Central', 'kind' => 'UBS', 'code' => 'UBS-01', 'address' => 'Rua A, 100'],
            ['name' => 'UBS Vila Nova', 'kind' => 'UBS', 'code' => 'UBS-02', 'address' => 'Rua B, 220'],
            ['name' => 'Farmacia Central', 'kind' => 'FARMACIA', 'code' => 'FAR-01', 'address' => 'Rua C, 50'],
            ['name' => 'Hospital Municipal', 'kind' => 'HOSPITAL', 'code' => 'HSP-01', 'address' => 'Av. Principal, 10'],
        ];

        foreach ($units as $unit) {
            HealthUnit::updateOrCreate(['code' => $unit['code']], $unit + ['is_active' => true]);
        }
    }
}
