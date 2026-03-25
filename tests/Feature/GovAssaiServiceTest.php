<?php

namespace Tests\Feature;

use App\Services\GovAssaiService;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class GovAssaiServiceTest extends TestCase
{
    public function test_it_validates_invalid_cpf_format_before_request(): void
    {
        Http::fake();

        $service = app(GovAssaiService::class);
        $result = $service->fetchCitizenByCpf('123');

        $this->assertSame(400, $result['status']);
        $this->assertFalse($result['success']);
        $this->assertSame('INVALID_CPF_FORMAT', $result['error_code']);
        Http::assertNothingSent();
    }

    public function test_it_calls_gov_assai_and_returns_success_payload(): void
    {
        config()->set('services.gov_assai.base_url', 'https://gov-assai.test');
        config()->set('services.gov_assai.api_key', 'test-key');

        Http::fake([
            'https://gov-assai.test/api/saude/cidadaos/cpf/*' => Http::response([
                'success' => true,
                'message' => 'Consulta realizada com sucesso',
                'data' => [
                    'cidadao' => [
                        'nome' => 'MARIA TESTE',
                        'data_nascimento' => '1990-05-12',
                    ],
                ],
            ], 200),
        ]);

        $service = app(GovAssaiService::class);
        $result = $service->fetchCitizenByCpf('123.456.789-01');

        $this->assertSame(200, $result['status']);
        $this->assertTrue($result['success']);
        $this->assertSame('MARIA TESTE', data_get($result['data'], 'cidadao.nome'));

        Http::assertSent(function ($request) {
            return $request->url() === 'https://gov-assai.test/api/saude/cidadaos/cpf/12345678901'
                && $request->hasHeader('X-API-Key', 'test-key');
        });
    }

    public function test_it_maps_not_found_to_nao_residente(): void
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

        $service = app(GovAssaiService::class);

        $this->assertSame('NAO_RESIDENTE', $service->validateResidence('12345678901'));
    }

    public function test_it_returns_controlled_error_on_connection_failure(): void
    {
        config()->set('services.gov_assai.base_url', 'https://gov-assai.test');
        config()->set('services.gov_assai.api_key', 'test-key');

        Http::fake([
            'https://gov-assai.test/api/saude/cidadaos/cpf/*' => function () {
                throw new ConnectionException('Connection failed');
            },
        ]);

        $service = app(GovAssaiService::class);
        $result = $service->fetchCitizenByCpf('12345678901');

        $this->assertSame(503, $result['status']);
        $this->assertFalse($result['success']);
        $this->assertSame('GOV_ASSAI_UNAVAILABLE', $result['error_code']);
    }
}
