<?php

namespace App\Services;

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Throwable;

class GovAssaiService
{
    public function normalizeCpf(string $cpf): string
    {
        return preg_replace('/\D+/', '', $cpf) ?? '';
    }

    public function isValidCpfFormat(string $cpf): bool
    {
        return (bool) preg_match('/^\d{11}$/', $this->normalizeCpf($cpf));
    }

    /**
     * @return array{status:int, success:bool, message:string, error_code:?string, data:?array}
     */
    public function fetchCitizenByCpf(string $cpf): array
    {
        $normalizedCpf = $this->normalizeCpf($cpf);

        if (! $this->isValidCpfFormat($normalizedCpf)) {
            return [
                'status' => 400,
                'success' => false,
                'message' => 'CPF invalido. Informe 11 digitos ou formato 000.000.000-00.',
                'error_code' => 'INVALID_CPF_FORMAT',
                'data' => null,
            ];
        }

        $baseUrl = (string) config('services.gov_assai.base_url');
        $apiKey = (string) config('services.gov_assai.api_key');

        if ($baseUrl === '' || $apiKey === '') {
            return [
                'status' => 503,
                'success' => false,
                'message' => 'Integracao Gov.Assai nao configurada.',
                'error_code' => 'GOV_ASSAI_NOT_CONFIGURED',
                'data' => null,
            ];
        }

        try {
            $response = Http::acceptJson()
            //REMOVER IMPORTANTE
            //->withoutVerifying() // ✅ IGNORA O SSL LOCALMENTE
            //REMOVA
                ->timeout((int) config('services.gov_assai.timeout', 10))
                ->connectTimeout((int) config('services.gov_assai.connect_timeout', 5))
                ->retry([200, 500], throw: false)
                ->withHeaders([
                    'X-API-Key' => $apiKey,
                ])
                ->get(rtrim($baseUrl, '/').'/api/saude/cidadaos/cpf/'.$normalizedCpf);
        } catch (Throwable) {
            return [
                'status' => 503,
                'success' => false,
                'message' => 'Gov.Assai indisponivel no momento.',
                'error_code' => 'GOV_ASSAI_UNAVAILABLE',
                'data' => null,
            ];
        }

        if ($response->successful()) {
            $payload = $response->json();

            return [
                'status' => $response->status(),
                'success' => (bool) Arr::get($payload, 'success', false),
                'message' => (string) Arr::get($payload, 'message', 'Consulta realizada com sucesso'),
                'error_code' => null,
                'data' => Arr::get($payload, 'data'),
            ];
        }

        $payload = $response->json();

        return [
            'status' => $response->status(),
            'success' => false,
            'message' => (string) Arr::get($payload, 'message', 'Falha ao consultar Gov.Assai'),
            'error_code' => Arr::get($payload, 'error_code', Str::upper('HTTP_'.$response->status())),
            'data' => null,
        ];
    }

    /**
     * @return array{name:?string,birth_date:?string,social_name:?string,sexo:?string,email:?string,phone:?string,cns:?string,is_resident_assai:bool}
     */
    public function mapCitizenDataForLocalCreate(array $govData): array
    {
        return [
            'name' => Arr::get($govData, 'cidadao.nome'),
            'birth_date' => Arr::get($govData, 'cidadao.data_nascimento'),
            'social_name' => Arr::get($govData, 'cidadao.nome_social'),
            'sexo' => Arr::get($govData, 'cidadao.sexo'),
            'email' => Arr::get($govData, 'contato.email'),
            'phone' => Arr::get($govData, 'contato.celular'),
            'cns' => Arr::get($govData, 'saude.cns_numero'),
            'is_resident_assai' => true,
        ];
    }

    public function validateResidence(string $cpf): string
    {
        $result = $this->fetchCitizenByCpf($cpf);

        if ($result['status'] === 404) {
            return 'NAO_RESIDENTE';
        }

        if (! $result['success']) {
            return 'PENDENTE';
        }

        return 'RESIDENTE';
    }
}
