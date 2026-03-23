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
    }
}
