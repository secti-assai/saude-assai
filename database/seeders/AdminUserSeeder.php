<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use App\Models\HealthUnit;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Database\Seeder;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        $unitId = HealthUnit::where('kind', 'UBS')->value('id');
        $hospitalId = HealthUnit::where('kind', 'HOSPITAL')->value('id');

        // Admin
        User::updateOrCreate(
            ['email' => 'admin@saudeassai.local'],
            [
                'name' => 'Administrador SECTI',
                'password' => Hash::make('password'),
                'role' => User::ROLE_ADMIN,
                'health_unit_id' => $unitId,
                'email_verified_at' => now(),
            ]
        );

        // Médico UBS
        User::updateOrCreate(
            ['email' => 'medico.ubs@saudeassai.local'],
            [
                'name' => 'Médico Clínico (UBS)',
                'password' => Hash::make('password'),
                'role' => User::ROLE_MEDICO_UBS,
                'health_unit_id' => $unitId,
                'email_verified_at' => now(),
            ]
        );

        // Médico Hospital (M7)
        User::updateOrCreate(
            ['email' => 'medico.hospital@saudeassai.local'],
            [
                'name' => 'Médico Plantonista (Hospital)',
                'password' => Hash::make('password'),
                'role' => User::ROLE_MEDICO_HOSPITAL,
                'health_unit_id' => $hospitalId,
                'email_verified_at' => now(),
            ]
        );

        // Farmacêutico (M6)
        User::updateOrCreate(
            ['email' => 'farmacia@saudeassai.local'],
            [
                'name' => 'Farmacêutico Responsável',
                'password' => Hash::make('password'),
                'role' => User::ROLE_FARMACIA,
                'health_unit_id' => $unitId,
                'email_verified_at' => now(),
            ]
        );

        // Enfermeiro Triagem (M4)
        User::updateOrCreate(
            ['email' => 'enfermeiro@saudeassai.local'],
            [
                'name' => 'Enfermeiro Especialista',
                'password' => Hash::make('password'),
                'role' => User::ROLE_ENFERMAGEM,
                'health_unit_id' => $unitId,
                'email_verified_at' => now(),
            ]
        );

        // Recepcionista (M3)
        User::updateOrCreate(
            ['email' => 'recepcao@saudeassai.local'],
            [
                'name' => 'Atendente da Recepção',
                'password' => Hash::make('password'),
                'role' => User::ROLE_RECEPCAO,
                'health_unit_id' => $unitId,
                'email_verified_at' => now(),
            ]
        );
    }
}
