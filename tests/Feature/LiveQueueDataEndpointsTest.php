<?php

namespace Tests\Feature;

use App\Models\CentralPharmacyRequest;
use App\Models\Citizen;
use App\Models\User;
use App\Models\WomenClinicAppointment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class LiveQueueDataEndpointsTest extends TestCase
{
    use RefreshDatabase;

    public function test_recepcao_data_endpoint_returns_today_queue_without_reload(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-04-02 10:00:00'));

        $recepcao = User::factory()->create([
            'role' => 'recepcao_clinica',
            'email_verified_at' => now(),
        ]);

        $citizenToday = $this->createCitizen('40404040404', 'PACIENTE HOJE RECEPCAO');
        $citizenTomorrow = $this->createCitizen('50505050505', 'PACIENTE AMANHA RECEPCAO');

        WomenClinicAppointment::create([
            'citizen_id' => $citizenToday->id,
            'scheduler_user_id' => $recepcao->id,
            'scheduled_for' => '2026-04-02 09:00:00',
            'status' => 'AGENDADO',
        ]);

        WomenClinicAppointment::create([
            'citizen_id' => $citizenTomorrow->id,
            'scheduler_user_id' => $recepcao->id,
            'scheduled_for' => '2026-04-03 09:00:00',
            'status' => 'AGENDADO',
        ]);

        $response = $this->actingAs($recepcao)->get(route('women-clinic.recepcao.data'));

        $response->assertOk();
        $response->assertJsonCount(1, 'rows');
        $response->assertJsonPath('rows.0.citizen_name', 'PACIENTE HOJE RECEPCAO');
        $response->assertJsonPath('rows.0.status', 'AGENDADO');

        Carbon::setTestNow();
    }

    public function test_medico_data_endpoint_returns_only_checkin_patients(): void
    {
        $medico = User::factory()->create([
            'role' => 'medico_clinica',
            'clinic_specialty' => WomenClinicAppointment::SPECIALTY_CARDIOLOGIA,
            'email_verified_at' => now(),
        ]);

        $citizenCheckin = $this->createCitizen('60606060606', 'PACIENTE CHECKIN CARDIO');
        $citizenCheckinOutraEspecialidade = $this->createCitizen('70707070707', 'PACIENTE CHECKIN ORTOPEDIA');
        $citizenAgendado = $this->createCitizen('80808080808', 'PACIENTE AGENDADO MEDICO');

        WomenClinicAppointment::create([
            'citizen_id' => $citizenCheckin->id,
            'scheduler_user_id' => $medico->id,
            'scheduled_for' => now(),
            'specialty' => WomenClinicAppointment::SPECIALTY_CARDIOLOGIA,
            'status' => 'CHECKIN',
            'checked_in_at' => now(),
        ]);

        WomenClinicAppointment::create([
            'citizen_id' => $citizenCheckinOutraEspecialidade->id,
            'scheduler_user_id' => $medico->id,
            'scheduled_for' => now(),
            'specialty' => WomenClinicAppointment::SPECIALTY_ORTOPEDIA,
            'status' => 'CHECKIN',
            'checked_in_at' => now(),
        ]);

        WomenClinicAppointment::create([
            'citizen_id' => $citizenAgendado->id,
            'scheduler_user_id' => $medico->id,
            'scheduled_for' => now(),
            'specialty' => WomenClinicAppointment::SPECIALTY_CARDIOLOGIA,
            'status' => 'AGENDADO',
        ]);

        $response = $this->actingAs($medico)->get(route('women-clinic.medico.data'));

        $response->assertOk();
        $response->assertJsonCount(1, 'rows');
        $response->assertJsonPath('rows.0.citizen_name', 'PACIENTE CHECKIN CARDIO');
    }

    public function test_pharmacy_atendimento_data_endpoint_returns_pending_dispense_queue(): void
    {
        $atendente = User::factory()->create([
            'role' => 'atendimento_farmacia',
            'email_verified_at' => now(),
        ]);

        $recepcao = User::factory()->create([
            'role' => 'recepcao_farmacia',
            'email_verified_at' => now(),
        ]);

        $citizenPending = $this->createCitizen('80808080808', 'PACIENTE FILA FARMACIA');
        $citizenDispensed = $this->createCitizen('90909090909', 'PACIENTE DISPENSADO FARMACIA');

        CentralPharmacyRequest::create([
            'citizen_id' => $citizenPending->id,
            'reception_user_id' => $recepcao->id,
            'prescription_date' => now()->toDateString(),
            'prescriber_name' => 'Dr. Fila',
            'medication_name' => 'Dipirona',
            'concentration' => '500 mg',
            'quantity' => 2,
            'dosage' => '1 comprimido se dor',
            'status' => 'RECEPCAO_VALIDADA',
        ]);

        CentralPharmacyRequest::create([
            'citizen_id' => $citizenDispensed->id,
            'reception_user_id' => $recepcao->id,
            'prescription_date' => now()->toDateString(),
            'prescriber_name' => 'Dra. Fila',
            'medication_name' => 'Paracetamol',
            'concentration' => '750 mg',
            'quantity' => 1,
            'dosage' => '1 comprimido a cada 12 horas',
            'status' => 'DISPENSADO',
        ]);

        $response = $this->actingAs($atendente)->get(route('central-pharmacy.atendimento.data'));

        $response->assertOk();
        $response->assertJsonCount(1, 'rows');
        $response->assertJsonPath('rows.0.citizen_name', 'PACIENTE FILA FARMACIA');
        $response->assertJsonPath('rows.0.prescriber_name', 'Dr. Fila');
        $response->assertJsonPath('rows.0.concentration', '500 mg');
        $response->assertJsonPath('rows.0.medication_name', 'Dipirona');
        $response->assertJsonPath('rows.0.dosage', '1 comprimido se dor');
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
