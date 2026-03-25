<?php

namespace Tests\Feature;

use App\Models\Citizen;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class HospitalPatientFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_scenario_a_search_finds_existing_local_patient(): void
    {
        $user = User::factory()->create([
            'role' => 'admin_secti',
            'two_factor_enabled' => true,
            'email_verified_at' => now(),
        ]);

        Citizen::create([
            'full_name' => 'MARIA SILVA',
            'cpf' => '12345678909',
            'cpf_hash' => hash('sha256', '12345678909'),
            'birth_date' => '1990-01-15',
            'is_resident_assai' => true,
            'residence_validated_at' => now(),
        ]);

        $response = $this->actingAs($user)->getJson(route('hospital.citizens.search', ['q' => 'MARIA']));

        $response
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.0.full_name', 'MARIA SILVA')
            ->assertJsonPath('data.0.source', 'LOCAL');
    }

    public function test_scenario_b_lookup_finds_patient_in_gov_assai(): void
    {
        $user = User::factory()->create([
            'role' => 'admin_secti',
            'two_factor_enabled' => true,
            'email_verified_at' => now(),
        ]);

        config()->set('services.gov_assai.base_url', 'https://gov-assai.test');
        config()->set('services.gov_assai.api_key', 'test-key');

        Http::fake([
            'https://gov-assai.test/api/saude/cidadaos/cpf/*' => Http::response([
                'success' => true,
                'message' => 'Consulta realizada com sucesso.',
                'data' => [
                    'cidadao' => [
                        'nome' => 'JOAO ALMEIDA',
                        'data_nascimento' => '1988-03-20',
                    ],
                ],
            ], 200),
        ]);

        $response = $this->actingAs($user)->getJson(route('hospital.citizens.lookup', ['cpf' => '12345678909']));

        $response
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.cidadao.nome', 'JOAO ALMEIDA');
    }

    public function test_scenario_c_lookup_not_found_enables_manual_flow(): void
    {
        $user = User::factory()->create([
            'role' => 'admin_secti',
            'two_factor_enabled' => true,
            'email_verified_at' => now(),
        ]);

        config()->set('services.gov_assai.base_url', 'https://gov-assai.test');
        config()->set('services.gov_assai.api_key', 'test-key');

        Http::fake([
            'https://gov-assai.test/api/saude/cidadaos/cpf/*' => Http::response([
                'success' => false,
                'message' => 'Cidadao nao encontrado',
                'error_code' => 'CITIZEN_NOT_FOUND',
            ], 404),
        ]);

        $response = $this->actingAs($user)->getJson(route('hospital.citizens.lookup', ['cpf' => '12345678909']));

        $response
            ->assertStatus(404)
            ->assertJsonPath('success', false)
            ->assertJsonPath('error_code', 'CITIZEN_NOT_FOUND');
    }
}
