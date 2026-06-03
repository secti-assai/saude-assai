<?php

namespace Tests\Feature;

use App\Models\Citizen;
use App\Models\PharmacyExternalImport;
use App\Models\PharmacyExternalImportRow;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class AdminBorderControlTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $regularUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create([
            'role' => User::ROLE_ADMIN,
        ]);

        $this->regularUser = User::factory()->create([
            'role' => User::ROLE_FARMACIA,
        ]);
    }

    public function test_guests_and_non_admins_cannot_access_border_control()
    {
        // Guests redirected to login
        $this->get('/admin/controle-borda')->assertRedirect('/login');
        $this->get('/admin/controle-borda/exportar')->assertRedirect('/login');
        $this->post('/admin/controle-borda/alternar-bloqueio/1')->assertRedirect('/login');

        // Regular users forbidden (fallback to dashboard or abort 403 based on middleware)
        // Note: web.php uses ->middleware('role:admin')
        $this->actingAs($this->regularUser)->get('/admin/controle-borda')->assertStatus(403);
    }

    public function test_admin_can_access_border_control_view()
    {
        $response = $this->actingAs($this->admin)->get('/admin/controle-borda');
        
        $response->assertStatus(200);
        $response->assertViewIs('admin.border-control');
        $response->assertSee('Controle de Borda');
        $response->assertSee('Filtros de Auditoria');
    }

    public function test_border_control_shows_correct_statistics_and_daily_data()
    {
        $citizen = Citizen::create([
            'cpf' => '12345678901',
            'cpf_hash' => hash('sha256', '12345678901'),
            'full_name' => 'JOAO DA SILVA',
            'birth_date' => '1990-01-01',
            'is_resident_assai' => true,
            'pharmacy_lock_flag' => false,
        ]);

        $import = PharmacyExternalImport::create([
            'uploaded_by_user_id' => $this->admin->id,
            'betha_filename' => 'betha.csv',
            'daily_filename' => 'daily.txt',
            'betha_sha256' => hash('sha256', 'betha'),
            'daily_sha256' => hash('sha256', 'daily'),
            'imported_at' => now(),
            'stats' => [],
        ]);

        // Regular row (bypass_detected = false)
        PharmacyExternalImportRow::create([
            'import_id' => $import->id,
            'row_hash' => hash('sha256', 'row1'),
            'external_dispense_number' => '10001',
            'dispensed_at' => Carbon::parse('2026-05-28 10:00:00'),
            'customer_name_raw' => 'JOAO DA SILVA',
            'customer_name_normalized' => 'JOAO DA SILVA',
            'medication_name_raw' => 'DIPIRONA SODICA 500MG',
            'quantity' => 20,
            'bypass_detected' => false,
            'citizen_id' => $citizen->id,
        ]);

        // Bypass row (bypass_detected = true)
        PharmacyExternalImportRow::create([
            'import_id' => $import->id,
            'row_hash' => hash('sha256', 'row2'),
            'external_dispense_number' => '10002',
            'dispensed_at' => Carbon::parse('2026-05-28 11:30:00'),
            'customer_name_raw' => 'JOAO DA SILVA',
            'customer_name_normalized' => 'JOAO DA SILVA',
            'medication_name_raw' => 'PARACETAMOL 750MG',
            'quantity' => 10,
            'bypass_detected' => true,
            'citizen_id' => $citizen->id,
        ]);

        // High cost row (bypass_detected = true, insulin keyword)
        PharmacyExternalImportRow::create([
            'import_id' => $import->id,
            'row_hash' => hash('sha256', 'row3'),
            'external_dispense_number' => '10003',
            'dispensed_at' => Carbon::parse('2026-05-28 12:00:00'),
            'customer_name_raw' => 'JOAO DA SILVA',
            'customer_name_normalized' => 'JOAO DA SILVA',
            'medication_name_raw' => 'INSULINA GLARGINA 100UI/ML',
            'quantity' => 5,
            'bypass_detected' => true,
            'citizen_id' => $citizen->id,
        ]);

        $response = $this->actingAs($this->admin)->get('/admin/controle-borda?date_start=2026-05-28&date_end=2026-05-28');

        $response->assertStatus(200);
        $response->assertViewHas('stats', [
            'total' => 3,
            'regular' => 1,
            'bypass' => 2,
            'compliance_rate' => 33.3,
            'unique_locked' => 1,
        ]);

        // Daily breakdown checks
        $dailyData = $response->viewData('dailyData');
        $this->assertCount(1, $dailyData);
        $this->assertEquals('28/05/2026', $dailyData[0]['date']);
        $this->assertEquals(3, $dailyData[0]['total']);
        $this->assertEquals(2, $dailyData[0]['bypass']);
        $this->assertEquals(33.3, $dailyData[0]['rate']);

        // Check search functionality
        $responseSearch = $this->actingAs($this->admin)->get('/admin/controle-borda?citizen_search=JOAO');
        $responseSearch->assertSee('DIPIRONA SODICA');
        $responseSearch->assertSee('PARACETAMOL');
        $responseSearch->assertSee('INSULINA GLARGINA');

        $responseBypass = $this->actingAs($this->admin)->get('/admin/controle-borda?bypass_only=1');
        $responseBypass->assertSee('PARACETAMOL');
        $responseBypass->assertSee('INSULINA GLARGINA');
        $responseBypass->assertDontSee('DIPIRONA SODICA');

        // Check high cost only filter
        $responseHighCost = $this->actingAs($this->admin)->get('/admin/controle-borda?high_cost_only=1');
        $responseHighCost->assertSee('INSULINA GLARGINA');
        $responseHighCost->assertDontSee('DIPIRONA SODICA');
        $responseHighCost->assertDontSee('PARACETAMOL');
    }

    public function test_admin_can_toggle_citizen_lock_and_audits_correctly()
    {
        $citizen = Citizen::create([
            'cpf' => '12345678901',
            'cpf_hash' => hash('sha256', '12345678901'),
            'full_name' => 'JOAO DA SILVA',
            'birth_date' => '1990-01-01',
            'is_resident_assai' => true,
            'pharmacy_lock_flag' => false,
        ]);

        $this->assertFalse($citizen->pharmacy_lock_flag);

        // Toggle to true
        $response1 = $this->actingAs($this->admin)
            ->post("/admin/controle-borda/alternar-bloqueio/{$citizen->id}");

        $response1->assertRedirect();
        $this->assertTrue($citizen->fresh()->pharmacy_lock_flag);

        // Check audit log
        $this->assertDatabaseHas('audit_logs', [
            'module' => 'ADMIN_CONTROLE_BORDA',
            'action' => 'BLOQUEIO_FARMA_ATIVADO',
            'entity_type' => Citizen::class,
            'entity_id' => $citizen->id,
            'user_id' => $this->admin->id,
        ]);

        // Toggle back to false
        $response2 = $this->actingAs($this->admin)
            ->post("/admin/controle-borda/alternar-bloqueio/{$citizen->id}");

        $response2->assertRedirect();
        $this->assertFalse($citizen->fresh()->pharmacy_lock_flag);

        $this->assertDatabaseHas('audit_logs', [
            'module' => 'ADMIN_CONTROLE_BORDA',
            'action' => 'BLOQUEIO_FARMA_DESATIVADO',
            'entity_type' => Citizen::class,
            'entity_id' => $citizen->id,
            'user_id' => $this->admin->id,
        ]);
    }

    public function test_admin_can_export_border_control_csv()
    {
        $citizen = Citizen::create([
            'cpf' => '12345678901',
            'cpf_hash' => hash('sha256', '12345678901'),
            'full_name' => 'JOAO DA SILVA',
            'birth_date' => '1990-01-01',
            'is_resident_assai' => true,
            'pharmacy_lock_flag' => true,
        ]);

        $import = PharmacyExternalImport::create([
            'uploaded_by_user_id' => $this->admin->id,
            'betha_filename' => 'betha.csv',
            'daily_filename' => 'daily.txt',
            'betha_sha256' => hash('sha256', 'betha'),
            'daily_sha256' => hash('sha256', 'daily'),
            'imported_at' => now(),
            'stats' => [],
        ]);

        PharmacyExternalImportRow::create([
            'import_id' => $import->id,
            'row_hash' => hash('sha256', 'row1'),
            'external_dispense_number' => '99999',
            'dispensed_at' => Carbon::parse('2026-05-28 10:00:00'),
            'customer_name_raw' => 'JOAO DA SILVA',
            'customer_name_normalized' => 'JOAO DA SILVA',
            'medication_name_raw' => 'IBUPROFENO 600MG',
            'quantity' => 30,
            'bypass_detected' => true,
            'citizen_id' => $citizen->id,
        ]);

        $response = $this->actingAs($this->admin)
            ->get('/admin/controle-borda/exportar?date_start=2026-05-28&date_end=2026-05-28');

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'text/csv; charset=utf-8');

        $content = $response->streamedContent();

        // Validate UTF-8 BOM presence
        $this->assertStringStartsWith(chr(0xEF).chr(0xBB).chr(0xBF), $content);

        // Validate headers in CSV
        $this->assertStringContainsString('Numero Guia Externa', $content);
        $this->assertStringContainsString('Data Dispensacao', $content);
        $this->assertStringContainsString('CPF Cidadao', $content);
        $this->assertStringContainsString('Nome Cidadao', $content);

        // Validate records details presence
        $this->assertStringContainsString('99999', $content);
        $this->assertStringContainsString('JOAO DA SILVA', $content);
        $this->assertStringContainsString('IBUPROFENO 600MG', $content);
        $this->assertStringContainsString('SIM - BYPASS', $content);
        // Decrypted CPF check
        $this->assertStringContainsString('12345678901', $content);
    }
}
