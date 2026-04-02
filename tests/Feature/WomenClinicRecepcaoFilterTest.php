<?php

namespace Tests\Feature;

use App\Models\Citizen;
use App\Models\User;
use App\Models\WomenClinicAppointment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class WomenClinicRecepcaoFilterTest extends TestCase
{
    use RefreshDatabase;

    public function test_recepcao_defaults_to_today_in_chronological_order(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-04-02 10:00:00'));

        $recepcao = User::factory()->create([
            'role' => 'recepcao_clinica',
            'email_verified_at' => now(),
        ]);

        $todayFirst = $this->createCitizen('77777777777', 'PACIENTE 08H CHECKIN');
        $todaySecond = $this->createCitizen('88888888888', 'PACIENTE 09H AGENDADO');
        $tomorrow = $this->createCitizen('99999999999', 'PACIENTE AMANHA');

        WomenClinicAppointment::create([
            'citizen_id' => $todaySecond->id,
            'scheduler_user_id' => $recepcao->id,
            'scheduled_for' => '2026-04-02 09:00:00',
            'status' => 'AGENDADO',
        ]);

        WomenClinicAppointment::create([
            'citizen_id' => $todayFirst->id,
            'scheduler_user_id' => $recepcao->id,
            'scheduled_for' => '2026-04-02 08:00:00',
            'status' => 'CHECKIN',
        ]);

        WomenClinicAppointment::create([
            'citizen_id' => $tomorrow->id,
            'scheduler_user_id' => $recepcao->id,
            'scheduled_for' => '2026-04-03 08:00:00',
            'status' => 'AGENDADO',
        ]);

        $response = $this->actingAs($recepcao)->get(route('women-clinic.recepcao'));

        $response->assertOk();
        $response->assertViewHas('filters', fn (array $filters): bool =>
            $filters['date_start'] === '2026-04-02'
            && $filters['date_end'] === '2026-04-02'
            && $filters['status'] === 'TODOS'
        );
        $response->assertSeeInOrder(['PACIENTE 08H CHECKIN', 'PACIENTE 09H AGENDADO']);
        $response->assertDontSeeText('PACIENTE AMANHA');

        Carbon::setTestNow();
    }

    public function test_recepcao_can_filter_by_status_and_date_range(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-04-02 10:00:00'));

        $recepcao = User::factory()->create([
            'role' => 'recepcao_clinica',
            'email_verified_at' => now(),
        ]);

        $todayAgendado = $this->createCitizen('10101010101', 'PACIENTE HOJE AGENDADO');
        $todayCheckin = $this->createCitizen('20202020202', 'PACIENTE HOJE CHECKIN');
        $tomorrowCheckin = $this->createCitizen('30303030303', 'PACIENTE AMANHA CHECKIN');

        WomenClinicAppointment::create([
            'citizen_id' => $todayAgendado->id,
            'scheduler_user_id' => $recepcao->id,
            'scheduled_for' => '2026-04-02 09:00:00',
            'status' => 'AGENDADO',
        ]);

        WomenClinicAppointment::create([
            'citizen_id' => $todayCheckin->id,
            'scheduler_user_id' => $recepcao->id,
            'scheduled_for' => '2026-04-02 10:00:00',
            'status' => 'CHECKIN',
        ]);

        WomenClinicAppointment::create([
            'citizen_id' => $tomorrowCheckin->id,
            'scheduler_user_id' => $recepcao->id,
            'scheduled_for' => '2026-04-03 10:00:00',
            'status' => 'CHECKIN',
        ]);

        $response = $this->actingAs($recepcao)->get(route('women-clinic.recepcao', [
            'date_start' => '2026-04-02',
            'date_end' => '2026-04-03',
            'status' => 'CHECKIN',
        ]));

        $response->assertOk();
        $response->assertViewHas('filters', fn (array $filters): bool =>
            $filters['date_start'] === '2026-04-02'
            && $filters['date_end'] === '2026-04-03'
            && $filters['status'] === 'CHECKIN'
        );
        $response->assertSeeText('PACIENTE HOJE CHECKIN');
        $response->assertSeeText('PACIENTE AMANHA CHECKIN');
        $response->assertDontSeeText('PACIENTE HOJE AGENDADO');

        Carbon::setTestNow();
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
