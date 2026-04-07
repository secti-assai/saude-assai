<?php

namespace Tests\Feature;

use App\Models\Citizen;
use App\Models\User;
use App\Models\WomenClinicAppointment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WomenClinicReportsModuleTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_access_women_clinic_reports_module(): void
    {
        $admin = User::factory()->create([
            'role' => User::ROLE_ADMIN,
            'permissions' => null,
        ]);

        $response = $this->actingAs($admin)->get(route('women-clinic.reports'));

        $response->assertOk();
        $response->assertSee('Clínica da Mulher - Relatórios');
    }

    public function test_user_with_reports_permission_can_access_module(): void
    {
        $secretary = User::factory()->create([
            'role' => User::ROLE_AGENDADOR,
            'permissions' => [User::PERMISSION_WOMEN_CLINIC_REPORTS],
        ]);

        $response = $this->actingAs($secretary)->get(route('women-clinic.reports'));

        $response->assertOk();
    }

    public function test_user_without_reports_permission_is_forbidden(): void
    {
        $agendadorOnly = User::factory()->create([
            'role' => User::ROLE_RECEPCAO_CLINICA,
            'permissions' => [User::PERMISSION_WOMEN_CLINIC_CHECKIN],
        ]);

        $response = $this->actingAs($agendadorOnly)->get(route('women-clinic.reports'));

        $response->assertForbidden();
    }

    public function test_reports_show_panorama_metrics_and_feedback_data(): void
    {
        $viewer = User::factory()->create([
            'role' => User::ROLE_AGENDADOR,
            'permissions' => [User::PERMISSION_WOMEN_CLINIC_REPORTS],
            'email_verified_at' => now(),
        ]);

        $scheduler = User::factory()->create();
        $reception = User::factory()->create();
        $doctor = User::factory()->create();

        $citizenWithFeedback = Citizen::create([
            'cpf' => '90012640960',
            'cpf_hash' => hash('sha256', '90012640960'),
            'full_name' => 'CIDADAO FEEDBACK CLINICA',
            'birth_date' => '1990-01-01',
            'is_resident_assai' => true,
        ]);

        $citizenNoFeedback = Citizen::create([
            'cpf' => '90012640961',
            'cpf_hash' => hash('sha256', '90012640961'),
            'full_name' => 'CIDADAO SEM FEEDBACK',
            'birth_date' => '1991-01-01',
            'is_resident_assai' => true,
        ]);

        WomenClinicAppointment::create([
            'citizen_id' => $citizenWithFeedback->id,
            'scheduler_user_id' => $scheduler->id,
            'reception_user_id' => $reception->id,
            'doctor_user_id' => $doctor->id,
            'scheduled_for' => now()->subHours(2),
            'status' => 'FINALIZADO',
            'checked_in_at' => now()->subHour(),
            'checked_out_at' => now()->subMinutes(20),
            'feedback_score' => 5,
            'feedback_comment' => 'Atendimento excelente',
            'feedback_submitted_at' => now()->subMinutes(10),
            'residence_status' => 'RESIDENTE',
            'gov_assai_level' => '2',
        ]);

        WomenClinicAppointment::create([
            'citizen_id' => $citizenNoFeedback->id,
            'scheduler_user_id' => $scheduler->id,
            'scheduled_for' => now()->addHour(),
            'status' => 'AGENDADO',
            'residence_status' => 'RESIDENTE',
            'gov_assai_level' => '2',
        ]);

        $response = $this->actingAs($viewer)->get(route('women-clinic.reports'));

        $response->assertOk();
        $response->assertSee('CIDADAO FEEDBACK CLINICA');
        $response->assertSee('CIDADAO SEM FEEDBACK');
        $response->assertSee('Tempo médio de espera', false);
        $response->assertSee('Feedbacks recebidos', false);
        $response->assertSee('Nota 5/5');
    }

    public function test_reports_filter_can_show_only_feedback_rows(): void
    {
        $viewer = User::factory()->create([
            'role' => User::ROLE_AGENDADOR,
            'permissions' => [User::PERMISSION_WOMEN_CLINIC_REPORTS],
            'email_verified_at' => now(),
        ]);

        $scheduler = User::factory()->create();

        $citizenWithFeedback = Citizen::create([
            'cpf' => '90012640962',
            'cpf_hash' => hash('sha256', '90012640962'),
            'full_name' => 'CIDADAO FILTRO FEEDBACK',
            'birth_date' => '1990-01-01',
            'is_resident_assai' => true,
        ]);

        $citizenNoFeedback = Citizen::create([
            'cpf' => '90012640963',
            'cpf_hash' => hash('sha256', '90012640963'),
            'full_name' => 'CIDADAO FILTRO SEM FEEDBACK',
            'birth_date' => '1991-01-01',
            'is_resident_assai' => true,
        ]);

        WomenClinicAppointment::create([
            'citizen_id' => $citizenWithFeedback->id,
            'scheduler_user_id' => $scheduler->id,
            'scheduled_for' => now()->subDay(),
            'status' => 'FINALIZADO',
            'feedback_score' => 4,
            'feedback_submitted_at' => now()->subDay()->addHour(),
            'residence_status' => 'RESIDENTE',
            'gov_assai_level' => '2',
        ]);

        WomenClinicAppointment::create([
            'citizen_id' => $citizenNoFeedback->id,
            'scheduler_user_id' => $scheduler->id,
            'scheduled_for' => now()->subDay(),
            'status' => 'FINALIZADO',
            'feedback_score' => null,
            'feedback_submitted_at' => null,
            'residence_status' => 'RESIDENTE',
            'gov_assai_level' => '2',
        ]);

        $response = $this->actingAs($viewer)->get(route('women-clinic.reports', [
            'has_feedback' => 'yes',
        ]));

        $response->assertOk();
        $response->assertSee('CIDADAO FILTRO FEEDBACK');
        $response->assertDontSee('CIDADAO FILTRO SEM FEEDBACK');
    }
}
