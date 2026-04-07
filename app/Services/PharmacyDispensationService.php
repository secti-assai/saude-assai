<?php

namespace App\Services;

use App\Models\CentralPharmacyRequest;
use App\Models\Citizen;
use Illuminate\Support\Arr;
use App\Services\GovAssaiService;
use App\Services\AuditService;
use Illuminate\Support\Str;

class PharmacyDispensationService
{
    private const UNKNOWN_BIRTH_DATE = '1900-01-01';

    public function __construct(
        private readonly GovAssaiService $govAssai,
        private readonly AuditService $audit,
        private readonly PharmacyNotificationService $pharmacyNotifications,
    ) {}

    public function getCitizenInfo(string $cpf): array
    {
        $normalizedCpf = $this->govAssai->normalizeCpf($cpf);

        if (! $this->govAssai->isValidCpfFormat($normalizedCpf)) {
            return [
                'success' => false,
                'message' => 'CPF invalido.',
            ];
        }

        $citizen = Citizen::where('cpf_hash', hash('sha256', $normalizedCpf))->first();
        $govResponse = $this->govAssai->fetchCitizenByCpf($normalizedCpf);

        $lookupStatus = $this->resolveGovLookupStatus($govResponse);
        $lookupMessage = (string) ($govResponse['message'] ?? '');

        $govData = null;
        $level = null;

        if ($lookupStatus === 'FOUND' && is_array($govResponse['data'])) {
            $govData = $govResponse['data'];
            $level = $this->extractGovLevel($govData);

            if ($level === null) {
                $lookupStatus = 'ERROR';
                $lookupMessage = 'Gov.Assai retornou dados sem nivel de acesso valido.';
            }
        }

        $resolvedLevel = $level !== null ? (int) $level : 0;

        // Merge Gov.Assai and local data for the unified pharmacy screen.
        return [
            'success' => true,
            'citizen' => $citizen,
            'level' => $resolvedLevel,
            'gov_assai_found' => $lookupStatus === 'FOUND',
            'gov_data' => $govData,
            'normalized_cpf' => $normalizedCpf,
            'pharmacy_lock_flag' => $citizen ? $citizen->pharmacy_lock_flag : false,
            'gov_lookup_status' => $lookupStatus,
            'gov_lookup_message' => $lookupMessage,
        ];
    }

    public function processDispensation(array $data, int $attendantUserId, $request): array
    {
        $info = $this->getCitizenInfo($data['cpf']);
        if (! $info['success']) {
            return ['success' => false, 'action' => 'ERROR', 'message' => $info['message']];
        }

        $lookupStatus = (string) ($info['gov_lookup_status'] ?? 'ERROR');
        if (in_array($lookupStatus, ['UNAVAILABLE', 'ERROR'], true)) {
            return [
                'success' => false,
                'action' => 'RETRY_GOV_LOOKUP',
                'message' => (string) ($info['gov_lookup_message'] ?? 'Nao foi possivel consultar o Gov.Assai. Tente novamente.'),
            ];
        }

        $normalizedCpf = $info['normalized_cpf'];
        $citizen = $info['citizen'];
        $level = $info['level'];
        $govData = $info['gov_data'];
        $normalizedNameInput = $this->normalizeFullName($data['full_name'] ?? null);
        $normalizedPhoneInput = $this->normalizePhone($data['phone'] ?? null);

        if ($lookupStatus === 'NOT_FOUND' && ($normalizedNameInput === null || $normalizedPhoneInput === null)) {
            return [
                'success' => false,
                'action' => 'MISSING_CITIZEN_DATA',
                'message' => 'Informe nome completo e telefone no formato (00) 00000-0000 para continuar.',
            ];
        }

        // 1. Create or Find Citizen
        if (!$citizen) {
            $citizenData = [
                'cpf' => $normalizedCpf,
                'cpf_hash' => hash('sha256', $normalizedCpf),
            ];

            if ($govData) {
                // Merge data with manual overrides, keeping standardized formats.
                $citizenData['full_name'] = $normalizedNameInput
                    ?? $this->normalizeFullName((string) Arr::get($govData, 'cidadao.nome', ''))
                    ?? 'CIDADAO';
                $citizenData['phone'] = $normalizedPhoneInput
                    ?? $this->normalizePhone((string) Arr::get($govData, 'contato.celular', ''));
                $citizenData['birth_date'] = $this->resolveBirthDate(Arr::get($govData, 'cidadao.data_nascimento'));
                $citizenData['is_resident_assai'] = true;
            } else {
                // Not found on gov assai, use manual data
                $citizenData['full_name'] = $normalizedNameInput ?? 'CIDADAO NAO INFORMADO';
                $citizenData['phone'] = $normalizedPhoneInput;
                $citizenData['birth_date'] = self::UNKNOWN_BIRTH_DATE;
                $citizenData['is_resident_assai'] = false;
            }

            $citizen = Citizen::create($citizenData);
            $this->audit->log($request, 'FARMACIA_CENTRAL', 'CADASTRAR_CIDADAO_AVULSO', Citizen::class, $citizen->id, ['cpf' => $normalizedCpf]);
        } else {
            // Keep citizen record standardized when attendant confirms or edits data.
            $updates = [];

            if ($normalizedNameInput !== null && $normalizedNameInput !== (string) $citizen->full_name) {
                $updates['full_name'] = $normalizedNameInput;
            }

            if ($normalizedPhoneInput !== null && $normalizedPhoneInput !== (string) $citizen->phone) {
                $updates['phone'] = $normalizedPhoneInput;
            }

            if ($updates !== []) {
                $citizen->update($updates);
            }
        }

        // 2. Logic for Level 2 or Level <= 1
        if ($level >= 2) {
            // If they had the block flag, they have regularized now. Remove it!
            if ($citizen->pharmacy_lock_flag) {
                $citizen->update(['pharmacy_lock_flag' => false]);
                $this->audit->log($request, 'FARMACIA_CENTRAL', 'DESBLOQUEAR_FARMACIA_CIDADAO', Citizen::class, $citizen->id, ['reason' => 'Atingiu Nivel 2.']);
            }

            $pharmacyRequest = $this->logDispensation($citizen, $attendantUserId, $data, $level, $request);
            $this->pharmacyNotifications->sendDispenseFeedback($pharmacyRequest);

            return [
                'success' => true,
                'action' => 'DISPENSED',
                'message' => 'O cidadao esta regularizado com o Gov.Assai (Nivel '.$level.'). A dispensa foi realizada e registrada!',
            ];
        } else {
            // Level 1 or 0 (Not Found)
            if ($citizen->pharmacy_lock_flag) {
                // Blocked
                $this->pharmacyNotifications->sendRegularizationGuidance($citizen, $level, 'blocked-'.$citizen->id.'-'.now()->format('YmdHis'));

                return [
                    'success' => false,
                    'action' => 'BLOCKED',
                    'message' => 'BLOQUEADO: O cidadao ja foi notificado anteriormente e nao atingiu Nivel 2. Dispensacao nao autorizada.',
                ];
            } else {
                // First time -> Lock for next times
                $citizen->update(['pharmacy_lock_flag' => true]);
                $this->audit->log($request, 'FARMACIA_CENTRAL', 'BLOQUEAR_FARMACIA_CIDADAO', Citizen::class, $citizen->id, [
                    'reason' => 'Nivel '.$level.' no momento da dispensacao com aviso de regularizacao.',
                ]);

                $pharmacyRequest = $this->logDispensation($citizen, $attendantUserId, $data, $level, $request);
                $this->pharmacyNotifications->sendRegularizationGuidance($citizen, $level, 'dispensed-notified-'.$pharmacyRequest->id);
                $this->pharmacyNotifications->sendDispenseFeedback($pharmacyRequest);

                return [
                    'success' => true,
                    'action' => 'DISPENSED_NOTIFIED',
                    'message' => 'Cidadao NOTIFICADO! Dispensacao liberada desta vez, mas a flag de bloqueio foi ativada. Na proxima, precisara de Nivel 2.',
                ];
            }
        }
    }

    public function registerBlockedNoDispense(array $data, int $attendantUserId, $request): array
    {
        $info = $this->getCitizenInfo($data['cpf']);
        if (! $info['success']) {
            return ['success' => false, 'action' => 'ERROR', 'message' => $info['message']];
        }

        $lookupStatus = (string) ($info['gov_lookup_status'] ?? 'ERROR');
        if (in_array($lookupStatus, ['UNAVAILABLE', 'ERROR'], true)) {
            return [
                'success' => false,
                'action' => 'RETRY_GOV_LOOKUP',
                'message' => (string) ($info['gov_lookup_message'] ?? 'Nao foi possivel consultar o Gov.Assai. Tente novamente.'),
            ];
        }

        $citizen = $info['citizen'];
        if (! $citizen) {
            return [
                'success' => false,
                'action' => 'CITIZEN_NOT_FOUND_LOCAL',
                'message' => 'Nao foi possivel localizar o cidadao na base local para registrar a nao dispensacao.',
            ];
        }

        $level = (int) ($info['level'] ?? 0);
        if (! $citizen->pharmacy_lock_flag || $level >= 2) {
            return [
                'success' => false,
                'action' => 'BLOCK_NOT_ACTIVE',
                'message' => 'Este cidadao nao esta mais bloqueado para dispensacao.',
            ];
        }

        $category = $this->normalizeDispenseCategory($data['dispense_category'] ?? null);
        $refusalReason = 'Dispensacao bloqueada por nivel insuficiente no Gov.Assai (cidadao ja notificado).';

        $pharmacyRequest = CentralPharmacyRequest::create([
            'citizen_id' => $citizen->id,
            'reception_user_id' => $attendantUserId,
            'attendant_user_id' => $attendantUserId,
            'prescription_date' => now()->toDateString(),
            'prescriber_name' => null,
            'medication_name' => $category,
            'concentration' => null,
            'quantity' => 0,
            'dosage' => null,
            'gov_assai_level' => (string) $level,
            'residence_status' => $lookupStatus === 'FOUND' ? 'RESIDENTE' : 'PENDENTE',
            'status' => 'NAO_DISPENSADO',
            'notes' => null,
            'refusal_reason' => $refusalReason,
            'dispensed_at' => null,
        ]);

        $this->audit->log($request, 'FARMACIA_CENTRAL', 'ATENDENTE_REGISTROU_NAO_DISPENSACAO_BLOQUEIO', CentralPharmacyRequest::class, null, [
            'citizen_id' => $citizen->id,
            'pharmacy_request_id' => $pharmacyRequest->id,
            'dispense_category' => $category,
            'reason' => $refusalReason,
            'gov_assai_level' => $level,
        ]);

        $this->pharmacyNotifications->sendRegularizationGuidance($citizen, $level, 'no-dispense-'.$pharmacyRequest->id);

        return [
            'success' => true,
            'action' => 'NO_DISPENSE_RECORDED',
            'message' => 'Nao dispensacao registrada com sucesso para este cidadao bloqueado.',
        ];
    }

    private function resolveGovLookupStatus(array $govResponse): string
    {
        if (($govResponse['success'] ?? false) === true && is_array($govResponse['data'] ?? null)) {
            return 'FOUND';
        }

        $status = (int) ($govResponse['status'] ?? 0);
        if ($status === 404) {
            return 'NOT_FOUND';
        }

        return 'UNAVAILABLE';
    }

    private function extractGovLevel(array $data): ?string
    {
        $candidate = Arr::first([
            Arr::get($data, 'gov_assai.nivel'),
            Arr::get($data, 'gov_assai.nivel_conta'),
            Arr::get($data, 'cidadao.nivel'),
            Arr::get($data, 'cidadao.nivel_conta'),
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

    private function normalizeFullName(?string $name): ?string
    {
        $normalized = trim((string) $name);

        if ($normalized === '') {
            return null;
        }

        return Str::upper($normalized);
    }

    private function normalizePhone(?string $phone): ?string
    {
        $digits = preg_replace('/\D+/', '', (string) $phone) ?? '';

        if (strlen($digits) === 11) {
            return sprintf('(%s) %s-%s', substr($digits, 0, 2), substr($digits, 2, 5), substr($digits, 7, 4));
        }

        if (strlen($digits) === 10) {
            return sprintf('(%s) %s-%s', substr($digits, 0, 2), substr($digits, 2, 4), substr($digits, 6, 4));
        }

        return null;
    }

    private function resolveBirthDate(mixed $value): string
    {
        if (is_string($value)) {
            $trimmed = trim($value);

            if ($trimmed !== '') {
                $timestamp = strtotime($trimmed);
                if ($timestamp !== false) {
                    return date('Y-m-d', $timestamp);
                }
            }
        }

        return self::UNKNOWN_BIRTH_DATE;
    }

    private function normalizeDispenseCategory(mixed $value): string
    {
        if (! is_string($value)) {
            return 'MEDICACAO';
        }

        $normalized = Str::upper(trim($value));

        return match ($normalized) {
            'LEITE' => 'LEITE',
            'SUPLEMENTO' => 'SUPLEMENTO',
            default => 'MEDICACAO',
        };
    }

    private function logDispensation(Citizen $citizen, int $attendantUserId, array $data, int $level, $request): CentralPharmacyRequest
    {
        $pharmacyRequest = CentralPharmacyRequest::create([
            'citizen_id' => $citizen->id,
            'reception_user_id' => $attendantUserId,
            'attendant_user_id' => $attendantUserId,
            'prescription_code' => $data['prescription_code'] ?? null,
            'prescription_date' => $data['prescription_date'] ?? now()->toDateString(),
            'prescriber_name' => $data['prescriber_name'] ?? 'MÉDICO GENÉRICO',
            'medication_name' => $this->normalizeDispenseCategory($data['dispense_category'] ?? null),
            'concentration' => $data['concentration'] ?? '-',
            'quantity' => (int) ($data['quantity'] ?? 1),
            'dosage' => $data['dosage'] ?? '-',
            'gov_assai_level' => (string) $level,
            'residence_status' => 'RESIDENTE',
            'status' => 'DISPENSADO',
            'notes' => $data['notes'] ?? null,
            'dispensed_at' => now(),
        ]);

        $this->audit->log($request, 'FARMACIA_CENTRAL', 'DISPENSACAO_DIRETA', CentralPharmacyRequest::class, null, [
            'citizen_id' => $citizen->id,
            'pharmacy_request_id' => $pharmacyRequest->id,
            'dispense_category' => $pharmacyRequest->medication_name,
        ]);

        return $pharmacyRequest;
    }
}
