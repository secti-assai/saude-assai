<?php

namespace App\Services;

use App\Models\PharmacyExternalImportRow;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

class PharmacyReportService
{
    public function buildCsvExport(array $input): array
    {
        $filters = $this->normalizeFilters($input);

        $rows = $this->baseFilteredQuery($filters)
            ->orderBy('dispensed_at')
            ->cursor()
            ->map(function (PharmacyExternalImportRow $row): array {
                $status = $row->bypass_detected ? 'NÃO REGULARIZADO' : 'REGULARIZADO';
                $citizenName = $row->citizen ? $row->citizen->full_name : $row->customer_name_raw;

                return [
                    $citizenName,
                    $status,
                    (string) ($row->quantity ?? ''),
                    $row->dispensed_at?->format('d/m/Y H:i') ?? '',
                    (string) ($row->medication_name_raw ?? ''),
                ];
            });

        $filename = sprintf(
            'farmacia-relatorio-simplificado-%s-a-%s.csv',
            $filters['date_start'],
            $filters['date_end']
        );

        return [
            'filename' => $filename,
            'filters' => $filters,
            'headers' => [
                'Cidadão',
                'Situação',
                'Quantidade',
                'Horário da Dispensação',
                'Medicamento Dispensado',
            ],
            'rows' => $rows,
        ];
    }

    public function buildPdfExport(array $input): array
    {
        $filters = $this->normalizeFilters($input);

        $rowsQuery = $this->baseFilteredQuery($filters);
        $rows = $rowsQuery
            ->orderByDesc('dispensed_at')
            ->get();

        $filename = sprintf(
            'farmacia-relatorio-simplificado-%s-a-%s.pdf',
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

    public function build(array $input): array
    {
        $filters = $this->normalizeFilters($input);

        $rowsQuery = $this->baseFilteredQuery($filters);

        $rows = $rowsQuery
            ->orderByDesc('dispensed_at')
            ->paginate(25)
            ->withQueryString();

        $totalEvents = (clone $rowsQuery)->count();
        $totalBypass = (clone $rowsQuery)->where('bypass_detected', true)->count();
        $totalRegular = $totalEvents - $totalBypass;

        return [
            'filters' => $filters,
            'summary' => [
                'total_events' => $totalEvents,
                'total_regular' => $totalRegular,
                'total_bypass' => $totalBypass,
            ],
            'rows' => $rows,
        ];
    }

    private function baseFilteredQuery(array $filters): Builder
    {
        $query = PharmacyExternalImportRow::query()
            ->with(['citizen']);

        if ($filters['date_start']) {
            $query->whereDate('dispensed_at', '>=', $filters['date_start']);
        }
        if ($filters['date_end']) {
            $query->whereDate('dispensed_at', '<=', $filters['date_end']);
        }

        if ($filters['status'] === 'REGULARIZADO') {
            $query->where('bypass_detected', false);
        } elseif ($filters['status'] === 'NAO_REGULARIZADO') {
            $query->where('bypass_detected', true);
        }

        if ($filters['citizen_name'] !== '') {
            $needle = '%'.Str::lower($filters['citizen_name']).'%';

            $query->where(function ($q) use ($needle) {
                $q->whereRaw('LOWER(customer_name_raw) LIKE ?', [$needle])
                  ->orWhereHas('citizen', function ($qc) use ($needle) {
                      $qc->whereRaw('LOWER(full_name) LIKE ?', [$needle]);
                  });
            });
        }

        return $query;
    }

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
        if (! in_array($status, ['TODOS', 'REGULARIZADO', 'NAO_REGULARIZADO'], true)) {
            $status = 'TODOS';
        }

        $citizenName = trim((string) ($input['citizen_name'] ?? ''));

        return [
            'date_start' => $dateStart,
            'date_end' => $dateEnd,
            'status' => $status,
            'citizen_name' => $citizenName,
        ];
    }
}
