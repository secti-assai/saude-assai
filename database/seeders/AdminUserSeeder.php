<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use App\Models\HealthUnit;
use App\Models\User;
use App\Models\WomenClinicAppointment;
use Illuminate\Support\Facades\Hash;
use Illuminate\Database\Seeder;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        $clinicaUnitId = HealthUnit::whereIn('name', ['Clinica da Mulher', 'Clínica da Mulher'])->value('id')
            ?? HealthUnit::where('kind', 'UBS')->value('id');
        $policlinicaUnitId = HealthUnit::whereIn('name', ['Policlinica', 'Policlínica'])->value('id')
            ?? HealthUnit::where('kind', 'POLICLINICA')->value('id')
            ?? $clinicaUnitId;
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

        $clinicDoctors = [
            [
                'email' => 'medico.cardiologia@saudeassai.local',
                'name' => 'Medico Cardiologia',
                'specialty' => WomenClinicAppointment::SPECIALTY_CARDIOLOGIA,
            ],
            [
                'email' => 'medico.ortopedia@saudeassai.local',
                'name' => 'Medico Ortopedia',
                'specialty' => WomenClinicAppointment::SPECIALTY_ORTOPEDIA,
            ],
            [
                'email' => 'medico.psiquiatria@saudeassai.local',
                'name' => 'Medico Psiquiatria',
                'specialty' => WomenClinicAppointment::SPECIALTY_PSIQUIATRIA,
            ],
        ];

        foreach ($clinicDoctors as $doctor) {
            User::updateOrCreate(
                ['email' => $doctor['email']],
                [
                    'name' => $doctor['name'],
                    'password' => Hash::make('password'),
                    'role' => User::ROLE_MEDICO_CLINICA,
                    'clinic_specialty' => $doctor['specialty'],
                    'health_unit_id' => $clinicaUnitId,
                    'email_verified_at' => now(),
                ]
            );
        }

        $policlinicaDoctors = [
            [
                'email' => 'medico.odontologia@saudeassai.local',
                'name' => 'Medico Odontologia',
                'specialty' => WomenClinicAppointment::SPECIALTY_ODONTOLOGIA,
            ],
            [
                'email' => 'medico.fisioterapia@saudeassai.local',
                'name' => 'Medico Fisioterapia',
                'specialty' => WomenClinicAppointment::SPECIALTY_FISIOTERAPIA,
            ],
        ];

        foreach ($policlinicaDoctors as $doctor) {
            User::updateOrCreate(
                ['email' => $doctor['email']],
                [
                    'name' => $doctor['name'],
                    'password' => Hash::make('password'),
                    'role' => User::ROLE_MEDICO_POLICLINICA,
                    'clinic_specialty' => $doctor['specialty'],
                    'health_unit_id' => $policlinicaUnitId,
                    'email_verified_at' => now(),
                ]
            );
        }
    }
}
