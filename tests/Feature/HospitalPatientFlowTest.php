<?php

namespace Tests\Feature;

use App\Models\CentralPharmacyRequest;
use App\Models\WomenClinicAppointment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class HospitalPatientFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_women_clinic_schedule_succeeds_with_level_2_citizen(): void
    {
        $agendador = User::factory()->create([
            'role' => 'agendador',
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
                        'nome' => 'MARIA SILVA',
                        'data_nascimento' => '1990-01-15',
                        'sexo' => 'F',
                    ],
                    'saude' => [
                        'cns_numero' => '898001160444444',
                    ],
                    'gov_assai' => [
                        'nivel' => 2,
                    ],
                ],
            ], 200),
        ]);

        $response = $this->actingAs($agendador)->withSession([
            'women_clinic.schedule_flow' => [
                'cpf' => '12345678909',
                'citizen_name' => 'MARIA SILVA',
                'identity_verified' => true,
                'challenge' => ['token' => 'test'],
            ],
            'identity_verified.women_clinic_schedule' => [
                'cpf' => '12345678909',
                'verified_at' => now()->timestamp,
                'expires_at' => now()->addMinutes(20)->timestamp,
            ],
        ])->post(route('women-clinic.schedule'), [
            'scheduled_for' => now()->addDay()->format('Y-m-d H:i:s'),
            'notes' => 'Consulta de retorno',
        ]);

        $response
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $this->assertDatabaseCount('women_clinic_appointments', 1);
        $this->assertDatabaseHas('women_clinic_appointments', [
            'status' => 'AGENDADO',
            'residence_status' => 'RESIDENTE',
            'gov_assai_level' => '2',
        ]);
    }

    public function test_central_pharmacy_full_flow_reception_to_dispense(): void
    {
        $recepcaoFarmacia = User::factory()->create([
            'role' => 'recepcao_farmacia',
            'email_verified_at' => now(),
        ]);

        $atendimentoFarmacia = User::factory()->create([
            'role' => 'atendimento_farmacia',
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
                    'gov_assai' => [
                        'nivel' => 2,
                    ],
                ],
            ], 200),
        ]);

        $receptionResponse = $this->actingAs($recepcaoFarmacia)->withSession([
            'central_pharmacy.reception_flow' => [
                'cpf' => '12345678909',
                'citizen_name' => 'JOAO ALMEIDA',
                'identity_verified' => true,
                'challenge' => ['token' => 'test'],
            ],
            'identity_verified.central_pharmacy_reception' => [
                'cpf' => '12345678909',
                'verified_at' => now()->timestamp,
                'expires_at' => now()->addMinutes(20)->timestamp,
            ],
        ])->post(route('central-pharmacy.register-reception'), [
            'prescription_code' => 'RX-100',
            'prescription_date' => now()->toDateString(),
            'prescriber_name' => 'Dra. Ana Souza',
            'medication_name' => 'Dipirona 500mg',
            'concentration' => '500 mg',
            'quantity' => 2,
            'dosage' => '1 comprimido a cada 8 horas por 5 dias',
        ]);

        $receptionResponse->assertRedirect()->assertSessionHasNoErrors();
        $this->assertDatabaseHas('central_pharmacy_requests', [
            'status' => 'RECEPCAO_VALIDADA',
            'prescriber_name' => 'Dra. Ana Souza',
            'medication_name' => 'Dipirona 500mg',
            'concentration' => '500 mg',
            'quantity' => 2,
        ]);

        $request = CentralPharmacyRequest::query()->firstOrFail();
        $dispenseResponse = $this->actingAs($atendimentoFarmacia)->post(route('central-pharmacy.dispense', $request));

        $dispenseResponse->assertRedirect()->assertSessionHasNoErrors();
        $this->assertDatabaseHas('central_pharmacy_requests', [
            'id' => $request->id,
            'status' => 'DISPENSADO',
        ]);
    }

    public function test_women_clinic_schedule_is_blocked_when_level_is_below_2(): void
    {
        $agendador = User::factory()->create([
            'role' => 'agendador',
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
                        'nome' => 'ANA TESTE',
                        'data_nascimento' => '1992-10-10',
                    ],
                    'gov_assai' => [
                        'nivel' => 1,
                    ],
                ],
            ], 200),
        ]);

        $response = $this->actingAs($agendador)->withSession([
            'women_clinic.schedule_flow' => [
                'cpf' => '12345678909',
                'citizen_name' => 'ANA TESTE',
                'identity_verified' => true,
                'challenge' => ['token' => 'test'],
            ],
            'identity_verified.women_clinic_schedule' => [
                'cpf' => '12345678909',
                'verified_at' => now()->timestamp,
                'expires_at' => now()->addMinutes(20)->timestamp,
            ],
        ])->post(route('women-clinic.schedule'), [
            'scheduled_for' => now()->addDay()->format('Y-m-d H:i:s'),
        ]);

        $response
            ->assertRedirect()
            ->assertSessionHasErrors('cpf');

        $this->assertDatabaseCount('women_clinic_appointments', 0);
    }

    public function test_women_clinic_start_flow_is_blocked_when_level_is_below_2(): void
    {
        $agendador = User::factory()->create([
            'role' => 'agendador',
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
                        'nome' => 'NIVEL INSUFICIENTE',
                        'data_nascimento' => '1990-01-15',
                    ],
                    'gov_assai' => [
                        'nivel' => 1,
                    ],
                ],
            ], 200),
        ]);

        $response = $this->actingAs($agendador)->post(route('women-clinic.schedule.start'), [
            'cpf' => '123.456.789-09',
        ]);

        $response->assertRedirect();
        $response->assertSessionHasErrors('cpf');
        $response->assertSessionMissing('women_clinic.schedule_flow');
        $this->assertStringContainsString('nivel 2', (string) session('errors')->first('cpf'));
    }

    public function test_women_clinic_check_in_moves_appointment_to_checkin_status(): void
    {
        $agendador = User::factory()->create([
            'role' => 'agendador',
            'email_verified_at' => now(),
        ]);

        $recepcao = User::factory()->create([
            'role' => 'recepcao_clinica',
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
                        'nome' => 'PACIENTE CHECKIN',
                        'data_nascimento' => '1991-05-10',
                    ],
                    'gov_assai' => [
                        'nivel' => 2,
                    ],
                ],
            ], 200),
        ]);

        $scheduleResponse = $this->actingAs($agendador)->withSession([
            'women_clinic.schedule_flow' => [
                'cpf' => '12345678909',
                'citizen_name' => 'PACIENTE CHECKIN',
                'identity_verified' => true,
                'challenge' => ['token' => 'test'],
            ],
            'identity_verified.women_clinic_schedule' => [
                'cpf' => '12345678909',
                'verified_at' => now()->timestamp,
                'expires_at' => now()->addMinutes(20)->timestamp,
            ],
        ])->post(route('women-clinic.schedule'), [
            'scheduled_for' => now()->addDay()->format('Y-m-d H:i:s'),
        ]);

        $scheduleResponse->assertRedirect()->assertSessionHasNoErrors();

        $appointment = WomenClinicAppointment::query()->firstOrFail();
        $this->assertSame('AGENDADO', $appointment->status);

        $checkInResponse = $this->actingAs($recepcao)
            ->post(route('women-clinic.check-in', $appointment));

        $checkInResponse->assertRedirect()->assertSessionHasNoErrors();
        $this->assertDatabaseHas('women_clinic_appointments', [
            'id' => $appointment->id,
            'status' => 'CHECKIN',
            'reception_user_id' => $recepcao->id,
        ]);
    }
}
