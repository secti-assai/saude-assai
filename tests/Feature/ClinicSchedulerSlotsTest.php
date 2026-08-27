<?php

namespace Tests\Feature;

use App\Models\Citizen;
use App\Models\ClinicScheduleRule;
use App\Models\User;
use App\Models\WomenClinicAppointment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ClinicSchedulerSlotsTest extends TestCase
{
    use RefreshDatabase;

    public function test_scheduler_slots_endpoint_returns_configured_slots_for_week_5(): void
    {
        $user = User::factory()->create([
            'role' => User::ROLE_ADMIN,
            'permissions' => [User::PERMISSION_WOMEN_CLINIC_SCHEDULE],
        ]);

        // Cria regra de Ortopedia para Segunda-feira (1), todas as semanas [1, 2, 3, 4, 5], às 08:00 com capacidade 25
        ClinicScheduleRule::create([
            'clinic_type' => WomenClinicAppointment::CLINIC_WOMEN,
            'specialty' => WomenClinicAppointment::SPECIALTY_ORTOPEDIA,
            'day_of_week' => 1, // Segunda-feira
            'weeks_of_month' => [1, 2, 3, 4, 5],
            'time' => '08:00',
            'capacity' => 25,
            'is_active' => true,
        ]);

        // 31/08/2026 é uma Segunda-feira na 5ª semana do mês (ceil(31/7) = 5)
        $response = $this->actingAs($user)->getJson(route('clinic-scheduler.slots', [
            'clinic_type' => WomenClinicAppointment::CLINIC_WOMEN,
            'specialty' => WomenClinicAppointment::SPECIALTY_ORTOPEDIA,
            'date' => '2026-08-31',
        ]));

        $response->assertOk();
        $slots = $response->json();

        $this->assertCount(25, $slots);
        $this->assertSame('08:00', $slots[0]['time']);
        $this->assertTrue($slots[0]['available']);
    }

    public function test_scheduler_slots_shows_busy_and_available_slots(): void
    {
        $user = User::factory()->create([
            'role' => User::ROLE_ADMIN,
            'permissions' => [User::PERMISSION_WOMEN_CLINIC_SCHEDULE],
        ]);

        ClinicScheduleRule::create([
            'clinic_type' => WomenClinicAppointment::CLINIC_WOMEN,
            'specialty' => WomenClinicAppointment::SPECIALTY_ORTOPEDIA,
            'day_of_week' => 1,
            'weeks_of_month' => [1, 2, 3, 4, 5],
            'time' => '08:00',
            'capacity' => 2,
            'is_active' => true,
        ]);

        $citizen = Citizen::create([
            'cpf' => '12345678909',
            'full_name' => 'Paciente Teste',
            'birth_date' => '1990-01-01',
            'mother_name' => 'Mae Teste',
            'phone' => '43999999999',
        ]);

        WomenClinicAppointment::create([
            'citizen_id' => $citizen->id,
            'scheduler_user_id' => $user->id,
            'scheduled_for' => '2026-08-31 08:00:00',
            'clinic_type' => WomenClinicAppointment::CLINIC_WOMEN,
            'specialty' => WomenClinicAppointment::SPECIALTY_ORTOPEDIA,
            'status' => 'AGENDADO',
        ]);

        $response = $this->actingAs($user)->getJson(route('clinic-scheduler.slots', [
            'clinic_type' => WomenClinicAppointment::CLINIC_WOMEN,
            'specialty' => WomenClinicAppointment::SPECIALTY_ORTOPEDIA,
            'date' => '2026-08-31',
        ]));

        $response->assertOk();
        $slots = $response->json();

        $this->assertCount(2, $slots);
        $this->assertFalse($slots[0]['available']);
        $this->assertSame('Paciente Teste', $slots[0]['patient_name']);
        $this->assertTrue($slots[1]['available']);
    }
}
