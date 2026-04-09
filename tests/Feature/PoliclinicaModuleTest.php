<?php

namespace Tests\Feature;

use App\Models\Citizen;
use App\Models\User;
use App\Models\WomenClinicAppointment;
use App\Services\CitizenEligibilityService;
use App\Services\CitizenIdentityChallengeService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery\MockInterface;
use Tests\TestCase;

class PoliclinicaModuleTest extends TestCase
{
    use RefreshDatabase;

    public function test_scheduler_can_create_policlinica_appointment(): void
    {
        $agendador = User::factory()->create([
            'role' => User::ROLE_AGENDADOR,
            'email_verified_at' => now(),
        ]);

        $citizen = Citizen::create([
            'cpf' => '90012640980',
            'cpf_hash' => hash('sha256', '90012640980'),
            'full_name' => 'CIDADAO POLICLINICA',
            'birth_date' => '1990-01-01',
            'is_resident_assai' => true,
            'phone' => '(43) 98888-0000',
        ]);

        $this->mock(CitizenEligibilityService::class, function (MockInterface $mock) use ($citizen): void {
            $mock->shouldReceive('validateAndSync')->once()->andReturn([
                'eligible' => true,
                'message' => 'ok',
                'residence_status' => 'RESIDENTE',
                'gov_assai_level' => '2',
                'citizen' => $citizen,
                'cpf' => '90012640980',
                'error_code' => null,
            ]);
        });

        $this->mock(CitizenIdentityChallengeService::class, function (MockInterface $mock): void {
            $mock->shouldReceive('isVerified')->once()->andReturn(true);
            $mock->shouldReceive('clear')->once();
            $mock->shouldReceive('consumeVerified')->once();
        });

        $response = $this->actingAs($agendador)
            ->from(route('clinic-scheduler.index'))
            ->withSession([
                'women_clinic.schedule_flow' => [
                    'cpf' => '90012640980',
                ],
            ])
            ->post(route('clinic-scheduler.schedule'), [
                'scheduled_for' => now()->addDays(2)->format('Y-m-d H:i:s'),
                'clinic_type' => WomenClinicAppointment::CLINIC_POLICLINICA,
                'specialty' => WomenClinicAppointment::SPECIALTY_ODONTOLOGIA,
                'notes' => 'Consulta policlinica',
            ]);

        $response->assertRedirect(route('clinic-scheduler.index'));
        $response->assertSessionHasNoErrors();

        $this->assertDatabaseHas('women_clinic_appointments', [
            'citizen_id' => $citizen->id,
            'clinic_type' => WomenClinicAppointment::CLINIC_POLICLINICA,
            'specialty' => WomenClinicAppointment::SPECIALTY_ODONTOLOGIA,
            'status' => 'AGENDADO',
        ]);
    }

    public function test_policlinica_medico_data_is_filtered_by_clinic_and_doctor_specialty(): void
    {
        $doctor = User::factory()->create([
            'role' => User::ROLE_MEDICO_POLICLINICA,
            'clinic_specialty' => WomenClinicAppointment::SPECIALTY_ODONTOLOGIA,
            'email_verified_at' => now(),
        ]);

        $citizenOdonto = $this->createCitizen('90012640981', 'PACIENTE ODONTO POLICLINICA');
        $citizenFisio = $this->createCitizen('90012640982', 'PACIENTE FISIO POLICLINICA');
        $citizenWomen = $this->createCitizen('90012640983', 'PACIENTE CLINICA MULHER');

        WomenClinicAppointment::create([
            'citizen_id' => $citizenOdonto->id,
            'scheduler_user_id' => $doctor->id,
            'scheduled_for' => now(),
            'clinic_type' => WomenClinicAppointment::CLINIC_POLICLINICA,
            'specialty' => WomenClinicAppointment::SPECIALTY_ODONTOLOGIA,
            'status' => 'CHECKIN',
            'checked_in_at' => now(),
        ]);

        WomenClinicAppointment::create([
            'citizen_id' => $citizenFisio->id,
            'scheduler_user_id' => $doctor->id,
            'scheduled_for' => now(),
            'clinic_type' => WomenClinicAppointment::CLINIC_POLICLINICA,
            'specialty' => WomenClinicAppointment::SPECIALTY_FISIOTERAPIA,
            'status' => 'CHECKIN',
            'checked_in_at' => now(),
        ]);

        WomenClinicAppointment::create([
            'citizen_id' => $citizenWomen->id,
            'scheduler_user_id' => $doctor->id,
            'scheduled_for' => now(),
            'clinic_type' => WomenClinicAppointment::CLINIC_WOMEN,
            'specialty' => WomenClinicAppointment::SPECIALTY_CARDIOLOGIA,
            'status' => 'CHECKIN',
            'checked_in_at' => now(),
        ]);

        $response = $this->actingAs($doctor)->get(route('policlinica.medico.data'));

        $response->assertOk();
        $response->assertJsonCount(1, 'rows');
        $response->assertJsonPath('rows.0.citizen_name', 'PACIENTE ODONTO POLICLINICA');
    }

    public function test_policlinica_checkout_is_blocked_for_different_specialty(): void
    {
        $doctor = User::factory()->create([
            'role' => User::ROLE_MEDICO_POLICLINICA,
            'clinic_specialty' => WomenClinicAppointment::SPECIALTY_ODONTOLOGIA,
            'email_verified_at' => now(),
        ]);

        $citizen = $this->createCitizen('90012640984', 'PACIENTE CHECKOUT BLOQUEADO');

        $appointment = WomenClinicAppointment::create([
            'citizen_id' => $citizen->id,
            'scheduler_user_id' => $doctor->id,
            'scheduled_for' => now()->subHour(),
            'clinic_type' => WomenClinicAppointment::CLINIC_POLICLINICA,
            'specialty' => WomenClinicAppointment::SPECIALTY_FISIOTERAPIA,
            'status' => 'CHECKIN',
            'checked_in_at' => now()->subMinutes(20),
            'residence_status' => 'RESIDENTE',
            'gov_assai_level' => '2',
        ]);

        $response = $this->actingAs($doctor)->post(route('policlinica.check-out', $appointment));

        $response->assertRedirect();
        $response->assertSessionHasErrors('status');

        $appointment->refresh();
        $this->assertSame('CHECKIN', $appointment->status);
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
