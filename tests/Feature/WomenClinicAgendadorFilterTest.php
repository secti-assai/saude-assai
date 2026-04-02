<?php

namespace Tests\Feature;

use App\Models\Citizen;
use App\Models\User;
use App\Models\WomenClinicAppointment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class WomenClinicAgendadorFilterTest extends TestCase
{
    use RefreshDatabase;

    public function test_agendador_defaults_to_today_and_agendado_status(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-04-02 10:00:00'));

        $agendador = User::factory()->create([
            'role' => 'agendador',
            'email_verified_at' => now(),
        ]);

        $todayAgendado = $this->createCitizen('11111111111', 'PACIENTE HOJE AGENDADO');
        $todayCheckin = $this->createCitizen('22222222222', 'PACIENTE HOJE CHECKIN');
        $tomorrowAgendado = $this->createCitizen('33333333333', 'PACIENTE AMANHA AGENDADO');

        WomenClinicAppointment::create([
            'citizen_id' => $todayAgendado->id,
            'scheduler_user_id' => $agendador->id,
            'scheduled_for' => '2026-04-02 09:00:00',
            'status' => 'AGENDADO',
        ]);

        WomenClinicAppointment::create([
            'citizen_id' => $todayCheckin->id,
            'scheduler_user_id' => $agendador->id,
            'scheduled_for' => '2026-04-02 11:00:00',
            'status' => 'CHECKIN',
        ]);

        WomenClinicAppointment::create([
            'citizen_id' => $tomorrowAgendado->id,
            'scheduler_user_id' => $agendador->id,
            'scheduled_for' => '2026-04-03 09:00:00',
            'status' => 'AGENDADO',
        ]);

        $response = $this->actingAs($agendador)->get(route('women-clinic.agendador'));

        $response->assertOk();
        $response->assertViewHas('filters', fn (array $filters): bool =>
            $filters['date_start'] === '2026-04-02'
            && $filters['date_end'] === '2026-04-02'
            && $filters['status'] === 'AGENDADO'
        );
        $response->assertSeeText('PACIENTE HOJE AGENDADO');
        $response->assertDontSeeText('PACIENTE HOJE CHECKIN');
        $response->assertDontSeeText('PACIENTE AMANHA AGENDADO');

        Carbon::setTestNow();
    }

    public function test_agendador_can_filter_by_status_and_date_range(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-04-02 10:00:00'));

        $agendador = User::factory()->create([
            'role' => 'agendador',
            'email_verified_at' => now(),
        ]);

        $todayCheckin = $this->createCitizen('44444444444', 'PACIENTE HOJE CHECKIN');
        $tomorrowCheckin = $this->createCitizen('55555555555', 'PACIENTE AMANHA CHECKIN');
        $todayAgendado = $this->createCitizen('66666666666', 'PACIENTE HOJE AGENDADO');

        WomenClinicAppointment::create([
            'citizen_id' => $todayCheckin->id,
            'scheduler_user_id' => $agendador->id,
            'scheduled_for' => '2026-04-02 08:00:00',
            'status' => 'CHECKIN',
        ]);

        WomenClinicAppointment::create([
            'citizen_id' => $tomorrowCheckin->id,
            'scheduler_user_id' => $agendador->id,
            'scheduled_for' => '2026-04-03 08:00:00',
            'status' => 'CHECKIN',
        ]);

        WomenClinicAppointment::create([
            'citizen_id' => $todayAgendado->id,
            'scheduler_user_id' => $agendador->id,
            'scheduled_for' => '2026-04-02 09:00:00',
            'status' => 'AGENDADO',
        ]);

        $response = $this->actingAs($agendador)->get(route('women-clinic.agendador', [
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
