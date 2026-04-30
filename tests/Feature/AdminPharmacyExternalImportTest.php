<?php

namespace Tests\Feature;

use App\Models\Citizen;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class AdminPharmacyExternalImportTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_import_external_files_and_create_synthetic_dispensation(): void
    {
        $admin = User::factory()->create([
            'role' => User::ROLE_ADMIN,
            'permissions' => null,
        ]);

        $pharmacist = User::factory()->create([
            'name' => 'ANA FARMACEUTICA',
            'role' => User::ROLE_FARMACIA,
            'permissions' => [User::PERMISSION_CENTRAL_PHARMACY],
        ]);

        $citizen = Citizen::create([
            'cpf' => '90012640970',
            'cpf_hash' => hash('sha256', '90012640970'),
            'full_name' => 'MARIA SILVA',
            'birth_date' => '1990-01-01',
            'is_resident_assai' => true,
            'pharmacy_lock_flag' => false,
            'phone' => '(43) 99999-9999',
        ]);

        [$bethaFile, $txtFile] = $this->buildImportFiles();

        $response = $this->actingAs($admin)->post(route('admin.pharmacy-import.store'), [
            'betha_csv' => $bethaFile,
            'daily_txt' => $txtFile,
        ]);

        $response->assertRedirect(route('admin.reports'));
        $response->assertSessionHasNoErrors();

        $this->assertDatabaseHas('central_pharmacy_requests', [
            'citizen_id' => $citizen->id,
            'status' => 'DISPENSADO',
            'prescription_code' => '68080285',
            'attendant_user_id' => $pharmacist->id,
            'reception_user_id' => $pharmacist->id,
            'medication_name' => 'LEITE',
        ]);

        $citizen->refresh();
        $this->assertTrue((bool) $citizen->pharmacy_lock_flag);

        $this->assertDatabaseHas('pharmacy_external_import_rows', [
            'citizen_id' => $citizen->id,
            'pharmacist_user_id' => $pharmacist->id,
            'external_dispense_number' => '68080285',
            'bypass_detected' => true,
            'recurrence_alert_level' => 'SEM_HISTORICO',
        ]);
    }

    public function test_non_admin_cannot_import_external_files(): void
    {
        $user = User::factory()->create([
            'role' => User::ROLE_FARMACIA,
            'permissions' => [User::PERMISSION_CENTRAL_PHARMACY],
        ]);

        [$bethaFile, $txtFile] = $this->buildImportFiles();

        $response = $this->actingAs($user)->post(route('admin.pharmacy-import.store'), [
            'betha_csv' => $bethaFile,
            'daily_txt' => $txtFile,
        ]);

        $response->assertForbidden();
    }

    public function test_import_is_idempotent_for_same_external_rows(): void
    {
        $admin = User::factory()->create([
            'role' => User::ROLE_ADMIN,
            'permissions' => null,
        ]);

        Citizen::create([
            'cpf' => '90012640971',
            'cpf_hash' => hash('sha256', '90012640971'),
            'full_name' => 'MARIA SILVA',
            'birth_date' => '1990-01-01',
            'is_resident_assai' => true,
            'pharmacy_lock_flag' => false,
            'phone' => '(43) 98888-7777',
        ]);

        [$firstBethaFile, $firstTxtFile] = $this->buildImportFiles();

        $first = $this->actingAs($admin)->post(route('admin.pharmacy-import.store'), [
            'betha_csv' => $firstBethaFile,
            'daily_txt' => $firstTxtFile,
        ]);

        $first->assertRedirect(route('admin.reports'));

        [$secondBethaFile, $secondTxtFile] = $this->buildImportFiles();

        $second = $this->actingAs($admin)->post(route('admin.pharmacy-import.store'), [
            'betha_csv' => $secondBethaFile,
            'daily_txt' => $secondTxtFile,
        ]);

        $second->assertRedirect(route('admin.reports'));
        $second->assertSessionHas('import_summary', function ($payload): bool {
            return is_array($payload)
                && (($payload['summary']['duplicates_skipped'] ?? 0) >= 1);
        });

        $this->assertDatabaseCount('pharmacy_external_import_rows', 1);
        $this->assertDatabaseCount('central_pharmacy_requests', 1);
        $this->assertDatabaseCount('pharmacy_external_imports', 2);
    }

    /**
     * @return array{0:UploadedFile,1:UploadedFile}
     */
    private function buildImportFiles(): array
    {
        $csvContent = implode("\n", [
            'Nome do cliente: MARIA SILVA;;;;;;;;;;1;',
            ';Unidade: FARMACIA MUNICIPAL DE ASSAI;;;;;;;;;;',
            ';;Produto;;Total prescrito;;;;;;;',
            ';;;Data e hora;;;Lote;;Situacao;;;Dispensado',
            ';;LEITE NINHO 800 G;17/04/2026 - 08:00;;0,000;25KH0;;Dispensada;;;1,000',
            ';;;;;;;Total dispensado:;;1,000;;',
        ]);

        $txtContent = implode("\n", [
            'Pagin  1/  1',
            'ESTADO DO PARANA',
            'RELATORIO DE DISPENSAS POR USUARIO - ANALITICO',
            'Data In17/04/2026                Data Fi17/04/2026',
            'Usuario: AmandaKamada                   Profissional: ANA FARMACEUTICA',
            'Unidade de Saude: FARMACIA MUNICIPAL DE ASSAI',
            '17/04/2026',
            'Dispensa   Cliente',
            '68080285   MARIA SILVA',
            'Total de              1',
        ]);

        return [
            UploadedFile::fake()->createWithContent('betha.csv', $csvContent),
            UploadedFile::fake()->createWithContent('usuarios.txt', $txtContent),
        ];
    }
}
