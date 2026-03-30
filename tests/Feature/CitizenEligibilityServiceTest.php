<?php

namespace Tests\Feature;

use App\Services\CitizenEligibilityService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use stdClass;
use Tests\TestCase;

class CitizenEligibilityServiceTest extends TestCase
{
    use RefreshDatabase;

    private function fakeGovAssaiResponse(array $data, int $status = 200, bool $success = true, ?string $message = null): void
    {
        config()->set('services.gov_assai.base_url', 'https://gov-assai.test');
        config()->set('services.gov_assai.api_key', 'test-key');

        Http::fake([
            'https://gov-assai.test/api/saude/cidadaos/cpf/*' => Http::response([
                'success' => $success,
                'message' => $message ?? ($success ? 'Consulta realizada com sucesso' : 'Falha na consulta'),
                'error_code' => $success ? null : 'GOV_ERROR',
                'data' => $success ? $data : null,
            ], $status),
        ]);
    }

    public function test_it_accepts_scalar_level_from_gov_assai_nivel(): void
    {
        $this->fakeGovAssaiResponse([
            'cidadao' => [
                'nome' => 'MARIA SILVA',
                'data_nascimento' => '1990-01-15',
            ],
            'gov_assai' => [
                'nivel' => 2,
            ],
        ]);

        $result = app(CitizenEligibilityService::class)->validateAndSync('12345678909');

        $this->assertTrue($result['eligible']);
        $this->assertSame('2', $result['gov_assai_level']);
        $this->assertNotNull($result['citizen']);
    }

    public function test_it_accepts_level_when_nested_in_array_value_key(): void
    {
        $this->fakeGovAssaiResponse([
            'cidadao' => [
                'nome' => 'ANA SOUZA',
                'data_nascimento' => '1993-02-20',
            ],
            'gov_assai' => [
                'nivel' => ['value' => 2],
            ],
        ]);

        $result = app(CitizenEligibilityService::class)->validateAndSync('12345678909');

        $this->assertTrue($result['eligible']);
        $this->assertSame('2', $result['gov_assai_level']);
    }

    public function test_it_accepts_level_when_nested_in_array_codigo_key(): void
    {
        $this->fakeGovAssaiResponse([
            'cidadao' => [
                'nome' => 'CARLA OLIVEIRA',
                'data_nascimento' => '1992-03-10',
            ],
            'gov_assai' => [
                'nivel_conta' => ['codigo' => '2'],
            ],
        ]);

        $result = app(CitizenEligibilityService::class)->validateAndSync('12345678909');

        $this->assertTrue($result['eligible']);
        $this->assertSame('2', $result['gov_assai_level']);
    }

    public function test_it_accepts_level_when_value_is_stdclass(): void
    {
        $gov = new stdClass();
        $gov->nivel = new stdClass();
        $gov->nivel->valor = 2;

        $this->fakeGovAssaiResponse([
            'cidadao' => [
                'nome' => 'PAULA TESTE',
                'data_nascimento' => '1995-04-25',
            ],
            'gov_assai' => $gov,
        ]);

        $result = app(CitizenEligibilityService::class)->validateAndSync('12345678909');

        $this->assertTrue($result['eligible']);
        $this->assertSame('2', $result['gov_assai_level']);
    }

    public function test_it_blocks_when_level_is_below_two(): void
    {
        $this->fakeGovAssaiResponse([
            'cidadao' => [
                'nome' => 'JOANA TESTE',
                'data_nascimento' => '1998-06-18',
            ],
            'gov_assai' => [
                'nivel' => 1,
            ],
        ]);

        $result = app(CitizenEligibilityService::class)->validateAndSync('12345678909');

        $this->assertFalse($result['eligible']);
        $this->assertSame('GOV_ASSAI_LEVEL_INSUFFICIENT', $result['error_code']);
        $this->assertSame('1', $result['gov_assai_level']);
    }

    public function test_it_blocks_when_level_cannot_be_resolved_from_payload(): void
    {
        $this->fakeGovAssaiResponse([
            'cidadao' => [
                'nome' => 'FERNANDA TESTE',
                'data_nascimento' => '1987-11-02',
            ],
            'gov_assai' => [
                'nivel' => [],
            ],
        ]);

        $result = app(CitizenEligibilityService::class)->validateAndSync('12345678909');

        $this->assertFalse($result['eligible']);
        $this->assertSame('GOV_ASSAI_LEVEL_INSUFFICIENT', $result['error_code']);
        $this->assertNull($result['gov_assai_level']);
    }

    public function test_it_returns_nao_residente_when_gov_assai_returns_404(): void
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

        $result = app(CitizenEligibilityService::class)->validateAndSync('12345678909');

        $this->assertFalse($result['eligible']);
        $this->assertSame('NAO_RESIDENTE', $result['residence_status']);
    }

    public function test_it_blocks_when_required_citizen_data_is_missing_even_with_level_two(): void
    {
        $this->fakeGovAssaiResponse([
            'cidadao' => [
                'nome' => 'SEM DATA',
                'data_nascimento' => null,
            ],
            'gov_assai' => [
                'nivel' => 2,
            ],
        ]);

        $result = app(CitizenEligibilityService::class)->validateAndSync('12345678909');

        $this->assertFalse($result['eligible']);
        $this->assertSame('GOV_ASSAI_INCOMPLETE_DATA', $result['error_code']);
    }
}
