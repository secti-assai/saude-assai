<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ModuleFlowResetTest extends TestCase
{
    use RefreshDatabase;

    public function test_flow_is_preserved_while_user_stays_in_same_module(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'email_verified_at' => now(),
        ]);

        $response = $this->actingAs($admin)->withSession([
            'navigation.current_module' => 'women_clinic',
            'women_clinic.schedule_flow' => [
                'cpf' => '12345678909',
                'identity_verified' => false,
                'challenge' => ['token' => 'token-1'],
            ],
        ])->get(route('women-clinic.agendador'));

        $response->assertOk();
        $response->assertSessionHas('women_clinic.schedule_flow.cpf', '12345678909');
        $response->assertSessionHas('navigation.current_module', 'women_clinic');
    }

    public function test_flow_is_cleared_when_user_switches_module(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'email_verified_at' => now(),
        ]);

        $response = $this->actingAs($admin)->withSession([
            'navigation.current_module' => 'women_clinic',
            'women_clinic.schedule_flow' => [
                'cpf' => '12345678909',
                'identity_verified' => false,
                'challenge' => ['token' => 'token-1'],
            ],
            'central_pharmacy.reception_flow' => [
                'cpf' => '98765432100',
                'identity_verified' => false,
                'challenge' => ['token' => 'token-2'],
            ],
            'identity_challenge.women_clinic_schedule' => ['token' => 'token-1'],
            'identity_verified.women_clinic_schedule' => ['cpf' => '12345678909'],
            'identity_challenge.central_pharmacy_reception' => ['token' => 'token-2'],
            'identity_verified.central_pharmacy_reception' => ['cpf' => '98765432100'],
        ])->get(route('central-pharmacy.recepcao'));

        $response->assertOk();
        $response->assertSessionMissing('women_clinic.schedule_flow');
        $response->assertSessionMissing('central_pharmacy.reception_flow');
        $response->assertSessionMissing('identity_challenge.women_clinic_schedule');
        $response->assertSessionMissing('identity_verified.women_clinic_schedule');
        $response->assertSessionMissing('identity_challenge.central_pharmacy_reception');
        $response->assertSessionMissing('identity_verified.central_pharmacy_reception');
        $response->assertSessionHas('navigation.current_module', 'central_pharmacy');
    }

    public function test_ajax_polling_from_other_module_does_not_clear_flow(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'email_verified_at' => now(),
        ]);

        $response = $this->actingAs($admin)->withSession([
            'navigation.current_module' => 'women_clinic',
            'women_clinic.schedule_flow' => [
                'cpf' => '12345678909',
                'identity_verified' => false,
                'challenge' => ['token' => 'token-1'],
            ],
            'identity_challenge.women_clinic_schedule' => ['token' => 'token-1'],
            'identity_verified.women_clinic_schedule' => ['cpf' => '12345678909'],
        ])->get(route('central-pharmacy.atendimento.data'), [
            'Accept' => 'application/json',
            'X-Requested-With' => 'XMLHttpRequest',
        ]);

        $response->assertOk();
        $response->assertSessionHas('women_clinic.schedule_flow.cpf', '12345678909');
        $response->assertSessionHas('identity_challenge.women_clinic_schedule.token', 'token-1');
        $response->assertSessionHas('identity_verified.women_clinic_schedule.cpf', '12345678909');
        $response->assertSessionHas('navigation.current_module', 'women_clinic');
    }
}
