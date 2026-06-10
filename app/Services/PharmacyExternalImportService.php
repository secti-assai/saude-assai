<?php

namespace App\Services;

use App\Models\CentralPharmacyRequest;
use App\Models\Citizen;
use App\Models\PharmacyExternalImport;
use App\Models\PharmacyExternalImportRow;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PharmacyExternalImportService
{
    public function __construct(private readonly AuditService $audit)
    {
    }

    /**
     * @return array<string, mixed>
     */
    public function import(Request $request, UploadedFile $bethaFile, UploadedFile $dailyFile): array
    {
        $actor = $request->user();

        if (! $actor instanceof User) {
            throw new \RuntimeException('Usuario autenticado nao encontrado para processar importacao.');
        }

        $bethaPath = $bethaFile->getRealPath();
        $dailyPath = $dailyFile->getRealPath();

        if (! is_string($bethaPath) || ! is_file($bethaPath) || ! is_string($dailyPath) || ! is_file($dailyPath)) {
            throw new \RuntimeException('Arquivos de importacao invalidos.');
        }

        $csvEvents = $this->parseBethaCsv($bethaPath);
        $txtEvents = $this->parseDailyTxt($dailyPath);

        $summary = [
            'processed_rows' => 0,
            'ignored_rows' => 0,
            'duplicates_skipped' => 0,
            'synthetic_created' => 0,
            'matched_existing' => 0,
            'bypass_detected' => 0,
            'citizens_created' => 0,
            'citizens_locked' => 0,
            'pharmacist_unmatched' => 0,
            'txt_only_rows' => 0,
            'alerts_high' => 0,
            'alerts_medium' => 0,
        ];

        $alerts = [];

        $batch = DB::transaction(function () use (
            $request,
            $actor,
            $bethaFile,
            $dailyFile,
            $bethaPath,
            $dailyPath,
            $csvEvents,
            $txtEvents,
            &$summary,
            &$alerts
        ): PharmacyExternalImport {
            $batch = PharmacyExternalImport::create([
                'uploaded_by_user_id' => $actor->id,
                'betha_filename' => $bethaFile->getClientOriginalName(),
                'daily_filename' => $dailyFile->getClientOriginalName(),
                'betha_sha256' => hash_file('sha256', $bethaPath),
                'daily_sha256' => hash_file('sha256', $dailyPath),
                'imported_at' => now(),
                'stats' => null,
            ]);

            $citizenIndex = $this->buildCitizenIndex();
            $pharmacistIndex = $this->buildPharmacistIndex();

            $txtByDispense = [];
            $txtByNameDate = [];
            $txtByDate = [];
            $txtUsed = [];

            foreach ($txtEvents as $index => $event) {
                if (! empty($event['dispense_number'])) {
                    $txtByDispense[(string) $event['dispense_number']][] = $index;
                }

                $nameDateKey = $this->buildNameDateKey($event['customer_name_normalized'] ?? null, $event['dispensed_at'] ?? null);
                if ($nameDateKey !== null) {
                    $txtByNameDate[$nameDateKey][] = $index;
                }

                if (($event['dispensed_at'] ?? null) instanceof Carbon) {
                    $txtByDate[$event['dispensed_at']->toDateString()][] = $index;
                }
            }

            foreach ($csvEvents as $csvEvent) {
                $txtIndex = $this->findTxtMatchIndex($csvEvent, $txtEvents, $txtByDispense, $txtByNameDate, $txtByDate, $txtUsed);
                $txtEvent = $txtIndex !== null ? $txtEvents[$txtIndex] : null;

                if ($txtIndex !== null) {
                    $txtUsed[$txtIndex] = true;
                }

                $merged = $this->mergeEvents($csvEvent, $txtEvent);
                $result = $this->persistMergedEvent($batch, $merged, $actor, $request, $citizenIndex, $pharmacistIndex);

                foreach ($result as $key => $value) {
                    if (array_key_exists($key, $summary)) {
                        $summary[$key] += (int) $value;
                    }
                }

                if (isset($result['alert']) && is_array($result['alert'])) {
                    $alerts[] = $result['alert'];
                }
            }

            foreach ($txtEvents as $index => $txtEvent) {
                if (isset($txtUsed[$index])) {
                    continue;
                }

                $summary['txt_only_rows']++;
            }

            $batch->update([
                'imported_at' => now(),
                'stats' => $summary,
            ]);

            \App\Jobs\SweepImportedBypassesJob::dispatch($batch->id);

            return $batch;
        });

        $this->audit->log(
            $request,
            'FARMACIA_CENTRAL',
            'IMPORTACAO_EXTERNA_BETHA_EXECUTADA',
            PharmacyExternalImport::class,
            null,
            [
                'import_id' => $batch->id,
                'summary' => $summary,
                'alert_count' => count($alerts),
            ]
        );

        return [
            'import_id' => $batch->id,
            'summary' => $summary,
            'alerts' => array_slice($alerts, 0, 20),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function buildDashboard(): array
    {
        try {
            $recentImports = PharmacyExternalImport::query()
                ->with('uploader')
                ->orderByDesc('created_at')
                ->limit(10)
                ->get();

            $recentBypassRows = PharmacyExternalImportRow::query()
                ->with(['importBatch', 'citizen', 'pharmacistUser'])
                ->where('bypass_detected', true)
                ->orderByDesc('created_at')
                ->limit(20)
                ->get();

            $recurrenceAlerts = PharmacyExternalImportRow::query()
                ->with(['importBatch', 'citizen', 'pharmacistUser'])
                ->whereIn('recurrence_alert_level', ['ALTO', 'MEDIO'])
                ->orderByDesc('created_at')
                ->limit(20)
                ->get();

            return [
                'externalImportTotals' => [
                    'imports' => PharmacyExternalImport::query()->count(),
                    'bypass_rows' => PharmacyExternalImportRow::query()->where('bypass_detected', true)->count(),
                    'alerts_high' => PharmacyExternalImportRow::query()->where('recurrence_alert_level', 'ALTO')->count(),
                    'alerts_medium' => PharmacyExternalImportRow::query()->where('recurrence_alert_level', 'MEDIO')->count(),
                ],
                'recentPharmacyImports' => $recentImports,
                'recentBypassRows' => $recentBypassRows,
                'recurrenceAlerts' => $recurrenceAlerts,
            ];
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('PharmacyExternalImportService: Failed to build dashboard (table might be missing). ' . $e->getMessage());
            return [
                'externalImportTotals' => [
                    'imports' => 0,
                    'bypass_rows' => 0,
                    'alerts_high' => 0,
                    'alerts_medium' => 0,
                ],
                'recentPharmacyImports' => collect([]),
                'recentBypassRows' => collect([]),
                'recurrenceAlerts' => collect([]),
            ];
        }
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function parseBethaCsv(string $path): array
    {
        $handle = fopen($path, 'r');

        if (! is_resource($handle)) {
            return [];
        }

        $events = [];
        $lineNumber = 0;
        $headerMap = null;

        $currentCustomer = null;
        $currentMedication = null;

        while (($row = fgetcsv($handle, 0, ';', '"', '')) !== false) {
            $lineNumber++;

            if ($lineNumber === 1 && isset($row[0])) {
                $row[0] = $this->stripUtf8Bom((string) $row[0]);
            }

            $row = array_map(fn ($value): string => trim((string) $value), $row);

            if ($this->isRowEmpty($row)) {
                continue;
            }

            $line = trim(implode(';', $row));

            $customerFromLine = $this->extractCustomerFromBethaLine($line);
            if ($customerFromLine !== null) {
                $currentCustomer = $customerFromLine;
                $currentMedication = null;

                continue;
            }

            if ($headerMap === null) {
                $headerMap = $this->detectCsvHeaderMap($row);
                if ($headerMap !== null) {
                    continue;
                }
            }

            if ($headerMap !== null) {
                $event = $this->extractCsvEventFromHeaderRow($row, $headerMap, $lineNumber);
                if ($event !== null) {
                    $events[] = $event;
                }

                continue;
            }

            if ($this->isBethaMetadataLine($row, $line)) {
                continue;
            }

            $medicationInRow = $this->valueAt($row, 2);
            if ($medicationInRow !== null && ! $this->isBethaMedicationHeaderCell($medicationInRow)) {
                $currentMedication = $medicationInRow;
            }

            $dispensedAt = $this->extractDateFromText($this->valueAt($row, 3));
            if (! $dispensedAt instanceof Carbon) {
                continue;
            }

            if ($currentCustomer === null || $currentMedication === null) {
                continue;
            }

            $quantity = $this->extractQuantity($this->valueAt($row, 11))
                ?? $this->extractLastQuantityFromRow($row);

            $events[] = [
                'source' => 'csv',
                'line_number' => $lineNumber,
                'dispense_number' => null,
                'dispensed_at' => $dispensedAt,
                'customer_name_raw' => $currentCustomer,
                'customer_name_normalized' => $this->normalizePersonName($currentCustomer),
                'medication_name_raw' => $currentMedication,
                'quantity' => $quantity ?? 1,
                'payload' => [
                    'row' => $row,
                    'line' => $line,
                ],
            ];
        }

        fclose($handle);

        return $events;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function parseDailyTxt(string $path): array
    {
        $lines = file($path, FILE_IGNORE_NEW_LINES);

        if (! is_array($lines)) {
            return [];
        }

        $events = [];
        $currentPharmacist = null;
        $currentOperator = null;
        $currentDate = null;

        foreach ($lines as $lineNumber => $rawLine) {
            $line = trim((string) $rawLine);

            if ($lineNumber === 0) {
                $line = $this->stripUtf8Bom($line);
            }

            if ($line === '') {
                continue;
            }

            $actors = $this->extractTxtHeaderActors($line);
            if (is_array($actors)) {
                $currentOperator = $actors['operator_username_raw'];
                $currentPharmacist = $actors['pharmacist_name_raw'] ?? $currentOperator;

                continue;
            }

            if ($this->isTxtReportNoise($line)) {
                continue;
            }

            $isolatedDate = $this->extractDateOnlyFromTxtLine($line);
            if ($isolatedDate instanceof Carbon) {
                $currentDate = $isolatedDate;

                continue;
            }

            if (preg_match('/^\s*(\d{6,})\s+(.+)$/u', $line, $matches) !== 1) {
                continue;
            }

            $customerRaw = trim($matches[2]);
            if ($customerRaw === '') {
                continue;
            }

            $events[] = [
                'source' => 'txt',
                'line_number' => $lineNumber + 1,
                'dispense_number' => trim($matches[1]),
                'dispensed_at' => $currentDate,
                'customer_name_raw' => $customerRaw,
                'customer_name_normalized' => $this->normalizePersonName($customerRaw),
                'pharmacist_name_raw' => $currentPharmacist,
                'pharmacist_name_normalized' => $this->normalizePersonName($currentPharmacist),
                'operator_username_raw' => $currentOperator,
                'operator_username_normalized' => $this->normalizePersonName($currentOperator),
                'payload' => [
                    'line' => $line,
                ],
            ];
        }

        return $events;
    }

    private function extractCustomerFromBethaLine(string $line): ?string
    {
        if (preg_match('/^\s*Nome\s+do\s+cliente:\s*([^;]+)\s*(?:;|$)/iu', $line, $matches) !== 1) {
            return null;
        }

        $customer = trim($matches[1]);

        return $customer === '' ? null : $customer;
    }

    /**
     * @param array<int, string> $row
     */
    private function isBethaMetadataLine(array $row, string $line): bool
    {
        $normalizedLine = Str::upper(Str::ascii($line));

        foreach (['UNIDADE:', 'PRODUTO', 'TOTAL PRESCRITO', 'DATA E HORA', 'TOTAL DISPENSADO'] as $token) {
            if (str_contains($normalizedLine, $token)) {
                return true;
            }
        }

        $status = $this->valueAt($row, 8);
        if ($status !== null) {
            $normalizedStatus = Str::upper(Str::ascii($status));

            return ! str_contains($normalizedStatus, 'DISPENSAD');
        }

        return false;
    }

    private function isBethaMedicationHeaderCell(?string $value): bool
    {
        if ($value === null) {
            return false;
        }

        $normalized = Str::upper(Str::ascii($value));

        foreach (['NOME DO CLIENTE', 'UNIDADE', 'PRODUTO', 'TOTAL PRESCRITO', 'TOTAL DISPENSADO'] as $token) {
            if (str_contains($normalized, $token)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param array<int, string> $row
     */
    private function extractLastQuantityFromRow(array $row): ?int
    {
        for ($index = count($row) - 1; $index >= 0; $index--) {
            $quantity = $this->extractQuantity($row[$index] ?? null);
            if ($quantity !== null) {
                return $quantity;
            }
        }

        return null;
    }

    /**
     * @return array<string, string|null>|null
     */
    private function extractTxtHeaderActors(string $line): ?array
    {
        if (preg_match('/Usu[aá]rio:\s*(.*?)\s+Profissional:\s*(.*)$/iu', $line, $matches) !== 1) {
            return null;
        }

        $operator = trim($matches[1]);
        $professional = trim($matches[2]);

        $normalizedProfessional = Str::upper(Str::ascii($professional));
        if ($professional === '' || str_contains($normalizedProfessional, 'PROFISSIONAL NAO')) {
            $professional = null;
        }

        return [
            'operator_username_raw' => $operator !== '' ? $operator : null,
            'pharmacist_name_raw' => $professional,
        ];
    }

    private function isTxtReportNoise(string $line): bool
    {
        $normalized = Str::upper(Str::ascii(trim($line)));

        foreach ([
            'PAGIN',
            'ESTADO DO PARANA',
            'PREFEITURA MUNICIPAL',
            'RELATORIO DE DISPENSAS',
            'DATA IN',
            'DATA FI',
            'UNIDADE DE SAUDE',
            'DISPENSA CLIENTE',
            'IMPRESSO EM',
            'TOTAL DE',
            'TOTAL GERAL DE',
        ] as $prefix) {
            if (str_starts_with($normalized, $prefix)) {
                return true;
            }
        }

        return false;
    }

    private function extractDateOnlyFromTxtLine(string $line): ?Carbon
    {
        if (preg_match('/^\s*(\d{2}\/\d{2}\/\d{4})\s*$/', $line, $matches) !== 1) {
            return null;
        }

        return $this->parseDateTime($matches[1]);
    }

    /**
     * @param array<string, mixed>|null $csvEvent
     * @param array<string, mixed>|null $txtEvent
     * @return array<string, mixed>
     */
    private function mergeEvents(?array $csvEvent, ?array $txtEvent): array
    {
        $dispenseNumber = $csvEvent['dispense_number'] ?? $txtEvent['dispense_number'] ?? null;
        $dispensedAt = $csvEvent['dispensed_at'] ?? $txtEvent['dispensed_at'] ?? null;

        if (! $dispensedAt instanceof Carbon) {
            $dispensedAt = now();
        }

        $customerName = $csvEvent['customer_name_raw'] ?? $txtEvent['customer_name_raw'] ?? null;
        if (! is_string($customerName) || trim($customerName) === '') {
            $customerName = 'CIDADAO IMPORTADO '.($dispenseNumber ?? 'SEM_NUMERO');
        }

        $pharmacistRaw = null;
        if (is_string($txtEvent['pharmacist_name_raw'] ?? null) && trim((string) $txtEvent['pharmacist_name_raw']) !== '') {
            $pharmacistRaw = trim((string) $txtEvent['pharmacist_name_raw']);
        } elseif (is_string($txtEvent['operator_username_raw'] ?? null) && trim((string) $txtEvent['operator_username_raw']) !== '') {
            $pharmacistRaw = trim((string) $txtEvent['operator_username_raw']);
        }

        $medicationName = $csvEvent['medication_name_raw'] ?? null;
        $quantity = $csvEvent['quantity'] ?? null;

        return [
            'dispense_number' => is_string($dispenseNumber) ? $dispenseNumber : null,
            'dispensed_at' => $dispensedAt,
            'customer_name_raw' => $customerName,
            'customer_name_normalized' => $this->normalizePersonName($customerName),
            'pharmacist_name_raw' => $pharmacistRaw,
            'pharmacist_name_normalized' => $this->normalizePersonName($pharmacistRaw),
            'medication_name_raw' => is_string($medicationName) ? $medicationName : 'MEDICACAO',
            'quantity' => is_int($quantity) && $quantity > 0 ? $quantity : 1,
            'payload' => [
                'csv' => $csvEvent,
                'txt' => $txtEvent,
            ],
        ];
    }

    /**
     * @param array<string, mixed> $event
     * @param array<string, mixed> $citizenIndex
     * @param array<string, mixed> $pharmacistIndex
     * @return array<string, mixed>
     */
    private function persistMergedEvent(
        PharmacyExternalImport $batch,
        array $event,
        User $actor,
        Request $request,
        array &$citizenIndex,
        array $pharmacistIndex
    ): array {
        $result = [
            'processed_rows' => 0,
            'ignored_rows' => 0,
            'duplicates_skipped' => 0,
            'synthetic_created' => 0,
            'matched_existing' => 0,
            'bypass_detected' => 0,
            'citizens_created' => 0,
            'citizens_locked' => 0,
            'pharmacist_unmatched' => 0,
            'alerts_high' => 0,
            'alerts_medium' => 0,
        ];

        $hasCoreData = ($event['customer_name_normalized'] ?? null) !== null
            || ($event['dispense_number'] ?? null) !== null
            || ($event['medication_name_raw'] ?? null) !== null;

        if (! $hasCoreData) {
            $result['ignored_rows']++;

            return $result;
        }

        $rowHash = $this->buildRowHash($event);

        if (PharmacyExternalImportRow::query()->where('row_hash', $rowHash)->exists()) {
            $result['duplicates_skipped']++;

            return $result;
        }

        $result['processed_rows']++;

        $citizenResolution = $this->resolveCitizen($event, $citizenIndex);
        /** @var Citizen $citizen */
        $citizen = $citizenResolution['citizen'];

        if (($citizenResolution['created'] ?? false) === true) {
            $result['citizens_created']++;
        }

        $pharmacistUser = $this->resolvePharmacistUser($event['pharmacist_name_normalized'] ?? null, $pharmacistIndex);
        if ($pharmacistUser === null && ! empty($event['pharmacist_name_raw'])) {
            $result['pharmacist_unmatched']++;
        }

        $existingRequest = $this->findExistingRequest($citizen, $event);
        $bypassDetected = $existingRequest === null;

        $centralRequest = $existingRequest;

        if ($bypassDetected) {
            $centralRequest = $this->createSyntheticDispensationRequest($citizen, $pharmacistUser, $actor, $event);
            $result['synthetic_created']++;
            $result['bypass_detected']++;

            $this->audit->log(
                $request,
                'FARMACIA_CENTRAL',
                'IMPORTACAO_EXTERNA_CRIAR_ATENDIMENTO',
                CentralPharmacyRequest::class,
                null,
                [
                    'import_id' => $batch->id,
                    'import_row_hash' => $rowHash,
                    'central_pharmacy_request_id' => $centralRequest->id,
                    'citizen_id' => $citizen->id,
                    'external_dispense_number' => $event['dispense_number'],
                    'pharmacist_name_raw' => $event['pharmacist_name_raw'] ?? null,
                ]
            );

            if (! (bool) $citizen->pharmacy_lock_flag) {
                $citizen->update(['pharmacy_lock_flag' => true]);
                $result['citizens_locked']++;

                $this->audit->log(
                    $request,
                    'FARMACIA_CENTRAL',
                    'IMPORTACAO_EXTERNA_BLOQUEIO_CIDADAO',
                    Citizen::class,
                    (int) $citizen->id,
                    [
                        'import_id' => $batch->id,
                        'import_row_hash' => $rowHash,
                        'external_dispense_number' => $event['dispense_number'],
                    ]
                );
            }
        } else {
            $result['matched_existing']++;
        }

        $recurrence = $this->computeRecurrenceForCitizen($citizen);
        if (($recurrence['alert_level'] ?? null) === 'ALTO') {
            $result['alerts_high']++;
        }

        if (($recurrence['alert_level'] ?? null) === 'MEDIO') {
            $result['alerts_medium']++;
        }

        $importRow = PharmacyExternalImportRow::create([
            'import_id' => $batch->id,
            'row_hash' => $rowHash,
            'external_dispense_number' => $event['dispense_number'],
            'dispensed_at' => $event['dispensed_at'],
            'customer_name_raw' => $event['customer_name_raw'],
            'customer_name_normalized' => $event['customer_name_normalized'],
            'pharmacist_name_raw' => $event['pharmacist_name_raw'] ?? null,
            'pharmacist_name_normalized' => $event['pharmacist_name_normalized'] ?? null,
            'pharmacist_user_id' => $pharmacistUser?->id,
            'citizen_id' => $citizen->id,
            'central_pharmacy_request_id' => $centralRequest?->id,
            'medication_name_raw' => $event['medication_name_raw'],
            'quantity' => $event['quantity'],
            'bypass_detected' => $bypassDetected,
            'recurrence_interval_days' => $recurrence['interval_days'] ?? null,
            'recurrence_alert_level' => $recurrence['alert_level'] ?? null,
            'payload' => $event['payload'] ?? null,
        ]);

        if (in_array($importRow->recurrence_alert_level, ['ALTO', 'MEDIO'], true)) {
            $result['alert'] = [
                'citizen' => (string) $citizen->full_name,
                'dispense_number' => $event['dispense_number'],
                'interval_days' => $recurrence['interval_days'] ?? null,
                'baseline_days' => $recurrence['baseline_days'] ?? null,
                'alert_level' => $importRow->recurrence_alert_level,
            ];
        }

        return $result;
    }

    /**
     * @param array<string, mixed> $event
     * @param array<string, mixed> $citizenIndex
     * @return array{citizen:Citizen,created:bool}
     */
    private function resolveCitizen(array $event, array &$citizenIndex): array
    {
        $normalizedName = $event['customer_name_normalized'] ?? null;

        if (is_string($normalizedName) && isset($citizenIndex['map'][$normalizedName])) {
            /** @var array<int, Citizen> $candidates */
            $candidates = $citizenIndex['map'][$normalizedName];

            if (count($candidates) > 0) {
                return ['citizen' => $candidates[0], 'created' => false];
            }
        }

        if (is_string($normalizedName) && $normalizedName !== '') {
            $best = null;
            $bestScore = 0.0;

            foreach ($citizenIndex['map'] as $nameKey => $citizens) {
                similar_text($normalizedName, $nameKey, $score);
                if ($score > $bestScore && $score >= 92.0 && isset($citizens[0])) {
                    $bestScore = $score;
                    $best = $citizens[0];
                }
            }

            if ($best instanceof Citizen) {
                return ['citizen' => $best, 'created' => false];
            }
        }

        $displayName = trim((string) ($event['customer_name_raw'] ?? ''));
        if ($displayName === '') {
            $displayName = 'CIDADAO IMPORTADO SEM IDENTIFICACAO';
        }

        $syntheticCpf = $this->generateSyntheticCpf($displayName.'|'.($event['dispense_number'] ?? '').'|'.($event['dispensed_at'] instanceof Carbon ? $event['dispensed_at']->format('Y-m-d H:i:s') : ''));

        $citizen = Citizen::create([
            'cpf' => $syntheticCpf,
            'cpf_hash' => hash('sha256', $syntheticCpf),
            'full_name' => Str::upper($displayName),
            'birth_date' => '1900-01-01',
            'is_resident_assai' => false,
            'pharmacy_lock_flag' => false,
            'phone' => null,
        ]);

        $newName = $this->normalizePersonName($citizen->full_name);
        if ($newName !== null) {
            $citizenIndex['map'][$newName][] = $citizen;
        }

        return ['citizen' => $citizen, 'created' => true];
    }

    /**
     * @param array<string, mixed> $pharmacistIndex
     */
    private function resolvePharmacistUser(?string $normalizedName, array $pharmacistIndex): ?User
    {
        if ($normalizedName === null || $normalizedName === '') {
            return null;
        }

        if (isset($pharmacistIndex['map'][$normalizedName])) {
            /** @var array<int, User> $exact */
            $exact = $pharmacistIndex['map'][$normalizedName];

            return $this->pickPreferredPharmacist($exact);
        }

        $best = null;
        $bestScore = 0.0;

        foreach ($pharmacistIndex['map'] as $nameKey => $users) {
            similar_text($normalizedName, $nameKey, $score);
            if ($score > $bestScore && $score >= 88.0) {
                $bestScore = $score;
                $best = $this->pickPreferredPharmacist($users);
            }
        }

        return $best instanceof User ? $best : null;
    }

    /**
     * @param array<int, User> $users
     */
    private function pickPreferredPharmacist(array $users): ?User
    {
        if ($users === []) {
            return null;
        }

        foreach ($users as $user) {
            if ($user->role === User::ROLE_FARMACIA) {
                return $user;
            }
        }

        return $users[0];
    }

    /**
     * @param array<string, mixed> $event
     */
    private function findExistingRequest(Citizen $citizen, array $event): ?CentralPharmacyRequest
    {
        $dispenseNumber = $event['dispense_number'] ?? null;
        $dispensedAt = $event['dispensed_at'] ?? null;
        $category = $this->resolveDispenseCategory($event['medication_name_raw'] ?? null);

        if (is_string($dispenseNumber) && $dispenseNumber !== '') {
            $byCode = CentralPharmacyRequest::query()
                ->where('citizen_id', $citizen->id)
                ->where('prescription_code', $dispenseNumber)
                ->latest('created_at')
                ->first();

            if ($byCode instanceof CentralPharmacyRequest) {
                return $byCode;
            }
        }

        $query = CentralPharmacyRequest::query()
            ->where('citizen_id', $citizen->id)
            ->whereIn('status', ['RECEPCAO_VALIDADA', 'DISPENSADO', 'DISPENSADO_EQUIVALENTE', 'NAO_DISPENSADO']);

        if ($dispensedAt instanceof Carbon) {
            $date = $dispensedAt->toDateString();

            $query->where(function ($dateQuery) use ($date): void {
                $dateQuery->whereDate('prescription_date', $date)
                    ->orWhereDate('dispensed_at', $date)
                    ->orWhereDate('created_at', $date);
            });
        }

        if ($category !== null) {
            $query->where('medication_name', $category);
        }

        return $query->latest('created_at')->first();
    }

    /**
     * @param array<string, mixed> $event
     */
    private function createSyntheticDispensationRequest(
        Citizen $citizen,
        ?User $pharmacistUser,
        User $actor,
        array $event
    ): CentralPharmacyRequest {
        $responsibleUserId = $pharmacistUser?->id ?? $actor->id;
        $category = $this->resolveDispenseCategory($event['medication_name_raw'] ?? null) ?? 'MEDICACAO';
        $dispensedAt = $event['dispensed_at'] instanceof Carbon ? $event['dispensed_at'] : now();
        $quantity = (int) ($event['quantity'] ?? 1);
        if ($quantity < 1) {
            $quantity = 1;
        }

        $notes = [
            'IMPORTACAO EXTERNA BETHA/TXT',
            'Dispensa fora do fluxo interno identificada automaticamente.',
        ];

        if (! empty($event['pharmacist_name_raw'])) {
            $notes[] = 'Farmaceutica no arquivo externo: '.(string) $event['pharmacist_name_raw'];
        }

        if (! empty($event['dispense_number'])) {
            $notes[] = 'Numero da dispensa externa: '.(string) $event['dispense_number'];
        }

        return CentralPharmacyRequest::create([
            'citizen_id' => $citizen->id,
            'reception_user_id' => $responsibleUserId,
            'attendant_user_id' => $responsibleUserId,
            'prescription_code' => $event['dispense_number'] ?? null,
            'prescription_date' => $dispensedAt->toDateString(),
            'prescriber_name' => 'IMPORTACAO EXTERNA BETHA',
            'medication_name' => $category,
            'concentration' => '-',
            'quantity' => $quantity,
            'dosage' => 'IMPORTADO EXTERNAMENTE',
            'gov_assai_level' => '0',
            'residence_status' => $citizen->is_resident_assai ? 'RESIDENTE' : 'PENDENTE',
            'status' => 'DISPENSADO',
            'notes' => implode(' | ', $notes),
            'dispensed_at' => $dispensedAt,
        ]);
    }

    /**
     * @return array{interval_days:int|null,baseline_days:float|null,alert_level:string}
     */
    private function computeRecurrenceForCitizen(Citizen $citizen): array
    {
        $timeline = CentralPharmacyRequest::query()
            ->where('citizen_id', $citizen->id)
            ->whereIn('status', ['DISPENSADO', 'DISPENSADO_EQUIVALENTE'])
            ->orderBy('dispensed_at')
            ->orderBy('created_at')
            ->get(['dispensed_at', 'created_at']);

        $dates = [];
        foreach ($timeline as $row) {
            $date = $row->dispensed_at ?? $row->created_at;
            if ($date instanceof Carbon) {
                $dates[] = $date->copy();
            }
        }

        if (count($dates) < 2) {
            return [
                'interval_days' => null,
                'baseline_days' => null,
                'alert_level' => 'SEM_HISTORICO',
            ];
        }

        $intervals = [];
        for ($i = 1; $i < count($dates); $i++) {
            $intervals[] = $dates[$i - 1]->diffInDays($dates[$i]);
        }

        $currentInterval = (int) end($intervals);

        $baseline = null;
        if (count($intervals) > 1) {
            $history = array_slice($intervals, 0, -1);
            $baseline = round(array_sum($history) / max(1, count($history)), 1);
        }

        $alertLevel = 'BAIXO';

        if ($baseline !== null) {
            $highThreshold = max(1, (int) floor($baseline * 0.5));
            $mediumThreshold = max(1, (int) floor($baseline * 0.8));

            if ($currentInterval <= $highThreshold) {
                $alertLevel = 'ALTO';
            } elseif ($currentInterval <= $mediumThreshold) {
                $alertLevel = 'MEDIO';
            }
        } elseif ($currentInterval <= 7) {
            $alertLevel = 'MEDIO';
        }

        return [
            'interval_days' => $currentInterval,
            'baseline_days' => $baseline,
            'alert_level' => $alertLevel,
        ];
    }

    /**
     * @param array<string, mixed> $csvEvent
     * @param array<int, array<string, mixed>> $txtEvents
     * @param array<string, array<int, int>> $txtByDispense
     * @param array<string, array<int, int>> $txtByNameDate
     * @param array<string, array<int, int>> $txtByDate
     * @param array<int, bool> $txtUsed
     */
    private function findTxtMatchIndex(
        array $csvEvent,
        array $txtEvents,
        array $txtByDispense,
        array $txtByNameDate,
        array $txtByDate,
        array $txtUsed
    ): ?int
    {
        $dispenseNumber = $csvEvent['dispense_number'] ?? null;
        if (is_string($dispenseNumber) && isset($txtByDispense[$dispenseNumber])) {
            $index = $this->pickFirstUnusedIndex($txtByDispense[$dispenseNumber], $txtUsed);
            if ($index !== null) {
                return $index;
            }

            return $this->pickFirstIndex($txtByDispense[$dispenseNumber]);
        }

        $nameDateKey = $this->buildNameDateKey($csvEvent['customer_name_normalized'] ?? null, $csvEvent['dispensed_at'] ?? null);
        if ($nameDateKey !== null && isset($txtByNameDate[$nameDateKey])) {
            $index = $this->pickFirstUnusedIndex($txtByNameDate[$nameDateKey], $txtUsed);
            if ($index !== null) {
                return $index;
            }

            return $this->pickFirstIndex($txtByNameDate[$nameDateKey]);
        }

        $csvName = $csvEvent['customer_name_normalized'] ?? null;
        $csvDate = ($csvEvent['dispensed_at'] ?? null) instanceof Carbon ? $csvEvent['dispensed_at']->toDateString() : null;

        if (! is_string($csvName) || $csvName === '' || ! is_string($csvDate) || ! isset($txtByDate[$csvDate])) {
            return null;
        }

        $bestUnusedIndex = null;
        $bestUnusedScore = 0.0;
        $bestAnyIndex = null;
        $bestAnyScore = 0.0;

        foreach ($txtByDate[$csvDate] as $candidateIndex) {
            $txtName = $txtEvents[$candidateIndex]['customer_name_normalized'] ?? null;
            if (! is_string($txtName) || $txtName === '') {
                continue;
            }

            similar_text($csvName, $txtName, $score);

            if (str_contains($csvName, $txtName) || str_contains($txtName, $csvName)) {
                $score = max($score, 94.0);
            }

            if (! isset($txtUsed[$candidateIndex]) && $score > $bestUnusedScore) {
                $bestUnusedScore = $score;
                $bestUnusedIndex = $candidateIndex;
            }

            if ($score > $bestAnyScore) {
                $bestAnyScore = $score;
                $bestAnyIndex = $candidateIndex;
            }
        }

        if ($bestUnusedIndex !== null && $bestUnusedScore >= 88.0) {
            return $bestUnusedIndex;
        }

        if ($bestAnyIndex !== null && $bestAnyScore >= 92.0) {
            return $bestAnyIndex;
        }

        return null;
    }

    /**
     * @param array<int, int> $indices
     * @param array<int, bool> $used
     */
    private function pickFirstUnusedIndex(array $indices, array $used): ?int
    {
        foreach ($indices as $index) {
            if (! isset($used[$index])) {
                return $index;
            }
        }

        return null;
    }

    /**
     * @param array<int, int> $indices
     */
    private function pickFirstIndex(array $indices): ?int
    {
        foreach ($indices as $index) {
            return $index;
        }

        return null;
    }

    /**
     * @return array<string, mixed>
     */
    private function buildCitizenIndex(): array
    {
        $map = [];

        $citizens = Citizen::query()->get(['id', 'full_name', 'cpf_hash', 'pharmacy_lock_flag', 'is_resident_assai', 'birth_date']);

        foreach ($citizens as $citizen) {
            $normalized = $this->normalizePersonName((string) $citizen->full_name);
            if ($normalized === null) {
                continue;
            }

            $map[$normalized][] = $citizen;
        }

        return [
            'map' => $map,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function buildPharmacistIndex(): array
    {
        $map = [];

        $users = User::query()->get(['id', 'name', 'role', 'permissions']);

        foreach ($users as $user) {
            $normalized = $this->normalizePersonName((string) $user->name);
            if ($normalized === null) {
                continue;
            }

            $map[$normalized][] = $user;
        }

        return [
            'map' => $map,
        ];
    }

    /**
     * @param array<int, string> $row
     * @return array<string, int>|null
     */
    private function detectCsvHeaderMap(array $row): ?array
    {
        $synonyms = [
            'dispense_number' => ['NUMERODISPENSA', 'NUMERODISPENSACAO', 'NRODISPENSA', 'NRDISPENSA', 'DISPENSA'],
            'dispensed_at' => ['DATA', 'DATADISPENSA', 'DATADISPENSACAO', 'DATAHORA', 'DATAHORA'],
            'customer_name' => ['CLIENTE', 'NOMECLIENTE', 'USUARIO', 'PACIENTE', 'CIDADAO'],
            'medication_name' => ['MEDICAMENTO', 'MEDICACAO', 'PRODUTO', 'ITEM', 'DESCRICAO'],
            'quantity' => ['QUANTIDADE', 'QTD', 'QTDE'],
        ];

        $map = [];

        foreach ($row as $index => $column) {
            $key = $this->normalizeHeaderKey($column);
            foreach ($synonyms as $field => $candidates) {
                if (in_array($key, $candidates, true)) {
                    $map[$field] = $index;
                }
            }
        }

        $significant = 0;
        foreach (['customer_name', 'medication_name', 'dispense_number', 'dispensed_at'] as $field) {
            if (array_key_exists($field, $map)) {
                $significant++;
            }
        }

        return $significant >= 2 ? $map : null;
    }

    /**
     * @param array<int, string> $row
     * @param array<string, int> $headerMap
     * @return array<string, mixed>|null
     */
    private function extractCsvEventFromHeaderRow(array $row, array $headerMap, int $lineNumber): ?array
    {
        $customer = $this->valueAt($row, $headerMap['customer_name'] ?? null);
        $medication = $this->valueAt($row, $headerMap['medication_name'] ?? null);
        $dispenseNumber = $this->extractDispenseNumber($this->valueAt($row, $headerMap['dispense_number'] ?? null));
        $date = $this->extractDateFromText($this->valueAt($row, $headerMap['dispensed_at'] ?? null));
        $quantity = $this->extractQuantity($this->valueAt($row, $headerMap['quantity'] ?? null));

        if ($customer === null && $dispenseNumber === null && $medication === null) {
            return null;
        }

        return [
            'source' => 'csv',
            'line_number' => $lineNumber,
            'dispense_number' => $dispenseNumber,
            'dispensed_at' => $date,
            'customer_name_raw' => $customer,
            'customer_name_normalized' => $this->normalizePersonName($customer),
            'medication_name_raw' => $medication,
            'quantity' => $quantity ?? 1,
            'payload' => [
                'row' => $row,
                'header_map' => $headerMap,
            ],
        ];
    }

    /**
     * @param array<int, string> $row
     */
    private function isRowEmpty(array $row): bool
    {
        foreach ($row as $value) {
            if (trim((string) $value) !== '') {
                return false;
            }
        }

        return true;
    }

    private function valueAt(array $row, ?int $index): ?string
    {
        if ($index === null || ! array_key_exists($index, $row)) {
            return null;
        }

        $value = trim((string) $row[$index]);

        return $value === '' ? null : $value;
    }

    /**
     * @param array<int, string> $row
     */
    private function extractMedicationFromFreeRow(array $row): ?string
    {
        foreach ($row as $cell) {
            $value = trim((string) $cell);
            if ($value === '') {
                continue;
            }

            $key = $this->normalizeHeaderKey($value);
            if (in_array($key, ['CLIENTE', 'NUMERODISPENSA', 'DATA', 'QTD', 'QUANTIDADE'], true)) {
                continue;
            }

            if ($this->extractDispenseNumber($value) !== null) {
                continue;
            }

            if ($this->extractDateFromText($value) !== null) {
                continue;
            }

            if ($this->extractQuantity($value) !== null && preg_match('/^\d+[\.,]?\d*$/', $value) === 1) {
                continue;
            }

            return $value;
        }

        return null;
    }

    /**
     * @param array<int, string> $row
     */
    private function extractQuantityFromFreeRow(array $row): ?int
    {
        foreach ($row as $cell) {
            $quantity = $this->extractQuantity($cell);
            if ($quantity !== null) {
                return $quantity;
            }
        }

        return null;
    }

    private function extractQuantity(?string $value): ?int
    {
        if ($value === null) {
            return null;
        }

        $clean = trim((string) $value);
        if ($clean === '') {
            return null;
        }

        $clean = str_replace(',', '.', preg_replace('/[^\d,\.]/', '', $clean) ?? '');
        if ($clean === '' || ! is_numeric($clean)) {
            return null;
        }

        $quantity = (int) round((float) $clean);

        return $quantity > 0 ? $quantity : null;
    }

    private function extractDispenseNumber(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $text = trim((string) $value);
        if ($text === '') {
            return null;
        }

        if (preg_match('/(?:dispensa(?:cao)?|n[ºo°]?|nr|numero)\s*[:#-]?\s*(\d{3,})/iu', $text, $matches) === 1) {
            return $matches[1];
        }

        if (preg_match('/\b(\d{4,})\b/', $text, $matches) === 1) {
            return $matches[1];
        }

        return null;
    }

    private function extractCustomerFromText(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $text = trim((string) $value);
        if ($text === '') {
            return null;
        }

        if (preg_match('/(?:nome\s+do\s+cliente|cliente|paciente|cidad[aã]o|usu[aá]rio)\s*[:\-]\s*([^;\|\t]+)/iu', $text, $matches) === 1) {
            $candidate = trim($matches[1]);
            return $candidate !== '' ? $candidate : null;
        }

        if (preg_match('/^([A-ZÀ-Ú][A-ZÀ-Ú\s\'\.-]{4,})$/u', Str::upper($text), $matches) === 1) {
            return trim($matches[1]);
        }

        return null;
    }

    private function extractPharmacistFromText(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $text = trim((string) $value);
        if ($text === '') {
            return null;
        }

        if (preg_match('/(?:usu[aá]rio|farmac[eê]utic[ao]|respons[aá]vel)\s*[:\-]\s*(.+)$/iu', $text, $matches) === 1) {
            $candidate = trim($matches[1]);
            if ($candidate !== '') {
                return $candidate;
            }
        }

        return null;
    }

    private function extractLikelyPersonName(string $line): ?string
    {
        $candidate = trim($line);
        if ($candidate === '') {
            return null;
        }

        $normalized = $this->normalizePersonName($candidate);
        if ($normalized === null) {
            return null;
        }

        if (strlen($normalized) < 6) {
            return null;
        }

        if (preg_match('/\b(USUARIO|DATA|DISPENSA|TOTAL|PAGINA|RELATORIO)\b/u', $normalized) === 1) {
            return null;
        }

        return $candidate;
    }

    private function extractDateFromText(?string $value): ?Carbon
    {
        if ($value === null) {
            return null;
        }

        $text = trim((string) $value);
        if ($text === '') {
            return null;
        }

        if (preg_match('/\b(\d{2}\/\d{2}\/\d{4}(?:\s*(?:-|AS)?\s*\d{2}:\d{2}(?::\d{2})?)?)\b/i', $text, $matches) === 1) {
            $parsed = $this->parseDateTime($matches[1]);
            if ($parsed !== null) {
                return $parsed;
            }
        }

        if (preg_match('/\b(\d{4}-\d{2}-\d{2}(?:\s*(?:-|AS)?\s*\d{2}:\d{2}(?::\d{2})?)?)\b/i', $text, $matches) === 1) {
            $parsed = $this->parseDateTime($matches[1]);
            if ($parsed !== null) {
                return $parsed;
            }
        }

        return $this->parseDateTime($text);
    }

    private function parseDateTime(string $value): ?Carbon
    {
        $normalized = trim(preg_replace('/\s*-\s*/', ' ', $value) ?? $value);

        $formats = [
            'd/m/Y H:i:s',
            'd/m/Y H:i',
            'd/m/Y',
            'Y-m-d H:i:s',
            'Y-m-d H:i',
            'Y-m-d',
        ];

        foreach ($formats as $format) {
            try {
                return Carbon::createFromFormat($format, $normalized);
            } catch (\Throwable) {
                // try next format
            }
        }

        $timestamp = strtotime($normalized);
        if ($timestamp === false) {
            return null;
        }

        return Carbon::createFromTimestamp($timestamp);
    }

    private function normalizeHeaderKey(?string $value): string
    {
        $normalized = Str::upper(Str::ascii((string) $value));
        $normalized = preg_replace('/[^A-Z0-9]/', '', $normalized) ?? '';

        return $normalized;
    }

    private function normalizePersonName(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $normalized = trim((string) $value);
        if ($normalized === '') {
            return null;
        }

        $normalized = Str::upper(Str::ascii($normalized));
        $normalized = preg_replace('/[^A-Z0-9\s]/', ' ', $normalized) ?? '';
        $normalized = preg_replace('/\s+/', ' ', $normalized) ?? '';
        $normalized = trim($normalized);

        return $normalized === '' ? null : $normalized;
    }

    private function resolveDispenseCategory(?string $medicationName): ?string
    {
        if (! is_string($medicationName) || trim($medicationName) === '') {
            return 'MEDICACAO';
        }

        $normalized = Str::upper(Str::ascii($medicationName));

        if (str_contains($normalized, 'LEITE')) {
            return 'LEITE';
        }

        if (str_contains($normalized, 'SUPLEMENT') || str_contains($normalized, 'NUTRI')) {
            return 'SUPLEMENTO';
        }

        return 'MEDICACAO';
    }

    private function buildRowHash(array $event): string
    {
        return hash('sha256', implode('|', [
            (string) ($event['dispense_number'] ?? ''),
            (string) ($event['customer_name_normalized'] ?? ''),
            ($event['dispensed_at'] instanceof Carbon) ? $event['dispensed_at']->format('Y-m-d H:i:s') : '',
            (string) ($event['medication_name_raw'] ?? ''),
            (string) ($event['quantity'] ?? ''),
            (string) ($event['pharmacist_name_normalized'] ?? ''),
        ]));
    }

    private function buildNameDateKey(?string $normalizedName, mixed $dispensedAt): ?string
    {
        if (! is_string($normalizedName) || $normalizedName === '') {
            return null;
        }

        if (! $dispensedAt instanceof Carbon) {
            return null;
        }

        return $normalizedName.'|'.$dispensedAt->toDateString();
    }

    private function stripUtf8Bom(string $value): string
    {
        if (str_starts_with($value, "\xEF\xBB\xBF")) {
            return substr($value, 3);
        }

        return $value;
    }

    private function generateSyntheticCpf(string $seed): string
    {
        $counter = 0;

        do {
            $hash = hash('sha256', $seed.'|'.$counter);
            $digits = preg_replace('/[^0-9]/', '', $hash) ?? '';
            if (strlen($digits) < 11) {
                $digits = str_pad($digits, 11, (string) ($counter % 10));
            }

            $candidate = substr($digits, 0, 11);
            $exists = Citizen::query()->where('cpf_hash', hash('sha256', $candidate))->exists();
            $counter++;
        } while ($exists && $counter < 1000);

        return $candidate;
    }
}
