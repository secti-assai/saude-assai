<?php

namespace Tests\Feature;

use App\Jobs\SendWomenClinicLifecycleNotificationJob;
use App\Models\Citizen;
use App\Models\User;
use App\Models\WomenClinicAppointment;
use App\Services\CitizenEligibilityService;
use App\Services\CitizenIdentityChallengeService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Mockery\MockInterface;
use Tests\TestCase;

class WomenClinicNotificationWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_schedule_dispatches_immediate_and_24h_reminder_jobs(): void
    {
        Queue::fake();

        $agendador = User::factory()->create([
            'role' => User::ROLE_AGENDADOR,
            'email_verified_at' => now(),
        ]);

        $citizen = Citizen::create([
            'cpf' => '90012640930',
            'cpf_hash' => hash('sha256', '90012640930'),
            'full_name' => 'CIDADAO NOTIFICACAO',
            'birth_date' => '1990-01-01',
            'is_resident_assai' => true,
            'phone' => '(43) 99999-9999',
        ]);

        $this->mock(CitizenEligibilityService::class, function (MockInterface $mock) use ($citizen): void {
            $mock->shouldReceive('validateAndSync')->once()->andReturn([
                'eligible' => true,
                'message' => 'ok',
                'residence_status' => 'RESIDENTE',
                'gov_assai_level' => '2',
                'citizen' => $citizen,
                'cpf' => '90012640930',
                'error_code' => null,
            ]);
        });

        $this->mock(CitizenIdentityChallengeService::class, function (MockInterface $mock): void {
            $mock->shouldReceive('isVerified')->once()->andReturn(true);
            $mock->shouldReceive('clear')->once();
            $mock->shouldReceive('consumeVerified')->once();
        });

        $response = $this->actingAs($agendador)
            ->from(route('women-clinic.agendador'))
            ->withSession([
                'women_clinic.schedule_flow' => [
                    'cpf' => '90012640930',
                ],
            ])
            ->post(route('women-clinic.schedule'), [
                'scheduled_for' => now()->addDays(2)->format('Y-m-d H:i:s'),
                'notes' => 'Teste de notificacao',
            ]);

        $response->assertRedirect(route('women-clinic.agendador'));
        $response->assertSessionHasNoErrors();

        $appointment = WomenClinicAppointment::query()->latest('created_at')->first();

        $this->assertNotNull($appointment);

        Queue::assertPushed(SendWomenClinicLifecycleNotificationJob::class, function (SendWomenClinicLifecycleNotificationJob $job) use ($appointment): bool {
            return $job->appointmentId === (string) $appointment->id
                && $job->trigger === SendWomenClinicLifecycleNotificationJob::TRIGGER_SCHEDULED;
        });

        Queue::assertPushed(SendWomenClinicLifecycleNotificationJob::class, function (SendWomenClinicLifecycleNotificationJob $job) use ($appointment): bool {
            return $job->appointmentId === (string) $appointment->id
                && $job->trigger === SendWomenClinicLifecycleNotificationJob::TRIGGER_REMINDER_24H;
        });

        Queue::assertPushed(SendWomenClinicLifecycleNotificationJob::class, 2);
    }

    public function test_checkin_dispatches_checkin_notification_job(): void
    {
        Queue::fake();

        $reception = User::factory()->create([
            'role' => User::ROLE_RECEPCAO_CLINICA,
            'email_verified_at' => now(),
        ]);

        $citizen = Citizen::create([
            'cpf' => '90012640931',
            'cpf_hash' => hash('sha256', '90012640931'),
            'full_name' => 'CIDADAO CHECKIN',
            'birth_date' => '1991-01-01',
            'is_resident_assai' => true,
            'phone' => '(43) 98888-8888',
        ]);

        $appointment = WomenClinicAppointment::create([
            'citizen_id' => $citizen->id,
            'scheduler_user_id' => $reception->id,
            'scheduled_for' => now()->addDay(),
            'status' => 'AGENDADO',
            'residence_status' => 'RESIDENTE',
            'gov_assai_level' => '2',
        ]);

        $this->mock(CitizenEligibilityService::class, function (MockInterface $mock) use ($citizen): void {
            $mock->shouldReceive('validateAndSync')->once()->andReturn([
                'eligible' => true,
                'message' => 'ok',
                'residence_status' => 'RESIDENTE',
                'gov_assai_level' => '2',
                'citizen' => $citizen,
                'cpf' => '90012640931',
                'error_code' => null,
            ]);
        });

        $response = $this->actingAs($reception)->post(route('women-clinic.check-in', $appointment));

        $response->assertRedirect();
        $response->assertSessionHasNoErrors();

        $appointment->refresh();
        $this->assertSame('CHECKIN', $appointment->status);

        Queue::assertPushed(SendWomenClinicLifecycleNotificationJob::class, function (SendWomenClinicLifecycleNotificationJob $job) use ($appointment): bool {
            return $job->appointmentId === (string) $appointment->id
                && $job->trigger === SendWomenClinicLifecycleNotificationJob::TRIGGER_CHECKIN;
        });
    }

    public function test_checkout_dispatches_checkout_notification_job(): void
    {
        Queue::fake();

        $doctor = User::factory()->create([
            'role' => User::ROLE_MEDICO_CLINICA,
            'email_verified_at' => now(),
        ]);

        $citizen = Citizen::create([
            'cpf' => '90012640932',
            'cpf_hash' => hash('sha256', '90012640932'),
            'full_name' => 'CIDADAO CHECKOUT',
            'birth_date' => '1992-01-01',
            'is_resident_assai' => true,
            'phone' => '(43) 97777-7777',
        ]);

        $appointment = WomenClinicAppointment::create([
            'citizen_id' => $citizen->id,
            'scheduler_user_id' => $doctor->id,
            'reception_user_id' => $doctor->id,
            'scheduled_for' => now()->subHour(),
            'status' => 'CHECKIN',
            'checked_in_at' => now()->subMinutes(20),
            'residence_status' => 'RESIDENTE',
            'gov_assai_level' => '2',
        ]);

        $response = $this->actingAs($doctor)->post(route('women-clinic.check-out', $appointment));

        $response->assertRedirect();
        $response->assertSessionHasNoErrors();

        $appointment->refresh();
        $this->assertSame('FINALIZADO', $appointment->status);

        Queue::assertPushed(SendWomenClinicLifecycleNotificationJob::class, function (SendWomenClinicLifecycleNotificationJob $job) use ($appointment): bool {
            return $job->appointmentId === (string) $appointment->id
                && $job->trigger === SendWomenClinicLifecycleNotificationJob::TRIGGER_CHECKOUT;
        });
    }
}
