<?php

namespace Tests\Feature;

use App\Models\HealthUnit;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserAccessAndUnitRestrictionTest extends TestCase
{
    use RefreshDatabase;

    public function test_custom_permissions_expand_access_beyond_base_role(): void
    {
        $user = User::factory()->create([
            'role' => User::ROLE_AGENDADOR,
            'permissions' => [
                User::PERMISSION_WOMEN_CLINIC_SCHEDULE,
                User::PERMISSION_WOMEN_CLINIC_CHECKIN,
            ],
            'email_verified_at' => now(),
        ]);

        $this->actingAs($user)
            ->get(route('women-clinic.agendador'))
            ->assertOk();

        $this->actingAs($user)
            ->get(route('women-clinic.recepcao'))
            ->assertOk();

        $this->actingAs($user)
            ->get(route('women-clinic.medico'))
            ->assertForbidden();
    }

    public function test_role_defaults_are_applied_when_permissions_are_empty(): void
    {
        $user = User::factory()->create([
            'role' => User::ROLE_RECEPCAO_CLINICA,
            'permissions' => null,
            'email_verified_at' => now(),
        ]);

        $this->actingAs($user)
            ->get(route('women-clinic.recepcao'))
            ->assertOk();

        $this->actingAs($user)
            ->get(route('women-clinic.agendador'))
            ->assertForbidden();
    }

    public function test_admin_user_management_allows_only_clinic_and_farmacia_units(): void
    {
        $admin = User::factory()->create([
            'role' => User::ROLE_ADMIN,
            'permissions' => null,
            'email_verified_at' => now(),
        ]);

        $clinic = HealthUnit::create([
            'name' => 'Clinica da Mulher',
            'kind' => 'CLINICA_MULHER',
            'code' => 'CLM-TST',
            'is_active' => true,
        ]);

        $farmacia = HealthUnit::create([
            'name' => 'Farmacia Central',
            'kind' => 'FARMACIA',
            'code' => 'FAR-TST',
            'is_active' => true,
        ]);

        $ubs = HealthUnit::create([
            'name' => 'UBS Central',
            'kind' => 'UBS',
            'code' => 'UBS-TST',
            'is_active' => true,
        ]);

        $usersPage = $this->actingAs($admin)->get(route('admin.users'));

        $usersPage->assertOk();
        $usersPage->assertSeeText('Clinica da Mulher');
        $usersPage->assertSeeText('Farmacia Central');
        $usersPage->assertDontSeeText('UBS Central');

        $invalidCreate = $this->actingAs($admin)->post(route('admin.users.create'), [
            'name' => 'Usuario Invalido',
            'email' => 'invalido@saudeassai.local',
            'password' => 'password',
            'password_confirmation' => 'password',
            'role' => User::ROLE_AGENDADOR,
            'health_unit_id' => $ubs->id,
            'permissions' => [User::PERMISSION_WOMEN_CLINIC_SCHEDULE],
        ]);

        $invalidCreate->assertRedirect();
        $invalidCreate->assertSessionHasErrors('health_unit_id');
        $this->assertDatabaseMissing('users', ['email' => 'invalido@saudeassai.local']);

        $validCreate = $this->actingAs($admin)->post(route('admin.users.create'), [
            'name' => 'Usuario Clinica',
            'email' => 'clinica@saudeassai.local',
            'password' => 'password',
            'password_confirmation' => 'password',
            'role' => User::ROLE_AGENDADOR,
            'health_unit_id' => $clinic->id,
            'permissions' => [
                User::PERMISSION_WOMEN_CLINIC_SCHEDULE,
                User::PERMISSION_WOMEN_CLINIC_CHECKIN,
            ],
        ]);

        $validCreate->assertRedirect();
        $validCreate->assertSessionHasNoErrors();
        $this->assertDatabaseHas('users', [
            'email' => 'clinica@saudeassai.local',
            'health_unit_id' => $clinic->id,
        ]);

        $this->assertNotNull($farmacia->id);
    }
}
