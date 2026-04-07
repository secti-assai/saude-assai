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
        $clinicaUnitId = HealthUnit::whereIn('name', ['Clinica da Mulher', 'Clínica da Mulher'])->value('id')
            ?? HealthUnit::where('kind', 'UBS')->value('id');
        $farmaciaUnitId = HealthUnit::whereIn('name', ['Farmacia Central', 'Farmácia Central'])->value('id')
            ?? HealthUnit::where('kind', 'FARMACIA')->value('id')
            ?? $clinicaUnitId;

        // Admin global (acesso total para testes)
        User::updateOrCreate(
            ['email' => 'admin.teste@saudeassai.local'],
            [
                'name' => 'Administrador de Teste',
                'password' => Hash::make('password'),
                'role' => User::ROLE_ADMIN,
                'health_unit_id' => $clinicaUnitId,
                'email_verified_at' => now(),
            ]
        );
    }
}
