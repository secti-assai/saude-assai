<?php

namespace Tests\Feature;

use App\Models\CentralPharmacyRequest;
use App\Models\Citizen;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class CentralPharmacyReportsModuleTest extends TestCase
{
    use RefreshDatabase;

    public function test_reports_default_view_includes_non_dispensed_and_dispensed_records(): void
    {
        $viewer = User::factory()->create([
            'role' => User::ROLE_FARMACIA,
            'permissions' => [User::PERMISSION_CENTRAL_PHARMACY_REPORTS],
        ]);

        $reception = User::factory()->create();
        $attendant = User::factory()->create();

        $dispensedCitizen = Citizen::create([
            'cpf' => '90012640950',
            'cpf_hash' => hash('sha256', '90012640950'),
            'full_name' => 'CIDADAO DISPENSADO PADRAO',
            'birth_date' => '1990-01-01',
            'is_resident_assai' => true,
            'pharmacy_lock_flag' => false,
            'phone' => '(43) 95555-5555',
        ]);

        $nonDispensedCitizen = Citizen::create([
            'cpf' => '90012640951',
            'cpf_hash' => hash('sha256', '90012640951'),
            'full_name' => 'CIDADAO NAO DISPENSADO PADRAO',
            'birth_date' => '1991-01-01',
            'is_resident_assai' => false,
            'pharmacy_lock_flag' => true,
            'phone' => '(43) 94444-4444',
        ]);

        CentralPharmacyRequest::create([
            'citizen_id' => $dispensedCitizen->id,
            'reception_user_id' => $reception->id,
            'attendant_user_id' => $attendant->id,
            'prescription_date' => now()->toDateString(),
            'prescriber_name' => 'Dr. Dispensado',
            'medication_name' => 'MEDICACAO',
            'concentration' => '500mg',
            'quantity' => 1,
            'dosage' => '1 comprimido',
            'gov_assai_level' => '2',
            'status' => 'DISPENSADO',
            'residence_status' => 'RESIDENTE',
            'dispensed_at' => now(),
        ]);

        CentralPharmacyRequest::create([
            'citizen_id' => $nonDispensedCitizen->id,
            'reception_user_id' => $reception->id,
            'attendant_user_id' => $attendant->id,
            'prescription_date' => now()->toDateString(),
            'prescriber_name' => null,
            'medication_name' => 'LEITE',
            'concentration' => null,
            'quantity' => 0,
            'dosage' => null,
            'gov_assai_level' => '1',
            'status' => 'NAO_DISPENSADO',
            'residence_status' => 'PENDENTE',
            'refusal_reason' => 'Dispensacao bloqueada por nivel insuficiente',
            'dispensed_at' => null,
        ]);

        $response = $this->actingAs($viewer)->get(route('central-pharmacy.reports'));

        $response->assertOk();
        $response->assertSee('CIDADAO DISPENSADO PADRAO');
        $response->assertSee('CIDADAO NAO DISPENSADO PADRAO');
        $response->assertSee('NAO_DISPENSADO');
    }

    public function test_blocked_no_dispense_is_recorded_and_visible_in_reports(): void
    {
        config()->set('services.gov_assai.base_url', 'https://gov-assai.test');
        config()->set('services.gov_assai.api_key', 'test-key');

        Http::fake([
            'https://gov-assai.test/api/saude/cidadaos/cpf/*' => Http::response([
                'success' => true,
                'message' => 'Consulta realizada com sucesso',
                'data' => [
                    'cidadao' => [
                        'nome' => 'CIDADAO BLOQUEADO',
                        'data_nascimento' => '1990-01-01',
                    ],
                    'gov_assai' => [
                        'nivel' => 1,
                    ],
                ],
            ], 200),
        ]);

        $pharmacyUser = User::factory()->create([
            'role' => User::ROLE_FARMACIA,
            'permissions' => [
                User::PERMISSION_CENTRAL_PHARMACY,
                User::PERMISSION_CENTRAL_PHARMACY_REPORTS,
            ],
        ]);

        $citizen = Citizen::create([
            'cpf' => '90012640932',
            'cpf_hash' => hash('sha256', '90012640932'),
            'full_name' => 'CIDADAO BLOQUEADO',
            'birth_date' => '1990-01-01',
            'is_resident_assai' => false,
            'pharmacy_lock_flag' => true,
            'phone' => '(43) 99999-1234',
        ]);

        $postResponse = $this->actingAs($pharmacyUser)->post(route('central-pharmacy.unified.no-dispense-blocked'), [
            'cpf' => '900.126.409-32',
            'dispense_category' => 'LEITE',
        ]);

        $postResponse->assertRedirect(route('central-pharmacy.unified'));
        $postResponse->assertSessionHasNoErrors();

        $this->assertDatabaseHas('central_pharmacy_requests', [
            'citizen_id' => $citizen->id,
            'status' => 'NAO_DISPENSADO',
            'medication_name' => 'LEITE',
            'quantity' => 0,
        ]);

        $reportResponse = $this->actingAs($pharmacyUser)->get(route('central-pharmacy.reports', [
            'status' => 'NAO_DISPENSADO',
            'dispense_category' => 'LEITE',
            'citizen_name' => 'BLOQUEADO',
        ]));

        $reportResponse->assertOk();
        $reportResponse->assertSee('CIDADAO BLOQUEADO');
        $reportResponse->assertSee('NAO_DISPENSADO');
    }

    public function test_admin_can_access_pharmacy_reports_module(): void
    {
        $admin = User::factory()->create([
            'role' => User::ROLE_ADMIN,
            'permissions' => null,
        ]);

        $response = $this->actingAs($admin)->get(route('central-pharmacy.reports'));

        $response->assertOk();
    }

    public function test_user_with_reports_permission_can_access_module(): void
    {
        $secretary = User::factory()->create([
            'role' => User::ROLE_FARMACIA,
            'permissions' => [User::PERMISSION_CENTRAL_PHARMACY_REPORTS],
        ]);

        $response = $this->actingAs($secretary)->get(route('central-pharmacy.reports'));

        $response->assertOk();
    }

    public function test_user_without_reports_permission_is_forbidden(): void
    {
        $pharmacyUser = User::factory()->create([
            'role' => User::ROLE_FARMACIA,
            'permissions' => [User::PERMISSION_CENTRAL_PHARMACY],
        ]);

        $response = $this->actingAs($pharmacyUser)->get(route('central-pharmacy.reports'));

        $response->assertForbidden();
    }

    public function test_reports_filter_can_show_only_citizens_pending_level_two_validation(): void
    {
        $viewer = User::factory()->create([
            'role' => User::ROLE_FARMACIA,
            'permissions' => [User::PERMISSION_CENTRAL_PHARMACY_REPORTS],
        ]);

        $reception = User::factory()->create();
        $attendant = User::factory()->create();

        $pendingCitizen = Citizen::create([
            'cpf' => '90012640930',
            'cpf_hash' => hash('sha256', '90012640930'),
            'full_name' => 'CIDADAO PENDENTE',
            'birth_date' => '1990-01-01',
            'is_resident_assai' => false,
            'pharmacy_lock_flag' => true,
            'phone' => '(43) 99999-9999',
        ]);

        $regularCitizen = Citizen::create([
            'cpf' => '90012640931',
            'cpf_hash' => hash('sha256', '90012640931'),
            'full_name' => 'CIDADAO REGULAR',
            'birth_date' => '1991-01-01',
            'is_resident_assai' => true,
            'pharmacy_lock_flag' => false,
            'phone' => '(43) 98888-8888',
        ]);

        CentralPharmacyRequest::create([
            'citizen_id' => $pendingCitizen->id,
            'reception_user_id' => $reception->id,
            'attendant_user_id' => $attendant->id,
            'prescription_date' => now()->toDateString(),
            'prescriber_name' => 'Dr. Pendente',
            'medication_name' => 'LEITE',
            'concentration' => '500mg',
            'quantity' => 1,
            'dosage' => '1 comprimido',
            'gov_assai_level' => '1',
            'status' => 'DISPENSADO',
            'residence_status' => 'RESIDENTE',
            'dispensed_at' => now(),
        ]);

        CentralPharmacyRequest::create([
            'citizen_id' => $regularCitizen->id,
            'reception_user_id' => $reception->id,
            'attendant_user_id' => $attendant->id,
            'prescription_date' => now()->toDateString(),
            'prescriber_name' => 'Dr. Regular',
            'medication_name' => 'SUPLEMENTO',
            'concentration' => '50mg',
            'quantity' => 1,
            'dosage' => '1 comprimido',
            'gov_assai_level' => '2',
            'status' => 'DISPENSADO',
            'residence_status' => 'RESIDENTE',
            'dispensed_at' => now(),
        ]);

        $response = $this->actingAs($viewer)->get(route('central-pharmacy.reports', [
            'needs_validation' => 'yes',
            'status' => 'DISPENSADOS',
        ]));

        $response->assertOk();
        $response->assertSee('CIDADAO PENDENTE');
        $response->assertDontSee('CIDADAO REGULAR');
    }

    public function test_reports_filter_can_show_only_selected_dispense_category(): void
    {
        $viewer = User::factory()->create([
            'role' => User::ROLE_FARMACIA,
            'permissions' => [User::PERMISSION_CENTRAL_PHARMACY_REPORTS],
        ]);

        $reception = User::factory()->create();
        $attendant = User::factory()->create();

        $milkCitizen = Citizen::create([
            'cpf' => '90012640940',
            'cpf_hash' => hash('sha256', '90012640940'),
            'full_name' => 'CIDADAO LEITE',
            'birth_date' => '1990-01-01',
            'is_resident_assai' => true,
            'pharmacy_lock_flag' => false,
            'phone' => '(43) 97777-7777',
        ]);

        $supplementCitizen = Citizen::create([
            'cpf' => '90012640941',
            'cpf_hash' => hash('sha256', '90012640941'),
            'full_name' => 'CIDADAO SUPLEMENTO',
            'birth_date' => '1991-01-01',
            'is_resident_assai' => true,
            'pharmacy_lock_flag' => false,
            'phone' => '(43) 96666-6666',
        ]);

        CentralPharmacyRequest::create([
            'citizen_id' => $milkCitizen->id,
            'reception_user_id' => $reception->id,
            'attendant_user_id' => $attendant->id,
            'prescription_date' => now()->toDateString(),
            'prescriber_name' => 'Dr. Leite',
            'medication_name' => 'LEITE',
            'concentration' => 'N/A',
            'quantity' => 1,
            'dosage' => 'N/A',
            'gov_assai_level' => '2',
            'status' => 'DISPENSADO',
            'residence_status' => 'RESIDENTE',
            'dispensed_at' => now(),
        ]);

        CentralPharmacyRequest::create([
            'citizen_id' => $supplementCitizen->id,
            'reception_user_id' => $reception->id,
            'attendant_user_id' => $attendant->id,
            'prescription_date' => now()->toDateString(),
            'prescriber_name' => 'Dr. Suplemento',
            'medication_name' => 'SUPLEMENTO',
            'concentration' => 'N/A',
            'quantity' => 1,
            'dosage' => 'N/A',
            'gov_assai_level' => '2',
            'status' => 'DISPENSADO',
            'residence_status' => 'RESIDENTE',
            'dispensed_at' => now(),
        ]);

        $response = $this->actingAs($viewer)->get(route('central-pharmacy.reports', [
            'dispense_category' => 'LEITE',
            'status' => 'DISPENSADOS',
        ]));

        $response->assertOk();
        $response->assertSee('CIDADAO LEITE');
        $response->assertDontSee('CIDADAO SUPLEMENTO');
    }

    public function test_reports_prefer_external_attendant_name_when_present_in_notes(): void
    {
        $viewer = User::factory()->create([
            'role' => User::ROLE_FARMACIA,
            'permissions' => [User::PERMISSION_CENTRAL_PHARMACY_REPORTS],
        ]);

        $importer = User::factory()->create([
            'name' => 'ADMIN IMPORTADOR TESTE',
            'role' => User::ROLE_ADMIN,
        ]);

        $citizen = Citizen::create([
            'cpf' => '90012640949',
            'cpf_hash' => hash('sha256', '90012640949'),
            'full_name' => 'CIDADAO EXTERNO ATENDENTE',
            'birth_date' => '1990-01-01',
            'is_resident_assai' => false,
            'pharmacy_lock_flag' => true,
            'phone' => '(43) 95555-4949',
        ]);

        CentralPharmacyRequest::create([
            'citizen_id' => $citizen->id,
            'reception_user_id' => $importer->id,
            'attendant_user_id' => $importer->id,
            'prescription_date' => now()->toDateString(),
            'prescriber_name' => 'Dr. Externo',
            'medication_name' => 'MEDICACAO',
            'concentration' => '500mg',
            'quantity' => 1,
            'dosage' => '1 comprimido',
            'gov_assai_level' => '0',
            'status' => 'DISPENSADO',
            'residence_status' => 'PENDENTE',
            'notes' => 'IMPORTACAO EXTERNA BETHA/TXT | Farmaceutica no arquivo externo: MARIA DA SILVA FERREIRA | Numero da dispensa externa: 123456',
            'dispensed_at' => now(),
        ]);

        $response = $this->actingAs($viewer)->get(route('central-pharmacy.reports', [
            'citizen_name' => 'EXTERNO ATENDENTE',
        ]));

        $response->assertOk();
        $response->assertSee('MARIA DA SILVA FERREIRA');

        $csvResponse = $this->actingAs($viewer)->get(route('central-pharmacy.reports.export-csv', [
            'date_start' => now()->toDateString(),
            'date_end' => now()->toDateString(),
            'citizen_name' => 'EXTERNO ATENDENTE',
        ]));

        $csvResponse->assertOk();

        $csv = ltrim($csvResponse->streamedContent(), "\xEF\xBB\xBF");
        $this->assertStringContainsString(';"MARIA DA SILVA FERREIRA";"ADMIN IMPORTADOR TESTE";', $csv);
    }

    public function test_reports_csv_export_downloads_all_records_for_selected_period(): void
    {
        $viewer = User::factory()->create([
            'role' => User::ROLE_FARMACIA,
            'permissions' => [User::PERMISSION_CENTRAL_PHARMACY_REPORTS],
        ]);

        $reception = User::factory()->create();
        $attendant = User::factory()->create();

        $insideOneCitizen = Citizen::create([
            'cpf' => '90012640960',
            'cpf_hash' => hash('sha256', '90012640960'),
            'full_name' => 'CIDADAO CSV DENTRO 1',
            'birth_date' => '1990-01-01',
            'is_resident_assai' => true,
            'pharmacy_lock_flag' => false,
            'phone' => '(43) 95555-1111',
        ]);

        $insideTwoCitizen = Citizen::create([
            'cpf' => '90012640961',
            'cpf_hash' => hash('sha256', '90012640961'),
            'full_name' => 'CIDADAO CSV DENTRO 2',
            'birth_date' => '1991-01-01',
            'is_resident_assai' => true,
            'pharmacy_lock_flag' => true,
            'phone' => '(43) 95555-2222',
        ]);

        $outsideCitizen = Citizen::create([
            'cpf' => '90012640962',
            'cpf_hash' => hash('sha256', '90012640962'),
            'full_name' => 'CIDADAO CSV FORA',
            'birth_date' => '1992-01-01',
            'is_resident_assai' => false,
            'pharmacy_lock_flag' => true,
            'phone' => '(43) 95555-3333',
        ]);

        $insideOne = CentralPharmacyRequest::create([
            'citizen_id' => $insideOneCitizen->id,
            'reception_user_id' => $reception->id,
            'attendant_user_id' => $attendant->id,
            'prescription_date' => now()->toDateString(),
            'prescriber_name' => 'Dr. Csv 1',
            'medication_name' => 'MEDICACAO',
            'concentration' => '250mg',
            'quantity' => 1,
            'dosage' => '1 comprimido',
            'gov_assai_level' => '2',
            'status' => 'DISPENSADO',
            'residence_status' => 'RESIDENTE',
            'dispensed_at' => now()->subDays(2),
        ]);

        $insideTwo = CentralPharmacyRequest::create([
            'citizen_id' => $insideTwoCitizen->id,
            'reception_user_id' => $reception->id,
            'attendant_user_id' => $attendant->id,
            'prescription_date' => now()->toDateString(),
            'prescriber_name' => 'Dr. Csv 2',
            'medication_name' => 'LEITE',
            'concentration' => 'N/A',
            'quantity' => 1,
            'dosage' => 'N/A',
            'gov_assai_level' => '1',
            'status' => 'NAO_DISPENSADO',
            'residence_status' => 'PENDENTE',
            'refusal_reason' => 'Sem estoque',
            'dispensed_at' => null,
        ]);

        $outside = CentralPharmacyRequest::create([
            'citizen_id' => $outsideCitizen->id,
            'reception_user_id' => $reception->id,
            'attendant_user_id' => $attendant->id,
            'prescription_date' => now()->subDays(20)->toDateString(),
            'prescriber_name' => 'Dr. Fora',
            'medication_name' => 'SUPLEMENTO',
            'concentration' => 'N/A',
            'quantity' => 1,
            'dosage' => 'N/A',
            'gov_assai_level' => '1',
            'status' => 'DISPENSADO',
            'residence_status' => 'NAO_RESIDENTE',
            'dispensed_at' => now()->subDays(20),
        ]);

        CentralPharmacyRequest::query()->whereKey($insideOne->id)->update([
            'created_at' => now()->subDays(2),
            'updated_at' => now()->subDays(2),
        ]);

        CentralPharmacyRequest::query()->whereKey($insideTwo->id)->update([
            'created_at' => now()->subDay(),
            'updated_at' => now()->subDay(),
        ]);

        CentralPharmacyRequest::query()->whereKey($outside->id)->update([
            'created_at' => now()->subDays(20),
            'updated_at' => now()->subDays(20),
        ]);

        $response = $this->actingAs($viewer)->get(route('central-pharmacy.reports.export-csv', [
            'date_start' => now()->subDays(3)->toDateString(),
            'date_end' => now()->toDateString(),
            'status' => 'TODOS',
        ]));

        $response->assertOk();
        $response->assertDownload();
        $response->assertHeader('Content-Type', 'text/csv; charset=UTF-8');

        $csv = ltrim($response->streamedContent(), "\xEF\xBB\xBF");

        $this->assertStringContainsString('id_atendimento;data_registro;data_atendimento;cidadao', $csv);
        $this->assertStringContainsString('CIDADAO CSV DENTRO 1', $csv);
        $this->assertStringContainsString('CIDADAO CSV DENTRO 2', $csv);
        $this->assertStringNotContainsString('CIDADAO CSV FORA', $csv);
    }

    public function test_user_without_reports_permission_cannot_export_reports_csv(): void
    {
        $pharmacyUser = User::factory()->create([
            'role' => User::ROLE_FARMACIA,
            'permissions' => [User::PERMISSION_CENTRAL_PHARMACY],
        ]);

        $response = $this->actingAs($pharmacyUser)->get(route('central-pharmacy.reports.export-csv'));

        $response->assertForbidden();
    }
}
