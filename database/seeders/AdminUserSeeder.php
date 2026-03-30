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

        // Admin global (acesso total para testes)
        User::updateOrCreate(
            ['email' => 'admin.teste@saudeassai.local'],
            [
                'name' => 'Administrador de Teste',
                'password' => Hash::make('password'),
                'role' => User::ROLE_ADMIN,
                'health_unit_id' => $unitId,
                'email_verified_at' => now(),
            ]
        );

        // Agendador (Clinica da Mulher)
        User::updateOrCreate(
            ['email' => 'agendador@saudeassai.local'],
            [
                'name' => 'Agendador Clinica da Mulher',
                'password' => Hash::make('password'),
                'role' => User::ROLE_AGENDADOR,
                'health_unit_id' => $unitId,
                'email_verified_at' => now(),
            ]
        );

        // Recepcao Clinica da Mulher
        User::updateOrCreate(
            ['email' => 'recepcao.clinica@saudeassai.local'],
            [
                'name' => 'Recepcao Clinica da Mulher',
                'password' => Hash::make('password'),
                'role' => User::ROLE_RECEPCAO_CLINICA,
                'health_unit_id' => $unitId,
                'email_verified_at' => now(),
            ]
        );

        // Medico Clinica da Mulher
        User::updateOrCreate(
            ['email' => 'medico.clinica@saudeassai.local'],
            [
                'name' => 'Medico Clinica da Mulher',
                'password' => Hash::make('password'),
                'role' => User::ROLE_MEDICO_CLINICA,
                'health_unit_id' => $unitId,
                'email_verified_at' => now(),
            ]
        );

        // Recepcao Farmacia Central
        User::updateOrCreate(
            ['email' => 'recepcao.farmacia@saudeassai.local'],
            [
                'name' => 'Recepcao Farmacia Central',
                'password' => Hash::make('password'),
                'role' => User::ROLE_RECEPCAO_FARMACIA,
                'health_unit_id' => $unitId,
                'email_verified_at' => now(),
            ]
        );

        // Atendimento Farmacia Central
        User::updateOrCreate(
            ['email' => 'atendimento.farmacia@saudeassai.local'],
            [
                'name' => 'Atendimento Farmacia Central',
                'password' => Hash::make('password'),
                'role' => User::ROLE_ATENDIMENTO_FARMACIA,
                'health_unit_id' => $unitId,
                'email_verified_at' => now(),
            ]
        );
    }
}
