<?php

namespace Tests\Feature;

use App\Models\Citizen;
use App\Models\User;
use App\Services\PharmacyDispensationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class PharmacyDispensationServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_resolves_level_two_from_nested_payload(): void
    {
        config()->set('services.gov_assai.base_url', 'https://gov-assai.test');
        config()->set('services.gov_assai.api_key', 'test-key');

        Http::fake([
            'https://gov-assai.test/api/saude/cidadaos/cpf/*' => Http::response([
                'success' => true,
                'message' => 'Consulta realizada com sucesso',
                'data' => [
                    'cidadao' => [
                        'nome' => 'KAWAN TESTE',
                        'data_nascimento' => '1990-01-01',
                    ],
                    'gov_assai' => [
                        'nivel' => ['value' => 2],
                    ],
                ],
            ], 200),
        ]);

        $info = app(PharmacyDispensationService::class)->getCitizenInfo('123.456.789-09');

        $this->assertTrue($info['success']);
        $this->assertSame('FOUND', $info['gov_lookup_status']);
        $this->assertTrue($info['gov_assai_found']);
        $this->assertSame(2, $info['level']);
    }

    public function test_it_marks_not_found_only_when_http_404(): void
    {
        config()->set('services.gov_assai.base_url', 'https://gov-assai.test');
        config()->set('services.gov_assai.api_key', 'test-key');

        Http::fake([
            'https://gov-assai.test/api/saude/cidadaos/cpf/*' => Http::response([
                'success' => false,
                'message' => 'Cidadao nao encontrado',
                'error_code' => 'CITIZEN_NOT_FOUND',
            ], 404),
        ]);

        $info = app(PharmacyDispensationService::class)->getCitizenInfo('12345678909');

        $this->assertTrue($info['success']);
        $this->assertSame('NOT_FOUND', $info['gov_lookup_status']);
        $this->assertFalse($info['gov_assai_found']);
        $this->assertSame(0, $info['level']);
    }

    public function test_it_marks_unavailable_for_non_404_failures(): void
    {
        config()->set('services.gov_assai.base_url', 'https://gov-assai.test');
        config()->set('services.gov_assai.api_key', 'test-key');

        Http::fake([
            'https://gov-assai.test/api/saude/cidadaos/cpf/*' => Http::response([
                'success' => false,
                'message' => 'Gov.Assai indisponivel no momento.',
                'error_code' => 'GOV_ASSAI_UNAVAILABLE',
            ], 503),
        ]);

        $service = app(PharmacyDispensationService::class);
        $info = $service->getCitizenInfo('12345678909');

        $this->assertTrue($info['success']);
        $this->assertSame('UNAVAILABLE', $info['gov_lookup_status']);
        $this->assertFalse($info['gov_assai_found']);

        $result = $service->processDispensation([
            'cpf' => '12345678909',
            'medication_name' => 'Dipirona',
            'quantity' => 1,
        ], 1, new Request());

        $this->assertFalse($result['success']);
        $this->assertSame('RETRY_GOV_LOOKUP', $result['action']);
    }

    public function test_it_blocks_manual_citizen_when_not_found_in_gov_assai(): void
    {
        config()->set('services.gov_assai.base_url', 'https://gov-assai.test');
        config()->set('services.gov_assai.api_key', 'test-key');

        Http::fake([
            'https://gov-assai.test/api/saude/cidadaos/cpf/*' => Http::response([
                'success' => false,
                'message' => 'Cidadao nao encontrado',
                'error_code' => 'CITIZEN_NOT_FOUND',
            ], 404),
        ]);

        $service = app(PharmacyDispensationService::class);
        $attendant = User::factory()->create();

        $result = $service->processDispensation([
            'cpf' => '90012640930',
            'full_name' => 'Alexsander Kakubo',
            'phone' => '(43) 99905-8050',
            'dispense_category' => 'LEITE',
        ], (int) $attendant->id, new Request());

        $this->assertFalse($result['success']);
        $this->assertSame('BLOCKED', $result['action']);

        $citizen = Citizen::where('cpf_hash', hash('sha256', '90012640930'))->first();
        $this->assertNotNull($citizen);
        $this->assertSame('ALEXSANDER KAKUBO', $citizen->full_name);
        
        $request = \App\Models\CentralPharmacyRequest::where('citizen_id', $citizen->id)->first();
        $this->assertNull($request); // Should not have logged a dispensation
    }

    public function test_it_blocks_level_one_immediately_and_dispenses_when_level_two(): void
    {
        config()->set('services.gov_assai.base_url', 'https://gov-assai.test');
        config()->set('services.gov_assai.api_key', 'test-key');

        Http::fake([
            'https://gov-assai.test/api/saude/cidadaos/cpf/*' => Http::sequence()
                ->push([
                    'success' => true,
                    'message' => 'Consulta realizada com sucesso',
                    'data' => [
                        'cidadao' => [
                            'nome' => 'ALEXSANDER KAKUBO',
                            'data_nascimento' => '1994-01-20',
                        ],
                        'gov_assai' => [
                            'nivel' => 1,
                        ],
                    ],
                ], 200)
                ->push([
                    'success' => true,
                    'message' => 'Consulta realizada com sucesso',
                    'data' => [
                        'cidadao' => [
                            'nome' => 'ALEXSANDER KAKUBO',
                            'data_nascimento' => '1994-01-20',
                        ],
                        'gov_assai' => [
                            'nivel' => 2,
                        ],
                    ],
                ], 200),
        ]);

        $service = app(PharmacyDispensationService::class);
        $attendant = User::factory()->create();

        $firstAttempt = $service->processDispensation([
            'cpf' => '90012640930',
            'full_name' => 'Alexsander Kakubo',
            'phone' => '(43) 99905-8050',
            'dispense_category' => 'MEDICACAO',
        ], (int) $attendant->id, new Request());

        $this->assertFalse($firstAttempt['success']);
        $this->assertSame('BLOCKED', $firstAttempt['action']);

        $secondAttempt = $service->processDispensation([
            'cpf' => '90012640930',
            'full_name' => 'Alexsander Kakubo',
            'phone' => '(43) 99905-8050',
            'dispense_category' => 'SUPLEMENTO',
        ], (int) $attendant->id, new Request());

        $this->assertTrue($secondAttempt['success']);
        $this->assertSame('DISPENSED', $secondAttempt['action']);
    }
}
