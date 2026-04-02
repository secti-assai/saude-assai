<?php

namespace Tests\Feature;

use App\Models\CentralPharmacyRequest;
use App\Models\Citizen;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class CentralPharmacyRecepcaoFilterTest extends TestCase
{
    use RefreshDatabase;

    public function test_recepcao_defaults_to_today_requests(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-04-02 10:00:00'));

        $reception = User::factory()->create([
            'role' => 'recepcao_farmacia',
            'email_verified_at' => now(),
        ]);

        $todayCitizen = $this->createCitizen('11111111111', 'PACIENTE HOJE FARMACIA');
        $tomorrowCitizen = $this->createCitizen('22222222222', 'PACIENTE AMANHA FARMACIA');

        $this->createRequest($reception, $todayCitizen, '2026-04-02', 'RECEPCAO_VALIDADA');
        $this->createRequest($reception, $tomorrowCitizen, '2026-04-03', 'DISPENSADO');

        $response = $this->actingAs($reception)->get(route('central-pharmacy.recepcao'));

        $response->assertOk();
        $response->assertViewHas('filters', fn (array $filters): bool =>
            $filters['date_start'] === '2026-04-02'
            && $filters['date_end'] === '2026-04-02'
        );
        $response->assertSeeText('PACIENTE HOJE FARMACIA');
        $response->assertDontSeeText('PACIENTE AMANHA FARMACIA');

        Carbon::setTestNow();
    }

    public function test_recepcao_can_filter_by_date_range(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-04-02 10:00:00'));

        $reception = User::factory()->create([
            'role' => 'recepcao_farmacia',
            'email_verified_at' => now(),
        ]);

        $yesterdayCitizen = $this->createCitizen('33333333333', 'PACIENTE ONTEM FARMACIA');
        $todayCitizen = $this->createCitizen('44444444444', 'PACIENTE HOJE INTERVALO FARMACIA');
        $tomorrowCitizen = $this->createCitizen('55555555555', 'PACIENTE FORA INTERVALO FARMACIA');

        $this->createRequest($reception, $yesterdayCitizen, '2026-04-01', 'RECEPCAO_VALIDADA');
        $this->createRequest($reception, $todayCitizen, '2026-04-02', 'NAO_DISPENSADO');
        $this->createRequest($reception, $tomorrowCitizen, '2026-04-03', 'DISPENSADO_EQUIVALENTE');

        $response = $this->actingAs($reception)->get(route('central-pharmacy.recepcao', [
            'date_start' => '2026-04-01',
            'date_end' => '2026-04-02',
        ]));

        $response->assertOk();
        $response->assertViewHas('filters', fn (array $filters): bool =>
            $filters['date_start'] === '2026-04-01'
            && $filters['date_end'] === '2026-04-02'
        );
        $response->assertSeeText('PACIENTE ONTEM FARMACIA');
        $response->assertSeeText('PACIENTE HOJE INTERVALO FARMACIA');
        $response->assertDontSeeText('PACIENTE FORA INTERVALO FARMACIA');

        Carbon::setTestNow();
    }

    private function createRequest(User $reception, Citizen $citizen, string $prescriptionDate, string $status): void
    {
        CentralPharmacyRequest::create([
            'citizen_id' => $citizen->id,
            'reception_user_id' => $reception->id,
            'prescription_date' => $prescriptionDate,
            'prescriber_name' => 'Dr. Teste',
            'medication_name' => 'Dipirona',
            'concentration' => '500 mg',
            'quantity' => 1,
            'dosage' => '1 comprimido se dor',
            'status' => $status,
            'residence_status' => 'RESIDENTE',
            'gov_assai_level' => '2',
        ]);
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
