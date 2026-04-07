<?php

namespace Tests\Feature;

use App\Models\CentralPharmacyRequest;
use App\Models\Citizen;
use App\Models\User;
use App\Models\WomenClinicAppointment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class PublicNotificationLinksTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_cancel_link_remains_valid_when_host_changes_in_dev(): void
    {
        $scheduler = User::factory()->create([
            'role' => User::ROLE_AGENDADOR,
            'email_verified_at' => now(),
        ]);

        $citizen = Citizen::create([
            'cpf' => '90012640949',
            'cpf_hash' => hash('sha256', '90012640949'),
            'full_name' => 'CIDADAO HOST DEV',
            'birth_date' => '1992-02-02',
            'is_resident_assai' => true,
            'phone' => '(43) 90000-0000',
        ]);

        $appointment = WomenClinicAppointment::create([
            'citizen_id' => $citizen->id,
            'scheduler_user_id' => $scheduler->id,
            'scheduled_for' => now()->addDay(),
            'status' => 'AGENDADO',
            'residence_status' => 'RESIDENTE',
            'gov_assai_level' => '2',
        ]);

        $signedPath = URL::temporarySignedRoute(
            'women-clinic.public.cancel',
            now()->addHours(6),
            ['womenClinicAppointment' => $appointment->id, 'nonce' => 'host-variation'],
            absolute: false
        );

        $this->get('http://127.0.0.1:8000'.$signedPath)->assertOk();
    }

    public function test_public_cancel_requires_valid_identity_and_cancels_appointment(): void
    {
        $scheduler = User::factory()->create([
            'role' => User::ROLE_AGENDADOR,
            'email_verified_at' => now(),
        ]);

        $citizen = Citizen::create([
            'cpf' => '90012640940',
            'cpf_hash' => hash('sha256', '90012640940'),
            'full_name' => 'CIDADAO CANCELAMENTO',
            'birth_date' => '1993-03-15',
            'is_resident_assai' => true,
            'phone' => '(43) 96666-6666',
        ]);

        $appointment = WomenClinicAppointment::create([
            'citizen_id' => $citizen->id,
            'scheduler_user_id' => $scheduler->id,
            'scheduled_for' => now()->addDays(2),
            'status' => 'AGENDADO',
            'residence_status' => 'RESIDENTE',
            'gov_assai_level' => '2',
        ]);

        $url = URL::temporarySignedRoute(
            'women-clinic.public.cancel',
            now()->addHours(6),
            ['womenClinicAppointment' => $appointment->id, 'nonce' => 'cancel-1'],
            absolute: false
        );

        $this->get($url)->assertOk();

        $this->post($url, [
            'cpf' => '90012640940',
            'birth_date' => '1993-03-15',
            'reason' => 'Imprevisto pessoal',
        ])->assertRedirect();

        $appointment->refresh();

        $this->assertSame('CANCELADO', $appointment->status);
        $this->assertNotNull($appointment->cancelled_at);
    }

    public function test_public_cancel_link_expires_after_signature_ttl(): void
    {
        $scheduler = User::factory()->create([
            'role' => User::ROLE_AGENDADOR,
            'email_verified_at' => now(),
        ]);

        $citizen = Citizen::create([
            'cpf' => '90012640941',
            'cpf_hash' => hash('sha256', '90012640941'),
            'full_name' => 'CIDADAO EXPIRADO',
            'birth_date' => '1994-04-20',
            'is_resident_assai' => true,
            'phone' => '(43) 95555-5555',
        ]);

        $appointment = WomenClinicAppointment::create([
            'citizen_id' => $citizen->id,
            'scheduler_user_id' => $scheduler->id,
            'scheduled_for' => now()->addDays(2),
            'status' => 'AGENDADO',
            'residence_status' => 'RESIDENTE',
            'gov_assai_level' => '2',
        ]);

        $expiredUrl = URL::temporarySignedRoute(
            'women-clinic.public.cancel',
            now()->subMinute(),
            ['womenClinicAppointment' => $appointment->id, 'nonce' => 'cancel-expired'],
            absolute: false
        );

        $this->get($expiredUrl)->assertForbidden();
    }

    public function test_public_women_clinic_feedback_saves_score_and_comment(): void
    {
        $scheduler = User::factory()->create([
            'role' => User::ROLE_AGENDADOR,
            'email_verified_at' => now(),
        ]);

        $citizen = Citizen::create([
            'cpf' => '90012640942',
            'cpf_hash' => hash('sha256', '90012640942'),
            'full_name' => 'CIDADAO FEEDBACK CLINICA',
            'birth_date' => '1995-05-25',
            'is_resident_assai' => true,
            'phone' => '(43) 94444-4444',
        ]);

        $appointment = WomenClinicAppointment::create([
            'citizen_id' => $citizen->id,
            'scheduler_user_id' => $scheduler->id,
            'scheduled_for' => now()->subHour(),
            'status' => 'FINALIZADO',
            'residence_status' => 'RESIDENTE',
            'gov_assai_level' => '2',
            'checked_in_at' => now()->subHours(2),
            'checked_out_at' => now()->subHour(),
        ]);

        $url = URL::temporarySignedRoute(
            'women-clinic.public.feedback',
            now()->addDays(7),
            ['womenClinicAppointment' => $appointment->id, 'nonce' => 'feedback-clinica'],
            absolute: false
        );

        $this->get($url)->assertOk();

        $this->post($url, [
            'cpf' => '90012640942',
            'birth_date' => '1995-05-25',
            'feedback_score' => 5,
            'feedback_comment' => 'Atendimento excelente.',
        ])->assertRedirect();

        $appointment->refresh();

        $this->assertSame(5, $appointment->feedback_score);
        $this->assertSame('Atendimento excelente.', $appointment->feedback_comment);
        $this->assertNotNull($appointment->feedback_submitted_at);
    }

    public function test_public_pharmacy_feedback_saves_score_and_comment(): void
    {
        $reception = User::factory()->create([
            'role' => User::ROLE_FARMACIA,
            'email_verified_at' => now(),
        ]);

        $dispense = User::factory()->create([
            'role' => User::ROLE_FARMACIA,
            'email_verified_at' => now(),
        ]);

        $citizen = Citizen::create([
            'cpf' => '90012640943',
            'cpf_hash' => hash('sha256', '90012640943'),
            'full_name' => 'CIDADAO FEEDBACK FARMACIA',
            'birth_date' => '1996-06-30',
            'is_resident_assai' => true,
            'phone' => '(43) 93333-3333',
        ]);

        $request = CentralPharmacyRequest::create([
            'citizen_id' => $citizen->id,
            'reception_user_id' => $reception->id,
            'attendant_user_id' => $dispense->id,
            'medication_name' => 'Dipirona 500mg',
            'quantity' => 1,
            'status' => 'DISPENSADO',
            'residence_status' => 'RESIDENTE',
            'gov_assai_level' => '2',
            'dispensed_at' => now()->subMinutes(5),
        ]);

        $url = URL::temporarySignedRoute(
            'central-pharmacy.public.feedback',
            now()->addDays(7),
            ['centralPharmacyRequest' => $request->id, 'nonce' => 'feedback-farmacia'],
            absolute: false
        );

        $this->get($url)->assertOk();

        $this->post($url, [
            'cpf' => '90012640943',
            'birth_date' => '1996-06-30',
            'feedback_score' => 4,
            'feedback_comment' => 'Atendimento bom e rapido.',
        ])->assertRedirect();

        $request->refresh();

        $this->assertSame(4, $request->feedback_score);
        $this->assertSame('Atendimento bom e rapido.', $request->feedback_comment);
        $this->assertNotNull($request->feedback_submitted_at);
    }
}
