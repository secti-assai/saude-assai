<?php

namespace Tests\Feature;

use App\Models\CentralPharmacyRequest;
use App\Models\Citizen;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class CentralPharmacyDecisionFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_attendant_can_refuse_dispensing_with_reason(): void
    {
        $attendant = User::factory()->create([
            'role' => 'atendimento_farmacia',
            'email_verified_at' => now(),
        ]);

        $reception = User::factory()->create([
            'role' => 'recepcao_farmacia',
            'email_verified_at' => now(),
        ]);

        $citizen = $this->createCitizen('11122233344', 'PACIENTE RECUSA');

        $request = CentralPharmacyRequest::create([
            'citizen_id' => $citizen->id,
            'reception_user_id' => $reception->id,
            'prescription_date' => now()->toDateString(),
            'prescriber_name' => 'Dr. Teste',
            'medication_name' => 'Amoxicilina',
            'concentration' => '500 mg',
            'quantity' => 1,
            'dosage' => '1 capsula a cada 8 horas',
            'status' => 'RECEPCAO_VALIDADA',
        ]);

        $response = $this->actingAs($attendant)->post(route('central-pharmacy.refuse', $request), [
            'refusal_reason' => 'Receita vencida no momento da retirada.',
        ]);

        $response->assertRedirect()->assertSessionHasNoErrors();
        $this->assertDatabaseHas('central_pharmacy_requests', [
            'id' => $request->id,
            'status' => 'NAO_DISPENSADO',
            'refusal_reason' => 'Receita vencida no momento da retirada.',
        ]);
    }

    public function test_attendant_can_dispense_equivalent_medication(): void
    {
        $attendant = User::factory()->create([
            'role' => 'atendimento_farmacia',
            'email_verified_at' => now(),
        ]);

        $reception = User::factory()->create([
            'role' => 'recepcao_farmacia',
            'email_verified_at' => now(),
        ]);

        $citizen = $this->createCitizen('55566677788', 'PACIENTE EQUIVALENTE');

        $request = CentralPharmacyRequest::create([
            'citizen_id' => $citizen->id,
            'reception_user_id' => $reception->id,
            'prescription_date' => now()->toDateString(),
            'prescriber_name' => 'Dra. Maria',
            'medication_name' => 'Losartana',
            'concentration' => '50 mg',
            'quantity' => 2,
            'dosage' => '1 comprimido ao dia',
            'status' => 'RECEPCAO_VALIDADA',
        ]);

        $response = $this->actingAs($attendant)->post(route('central-pharmacy.dispense-equivalent', $request), [
            'equivalent_medication_name' => 'Losartana Potassica Genérico',
            'equivalent_concentration' => '50 mg',
        ]);

        $response->assertRedirect()->assertSessionHasNoErrors();
        $this->assertDatabaseHas('central_pharmacy_requests', [
            'id' => $request->id,
            'status' => 'DISPENSADO_EQUIVALENTE',
            'equivalent_medication_name' => 'Losartana Potassica Genérico',
            'equivalent_concentration' => '50 mg',
        ]);
    }

    public function test_reception_returns_level_message_when_flow_session_is_missing(): void
    {
        $reception = User::factory()->create([
            'role' => 'recepcao_farmacia',
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
                        'nome' => 'PACIENTE NIVEL BAIXO',
                        'data_nascimento' => '1991-10-10',
                    ],
                    'gov_assai' => [
                        'nivel' => 1,
                    ],
                ],
            ], 200),
        ]);

        $response = $this->actingAs($reception)->post(route('central-pharmacy.register-reception'), [
            'flow_cpf' => '123.456.789-09',
            'prescription_date' => now()->toDateString(),
            'prescriber_name' => 'Dr. Baixo Nivel',
            'medication_name' => 'Dipirona',
            'concentration' => '500 mg',
            'quantity' => 1,
            'dosage' => '1 comprimido se dor',
        ]);

        $response->assertRedirect();
        $response->assertSessionHasErrors('cpf');
        $this->assertStringContainsString('nivel 2', (string) session('errors')->first('cpf'));
        $this->assertDatabaseCount('central_pharmacy_requests', 0);
    }

    public function test_reception_start_flow_is_blocked_when_level_is_below_2(): void
    {
        $reception = User::factory()->create([
            'role' => 'recepcao_farmacia',
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
                        'nome' => 'PACIENTE BLOQUEADO',
                        'data_nascimento' => '1990-01-01',
                    ],
                    'gov_assai' => [
                        'nivel' => 1,
                    ],
                ],
            ], 200),
        ]);

        $response = $this->actingAs($reception)->post(route('central-pharmacy.reception.start'), [
            'cpf' => '123.456.789-09',
        ]);

        $response->assertRedirect();
        $response->assertSessionHasErrors('cpf');
        $response->assertSessionMissing('central_pharmacy.reception_flow');
        $this->assertStringContainsString('nivel 2', (string) session('errors')->first('cpf'));
    }

    public function test_reception_start_flow_is_blocked_when_gov_assai_is_unavailable(): void
    {
        $reception = User::factory()->create([
            'role' => 'recepcao_farmacia',
            'email_verified_at' => now(),
        ]);

        config()->set('services.gov_assai.base_url', 'https://gov-assai.test');
        config()->set('services.gov_assai.api_key', 'test-key');

        Http::fake([
            'https://gov-assai.test/api/saude/cidadaos/cpf/*' => Http::response([
                'success' => false,
                'message' => 'Gov.Assai indisponivel no momento.',
                'error_code' => 'GOV_ASSAI_UNAVAILABLE',
            ], 503),
        ]);

        $response = $this->actingAs($reception)->post(route('central-pharmacy.reception.start'), [
            'cpf' => '123.456.789-09',
        ]);

        $response->assertRedirect();
        $response->assertSessionHasErrors('cpf');
        $response->assertSessionMissing('central_pharmacy.reception_flow');
        $this->assertStringContainsString('Gov.Assai indisponivel', (string) session('errors')->first('cpf'));
    }

    private function createCitizen(string $cpf, string $name): Citizen
    {
        return Citizen::create([
            'cpf' => $cpf,
            'cpf_hash' => hash('sha256', $cpf),
            'full_name' => $name,
            'birth_date' => '1990-01-01',
            'is_resident_assai' => true,
        ]);
    }
}
