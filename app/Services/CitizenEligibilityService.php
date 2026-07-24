<?php

namespace App\Services;

use App\Models\Citizen;
use Illuminate\Support\Arr;

class CitizenEligibilityService
{
    public function __construct(private readonly GovAssaiService $govAssai)
    {
    }

    /**
     * @return array{eligible:bool,message:string,residence_status:string,gov_assai_level:?string,citizen:?Citizen,cpf:string,error_code:?string,origem:?string}
     */
    public function validateAndSync(string $cpf): array
    {
        $normalizedCpf = $this->govAssai->normalizeCpf($cpf);

        if (! $this->govAssai->isValidCpfFormat($normalizedCpf)) {
            return [
                'eligible' => false,
                'message' => 'CPF invalido. Informe 11 digitos.',
                'residence_status' => 'PENDENTE',
                'gov_assai_level' => null,
                'citizen' => null,
                'cpf' => $normalizedCpf,
                'error_code' => 'INVALID_CPF_FORMAT',
            ];
        }

        $result = $this->govAssai->fetchCitizenByCpf($normalizedCpf);

        if (! $result['success']) {
            $errorCode = $result['error_code'] ?? null;

            if ($errorCode === 'AWAITING_ACS_VALIDATION') {
                return [
                    'eligible' => false,
                    'message' => 'Cidadao pendente de validacao pelo Agente de Saude (ACS). Atendimento nao autorizado no momento.',
                    'residence_status' => 'RESIDENTE',
                    'gov_assai_level' => '0',
                    'citizen' => null,
                    'cpf' => $normalizedCpf,
                    'error_code' => $errorCode,
                    'origem' => $result['origem'] ?? null,
                ];
            }

            return [
                'eligible' => false,
                'message' => $result['message'],
                'residence_status' => $result['status'] === 404 ? 'NAO_RESIDENTE' : 'PENDENTE',
                'gov_assai_level' => null,
                'citizen' => null,
                'cpf' => $normalizedCpf,
                'error_code' => $result['error_code'],
                'origem' => $result['origem'] ?? null,
            ];
        }

        $origem = $result['origem'] ?? null;

        // ── Cidadão vindo da integracao_esus: liberar sem exigir nivel 2 ──
        if ($origem === 'integracao_esus') {
            $citizen = $this->syncCitizen($normalizedCpf, $result['data'] ?? []);

            if (! $citizen) {
                return [
                    'eligible' => false,
                    'message' => 'e-SUS PEC retornou dados incompletos do cidadao.',
                    'residence_status' => 'RESIDENTE',
                    'gov_assai_level' => '0',
                    'citizen' => null,
                    'cpf' => $normalizedCpf,
                    'error_code' => 'ESUS_INCOMPLETE_DATA',
                    'origem' => $origem,
                ];
            }

            \App\Jobs\SyncCitizenToBethaJob::dispatch($citizen->id);

            return [
                'eligible' => true,
                'message' => 'Cidadao elegivel via integracao e-SUS PEC.',
                'residence_status' => 'RESIDENTE',
                'gov_assai_level' => '0',
                'citizen' => $citizen,
                'cpf' => $normalizedCpf,
                'error_code' => null,
                'origem' => $origem,
            ];
        }

        $level = $this->extractGovLevel($result['data'] ?? []);

        $isMinor = false;
        $birthDate = Arr::get($result['data'] ?? [], 'cidadao.data_nascimento');
        if ($birthDate) {
            $isMinor = \Carbon\Carbon::parse($birthDate)->age < 18;
        }

        if (!$isMinor && ($level === null || (int) $level < 2)) {
            return [
                'eligible' => false,
                'message' => 'Cidadao sem nivel 2 na Consulta à População à descrita de Assaí. Atendimento nao autorizado. Solicitar ao cidadão para que entre em contato com a Secretaria de Ciência, Tecnologia e Inovação para regularizar sua situação.',
                'residence_status' => 'RESIDENTE',
                'gov_assai_level' => $level,
                'citizen' => null,
                'cpf' => $normalizedCpf,
                'error_code' => 'GOV_ASSAI_LEVEL_INSUFFICIENT',
                'origem' => $origem,
            ];
        }

        $citizen = $this->syncCitizen($normalizedCpf, $result['data'] ?? []);

        if (! $citizen) {
            return [
                'eligible' => false,
                'message' => 'Consulta à População à descrita de Assaí retornou dados incompletos do cidadao.',
                'residence_status' => 'PENDENTE',
                'gov_assai_level' => $level,
                'citizen' => null,
                'cpf' => $normalizedCpf,
                'error_code' => 'GOV_ASSAI_INCOMPLETE_DATA',
                'origem' => $origem,
            ];
        }

        \App\Jobs\SyncCitizenToBethaJob::dispatch($citizen->id);

        return [
            'eligible' => true,
            'message' => 'Cidadao elegivel com Consulta à População à descrita de Assaí nivel 2.',
            'residence_status' => 'RESIDENTE',
            'gov_assai_level' => $level,
            'citizen' => $citizen,
            'cpf' => $normalizedCpf,
            'error_code' => null,
            'origem' => $origem,
        ];
    }

    private function extractGovLevel(array $data): ?string
    {
        $candidate = Arr::first([
            Arr::get($data, 'gov_assai.nivel'),
            Arr::get($data, 'gov_assai.nivel_conta'),
            Arr::get($data, 'cidadao.nivel'),
            Arr::get($data, 'usuario.nivel'),
            Arr::get($data, 'nivel'),
        ], fn ($value) => $this->normalizeGovLevelValue($value) !== null);

        return $this->normalizeGovLevelValue($candidate);
    }

    private function normalizeGovLevelValue(mixed $value, int $depth = 0): ?string
    {
        if ($value === null || $depth > 4) {
            return null;
        }

        if (is_int($value) || is_float($value) || is_string($value) || is_bool($value)) {
            $normalized = trim((string) $value);

            return $normalized !== '' ? $normalized : null;
        }

        if (is_object($value)) {
            $value = (array) $value;
        }

        if (! is_array($value)) {
            return null;
        }

        foreach (['nivel', 'nivel_conta', 'value', 'valor', 'codigo', 'id'] as $key) {
            if (array_key_exists($key, $value)) {
                $nested = $this->normalizeGovLevelValue($value[$key], $depth + 1);
                if ($nested !== null) {
                    return $nested;
                }
            }
        }

        foreach ($value as $item) {
            $nested = $this->normalizeGovLevelValue($item, $depth + 1);
            if ($nested !== null) {
                return $nested;
            }
        }

        return null;
    }

    private function syncCitizen(string $cpf, array $data): ?Citizen
    {
        $name = trim((string) Arr::get($data, 'cidadao.nome', ''));
        $birthDate = Arr::get($data, 'cidadao.data_nascimento');

        if ($name === '' || $birthDate === null) {
            return null;
        }

        $cpfHash = hash('sha256', $cpf);

        return Citizen::updateOrCreate(
            ['cpf_hash' => $cpfHash],
            [
                'cpf' => $cpf,
                'cpf_hash' => $cpfHash,
                'full_name' => $name,
                'social_name' => Arr::get($data, 'cidadao.nome_social'),
                'birth_date' => $birthDate,
                'sexo' => strtoupper(substr((string) Arr::get($data, 'cidadao.sexo', 'M'), 0, 1)),
                'raca_cor' => Arr::get($data, 'cidadao.raca', null),
                'address' => $this->buildAddress($data),
                'phone' => Arr::get($data, 'contato.celular'),
                'email' => Arr::get($data, 'contato.email'),
                'cns' => Arr::get($data, 'saude.cns_numero'),
                'is_resident_assai' => true,
                'residence_validated_at' => now(),
            ]
        );
    }

    private function buildAddress(array $data): ?string
    {
        $endereco = [
            'cep' => Arr::get($data, 'endereco.cep'),
            'logradouro' => Arr::get($data, 'endereco.logradouro'),
            'numero' => Arr::get($data, 'endereco.numero'),
            'bairro' => Arr::get($data, 'endereco.bairro'),
            'distrito' => Arr::get($data, 'endereco.distrito'),
            
            // Campos adicionais vindos do Gov.Assai
            'nacionalidade_sigla' => Arr::get($data, 'cidadao.nacionalidade_sigla'),
            'naturalidade' => Arr::get($data, 'cidadao.naturalidade'),
            'naturalidade_uf' => Arr::get($data, 'cidadao.naturalidade_uf'),
        ];
        
        return json_encode($endereco);
    }
}
