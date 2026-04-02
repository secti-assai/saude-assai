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
     * @return array{eligible:bool,message:string,residence_status:string,gov_assai_level:?string,citizen:?Citizen,cpf:string,error_code:?string}
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
            return [
                'eligible' => false,
                'message' => $result['message'],
                'residence_status' => $result['status'] === 404 ? 'NAO_RESIDENTE' : 'PENDENTE',
                'gov_assai_level' => null,
                'citizen' => null,
                'cpf' => $normalizedCpf,
                'error_code' => $result['error_code'],
            ];
        }

        $level = $this->extractGovLevel($result['data'] ?? []);

        if ($level === null || (int) $level < 2) {
            return [
                'eligible' => false,
                'message' => 'Cidadao sem nivel 2 no Gov.Assai. Atendimento nao autorizado. Solicitar ao cidadão para que entre em contato com a Secretaria de Ciência, Tecnologia e Inovação para regularizar sua situação.',
                'residence_status' => 'RESIDENTE',
                'gov_assai_level' => $level,
                'citizen' => null,
                'cpf' => $normalizedCpf,
                'error_code' => 'GOV_ASSAI_LEVEL_INSUFFICIENT',
            ];
        }

        $citizen = $this->syncCitizen($normalizedCpf, $result['data'] ?? []);

        if (! $citizen) {
            return [
                'eligible' => false,
                'message' => 'Gov.Assai retornou dados incompletos do cidadao.',
                'residence_status' => 'PENDENTE',
                'gov_assai_level' => $level,
                'citizen' => null,
                'cpf' => $normalizedCpf,
                'error_code' => 'GOV_ASSAI_INCOMPLETE_DATA',
            ];
        }

        return [
            'eligible' => true,
            'message' => 'Cidadao elegivel com Gov.Assai nivel 2.',
            'residence_status' => 'RESIDENTE',
            'gov_assai_level' => $level,
            'citizen' => $citizen,
            'cpf' => $normalizedCpf,
            'error_code' => null,
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
                'sexo' => Arr::get($data, 'cidadao.sexo'),
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
        $address = trim(implode(', ', array_filter([
            Arr::get($data, 'endereco.logradouro'),
            Arr::get($data, 'endereco.numero'),
            Arr::get($data, 'endereco.bairro'),
            Arr::get($data, 'endereco.distrito'),
        ], fn ($value) => $value !== null && $value !== '')));

        return $address !== '' ? $address : null;
    }
}
