<?php

namespace App\Services;

use App\Models\CentralPharmacyRequest;
use App\Models\Citizen;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\LazyCollection;
use Illuminate\Support\Str;

class PharmacyReportService
{
    /**
     * @return array{filename:string,filters:array{date_start:string,date_end:string,status:string,dispense_category:string,gov_level:string,needs_validation:string,citizen_name:string},headers:array<int,string>,rows:LazyCollection<int,array<int,string>>}
     */
    public function buildCsvExport(array $input): array
    {
        $filters = $this->normalizeFilters($input);

        $rows = $this->baseFilteredQuery($filters)
            ->orderBy('created_at')
            ->cursor()
            ->map(function (CentralPharmacyRequest $row): array {
                $lockFlag = (bool) ($row->citizen->pharmacy_lock_flag ?? false);

                return [
                    (string) $row->id,
                    $row->created_at?->format('d/m/Y H:i') ?? '',
                    ($row->dispensed_at ?? $row->created_at)?->format('d/m/Y H:i') ?? '',
                    (string) ($row->citizen->full_name ?? ''),
                    (string) ($row->gov_assai_level ?? 'N/A'),
                    $lockFlag ? 'PENDENTE_NIVEL_2' : 'REGULARIZADO',
                    (string) ($row->status ?? ''),
                    $this->categoryLabel($row->medication_name),
                    (string) ($row->quantity ?? ''),
                    (string) ($row->prescriber_name ?? ''),
                    (string) ($row->prescription_code ?? ''),
                    $row->prescription_date?->format('d/m/Y') ?? '',
                    (string) ($row->attendant_display_name ?? ''),
                    (string) ($row->reception->name ?? ''),
                    (string) ($row->residence_status ?? ''),
                    (string) ($row->refusal_reason ?? ''),
                    (string) ($row->equivalent_medication_name ?? ''),
                    (string) ($row->equivalent_concentration ?? ''),
                    (string) ($row->feedback_score ?? ''),
                    (string) ($row->feedback_comment ?? ''),
                ];
            });

        $filename = sprintf(
            'farmacia-central-atendimentos-%s-a-%s.csv',
            $filters['date_start'],
            $filters['date_end']
        );

        return [
            'filename' => $filename,
            'filters' => $filters,
            'headers' => [
                'id_atendimento',
                'data_registro',
                'data_atendimento',
                'cidadao',
                'nivel_gov_assai',
                'validacao_nivel_2',
                'status',
                'categoria',
                'quantidade',
                'prescritor',
                'codigo_receita',
                'data_receita',
                'atendente',
                'recepcao',
                'status_residencia',
                'motivo_recusa',
                'medicacao_equivalente',
                'concentracao_equivalente',
                'avaliacao_nota',
                'avaliacao_comentario',
            ],
            'rows' => $rows,
        ];
    }

    public function buildPdfExport(array $input): array
    {
        $filters = $this->normalizeFilters($input);

        $rowsQuery = $this->baseFilteredQuery($filters);
        $rows = $rowsQuery
            ->orderByDesc('created_at')
            ->get();

        $filename = sprintf(
            'farmacia-central-relatorio-%s-a-%s.pdf',
            $filters['date_start'],
            $filters['date_end']
        );

        return [
            'filename' => $filename,
            'filters' => $filters,
            'data' => [
                'filters' => $filters,
                'rows' => $rows,
            ],
        ];
    }

    /**
     * @return array{filters:array,summary:array,statusBreakdown:Collection<int,array{status:string,total:int}>,levelBreakdown:Collection<int,array{level:string,total:int}>,categoryBreakdown:Collection<int,array{category:string,total:int}>,rows:\Illuminate\Contracts\Pagination\LengthAwarePaginator}
     */
    public function build(array $input): array
    {
        $filters = $this->normalizeFilters($input);

        $rowsQuery = $this->baseFilteredQuery($filters);

        $rows = $rowsQuery
            ->orderByDesc('created_at')
            ->paginate(25)
            ->withQueryString();

        $periodQuery = CentralPharmacyRequest::query();
        $this->applyDateFilter($periodQuery, $filters);

        $totalEvents = (clone $periodQuery)->count();
        $totalDispensed = (clone $periodQuery)->whereIn('status', $this->dispensedStatuses())->count();
        $totalCitizensInPeriod = (clone $periodQuery)->distinct('citizen_id')->count('citizen_id');
        $activeAttendants = (clone $periodQuery)->whereNotNull('attendant_user_id')->distinct('attendant_user_id')->count('attendant_user_id');
        $pendingLevelTwoValidation = Citizen::query()->where('pharmacy_lock_flag', true)->count();

        $dispensedRows = (clone $periodQuery)
            ->whereIn('status', $this->dispensedStatuses())
            ->get(['gov_assai_level', 'medication_name']);

        $lowLevelDispensed = $dispensedRows->filter(fn (CentralPharmacyRequest $row): bool => $this->toLevelNumber($row->gov_assai_level) < 2)->count();
        $levelTwoPlusDispensed = $dispensedRows->filter(fn (CentralPharmacyRequest $row): bool => $this->toLevelNumber($row->gov_assai_level) >= 2)->count();
        $regularizationRate = $totalDispensed > 0
            ? round(($levelTwoPlusDispensed / $totalDispensed) * 100, 1)
            : 0.0;

        $statusBreakdown = (clone $periodQuery)
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->orderByDesc('total')
            ->get()
            ->map(fn ($row): array => [
                'status' => (string) $row->status,
                'total' => (int) $row->total,
            ]);

        $levelCounts = [];
        foreach ($dispensedRows as $row) {
            $level = $row->gov_assai_level;
            $label = $this->levelLabel($level);
            $levelCounts[$label] = ($levelCounts[$label] ?? 0) + 1;
        }

        $levelBreakdown = collect($levelCounts)
            ->map(fn (int $total, string $level): array => [
                'level' => $level,
                'total' => $total,
            ])
            ->values()
            ->sortBy(fn (array $row): int => $row['level'] === 'N/A' ? 999 : (int) $row['level'])
            ->values();

        $categoryCounts = [];
        foreach ($dispensedRows as $row) {
            $category = $this->categoryLabel($row->medication_name);
            $categoryCounts[$category] = ($categoryCounts[$category] ?? 0) + 1;
        }

        $categoryBreakdown = collect($categoryCounts)
            ->map(fn (int $total, string $category): array => [
                'category' => $category,
                'total' => $total,
            ])
            ->sortBy('category')
            ->values();

        return [
            'filters' => $filters,
            'summary' => [
                'total_events' => $totalEvents,
                'total_dispensed' => $totalDispensed,
                'total_citizens_period' => $totalCitizensInPeriod,
                'active_attendants' => $activeAttendants,
                'pending_level_two_validation' => $pendingLevelTwoValidation,
                'low_level_dispensed' => $lowLevelDispensed,
                'level_two_plus_dispensed' => $levelTwoPlusDispensed,
                'regularization_rate' => $regularizationRate,
            ],
            'statusBreakdown' => $statusBreakdown,
            'levelBreakdown' => $levelBreakdown,
            'categoryBreakdown' => $categoryBreakdown,
            'rows' => $rows,
        ];
    }

    private function baseFilteredQuery(array $filters): Builder
    {
        $query = CentralPharmacyRequest::query()
            ->with(['citizen', 'reception', 'attendant']);

        $this->applyDateFilter($query, $filters);
        $this->applyStatusFilter($query, $filters['status']);
        $this->applyTextFilters($query, $filters);

        return $query;
    }

    /**
     * @return array{date_start:string,date_end:string,status:string,dispense_category:string,gov_level:string,needs_validation:string,citizen_name:string}
     */
    private function normalizeFilters(array $input): array
    {
        $dateStart = trim((string) ($input['date_start'] ?? now()->subDays(30)->toDateString()));
        $dateEnd = trim((string) ($input['date_end'] ?? now()->toDateString()));

        if (! preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateStart)) {
            $dateStart = now()->subDays(30)->toDateString();
        }

        if (! preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateEnd)) {
            $dateEnd = now()->toDateString();
        }

        if ($dateStart > $dateEnd) {
            [$dateStart, $dateEnd] = [$dateEnd, $dateStart];
        }

        $status = (string) ($input['status'] ?? 'TODOS');
        $allowedStatus = ['DISPENSADOS', 'TODOS', 'RECEPCAO_VALIDADA', 'DISPENSADO', 'DISPENSADO_EQUIVALENTE', 'NAO_DISPENSADO'];
        if (! in_array($status, $allowedStatus, true)) {
            $status = 'TODOS';
        }

        $dispenseCategory = Str::upper(trim((string) ($input['dispense_category'] ?? 'ALL')));
        if (! in_array($dispenseCategory, ['ALL', 'MEDICACAO', 'LEITE', 'SUPLEMENTO'], true)) {
            $dispenseCategory = 'ALL';
        }

        $needsValidation = (string) ($input['needs_validation'] ?? 'all');
        if (! in_array($needsValidation, ['all', 'yes', 'no'], true)) {
            $needsValidation = 'all';
        }

        $govLevel = trim((string) ($input['gov_level'] ?? ''));
        if ($govLevel !== '' && ! preg_match('/^\d{1,2}$/', $govLevel)) {
            $govLevel = '';
        }

        $citizenName = trim((string) ($input['citizen_name'] ?? ''));

        return [
            'date_start' => $dateStart,
            'date_end' => $dateEnd,
            'status' => $status,
            'dispense_category' => $dispenseCategory,
            'gov_level' => $govLevel,
            'needs_validation' => $needsValidation,
            'citizen_name' => $citizenName,
        ];
    }

    private function applyDateFilter(Builder $query, array $filters): void
    {
        $query
            ->whereDate('created_at', '>=', $filters['date_start'])
            ->whereDate('created_at', '<=', $filters['date_end']);
    }

    private function applyStatusFilter(Builder $query, string $status): void
    {
        if ($status === 'TODOS') {
            return;
        }

        if ($status === 'DISPENSADOS') {
            $query->whereIn('status', $this->dispensedStatuses());

            return;
        }

        $query->where('status', $status);
    }

    private function applyTextFilters(Builder $query, array $filters): void
    {
        if (($filters['dispense_category'] ?? 'ALL') !== 'ALL') {
            $query->where('medication_name', $filters['dispense_category']);
        }

        if ($filters['gov_level'] !== '') {
            $query->where('gov_assai_level', $filters['gov_level']);
        }

        if ($filters['needs_validation'] === 'yes') {
            $query->whereHas('citizen', fn (Builder $citizen): Builder => $citizen->where('pharmacy_lock_flag', true));
        }

        if ($filters['needs_validation'] === 'no') {
            $query->whereHas('citizen', fn (Builder $citizen): Builder => $citizen->where('pharmacy_lock_flag', false));
        }

        if ($filters['citizen_name'] !== '') {
            $needle = '%'.Str::lower($filters['citizen_name']).'%';

            $query->whereHas('citizen', function (Builder $citizen) use ($needle): Builder {
                return $citizen->whereRaw('LOWER(full_name) LIKE ?', [$needle]);
            });
        }
    }

    /**
     * @return array<int,string>
     */
    private function dispensedStatuses(): array
    {
        return ['DISPENSADO', 'DISPENSADO_EQUIVALENTE'];
    }

    private function toLevelNumber(?string $level): int
    {
        if (! is_string($level) || trim($level) === '' || ! is_numeric($level)) {
            return 0;
        }

        return (int) $level;
    }

    private function levelLabel(?string $level): string
    {
        if (! is_string($level) || trim($level) === '' || ! is_numeric($level)) {
            return 'N/A';
        }

        return (string) ((int) $level);
    }

    private function categoryLabel(?string $category): string
    {
        $normalized = Str::upper(trim((string) $category));

        return match ($normalized) {
            'LEITE' => 'LEITE',
            'SUPLEMENTO' => 'SUPLEMENTO',
            'MEDICACAO' => 'MEDICACAO',
            default => 'N/A',
        };
    }
}
