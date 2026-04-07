<?php

namespace App\Services;

use App\Models\WomenClinicAppointment;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class WomenClinicReportService
{
    /**
     * @return array{filters:array,summary:array,statusBreakdown:Collection<int,array{status:string,total:int}>,feedbackBreakdown:Collection<int,array{score:string,total:int}>,dailyBreakdown:Collection<int,array{day:string,total:int,finalized:int,feedbacks:int}>,rows:\Illuminate\Contracts\Pagination\LengthAwarePaginator}
     */
    public function build(array $input): array
    {
        $filters = $this->normalizeFilters($input);

        $rowsQuery = WomenClinicAppointment::query()
            ->with(['citizen', 'scheduler', 'reception', 'doctor']);

        $this->applyDateFilter($rowsQuery, $filters);
        $this->applyStatusFilter($rowsQuery, $filters['status']);
        $this->applyTextFilters($rowsQuery, $filters);

        $rows = $rowsQuery
            ->orderByDesc('scheduled_for')
            ->paginate(25)
            ->withQueryString();

        $periodQuery = WomenClinicAppointment::query();
        $this->applyDateFilter($periodQuery, $filters);

        $totalAppointments = (clone $periodQuery)->count();
        $totalScheduled = (clone $periodQuery)->where('status', 'AGENDADO')->count();
        $totalCheckin = (clone $periodQuery)->where('status', 'CHECKIN')->count();
        $totalFinalized = (clone $periodQuery)->where('status', 'FINALIZADO')->count();
        $totalCancelled = (clone $periodQuery)->where('status', 'CANCELADO')->count();
        $totalWithFeedback = (clone $periodQuery)->whereNotNull('feedback_submitted_at')->count();

        $averageFeedbackScore = round((float) ((clone $periodQuery)->whereNotNull('feedback_score')->avg('feedback_score') ?? 0), 2);
        $feedbackCoverageRate = $totalFinalized > 0
            ? round(($totalWithFeedback / $totalFinalized) * 100, 1)
            : 0.0;

        $delayedScheduled = (clone $periodQuery)
            ->where('status', 'AGENDADO')
            ->where('scheduled_for', '<', now())
            ->count();

        $waitAndServiceSamples = (clone $periodQuery)
            ->whereNotNull('scheduled_for')
            ->get(['scheduled_for', 'checked_in_at', 'checked_out_at']);

        [$averageWaitMinutes, $averageServiceMinutes] = $this->computeAverageTimes($waitAndServiceSamples);

        $statusBreakdown = (clone $periodQuery)
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->orderByDesc('total')
            ->get()
            ->map(fn ($row): array => [
                'status' => (string) $row->status,
                'total' => (int) $row->total,
            ]);

        $feedbackCounts = ['1' => 0, '2' => 0, '3' => 0, '4' => 0, '5' => 0];
        foreach ((clone $periodQuery)->whereNotNull('feedback_score')->get(['feedback_score']) as $row) {
            $score = (string) ((int) $row->feedback_score);
            if (isset($feedbackCounts[$score])) {
                $feedbackCounts[$score]++;
            }
        }

        $feedbackBreakdown = collect($feedbackCounts)
            ->map(fn (int $total, string $score): array => [
                'score' => $score,
                'total' => $total,
            ])
            ->values();

        $dailyRows = (clone $periodQuery)
            ->orderBy('scheduled_for')
            ->get(['scheduled_for', 'status', 'feedback_submitted_at']);

        $dailyAccumulator = [];
        foreach ($dailyRows as $row) {
            $day = $row->scheduled_for?->toDateString() ?? 'N/A';
            if (! isset($dailyAccumulator[$day])) {
                $dailyAccumulator[$day] = [
                    'day' => $day,
                    'total' => 0,
                    'finalized' => 0,
                    'feedbacks' => 0,
                ];
            }

            $dailyAccumulator[$day]['total']++;
            if ((string) $row->status === 'FINALIZADO') {
                $dailyAccumulator[$day]['finalized']++;
            }
            if ($row->feedback_submitted_at !== null) {
                $dailyAccumulator[$day]['feedbacks']++;
            }
        }

        $dailyBreakdown = collect(array_values($dailyAccumulator))
            ->sortByDesc('day')
            ->take(15)
            ->values();

        return [
            'filters' => $filters,
            'summary' => [
                'total_appointments' => $totalAppointments,
                'total_scheduled' => $totalScheduled,
                'total_checkin' => $totalCheckin,
                'total_finalized' => $totalFinalized,
                'total_cancelled' => $totalCancelled,
                'total_with_feedback' => $totalWithFeedback,
                'average_feedback_score' => $averageFeedbackScore,
                'feedback_coverage_rate' => $feedbackCoverageRate,
                'average_wait_minutes' => $averageWaitMinutes,
                'average_service_minutes' => $averageServiceMinutes,
                'delayed_scheduled' => $delayedScheduled,
            ],
            'statusBreakdown' => $statusBreakdown,
            'feedbackBreakdown' => $feedbackBreakdown,
            'dailyBreakdown' => $dailyBreakdown,
            'rows' => $rows,
        ];
    }

    /**
     * @return array{date_start:string,date_end:string,status:string,has_feedback:string,citizen_name:string}
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

        $status = Str::upper(trim((string) ($input['status'] ?? 'TODOS')));
        if (! in_array($status, ['TODOS', 'AGENDADO', 'CHECKIN', 'FINALIZADO', 'CANCELADO'], true)) {
            $status = 'TODOS';
        }

        $hasFeedback = (string) ($input['has_feedback'] ?? 'all');
        if (! in_array($hasFeedback, ['all', 'yes', 'no'], true)) {
            $hasFeedback = 'all';
        }

        $citizenName = trim((string) ($input['citizen_name'] ?? ''));

        return [
            'date_start' => $dateStart,
            'date_end' => $dateEnd,
            'status' => $status,
            'has_feedback' => $hasFeedback,
            'citizen_name' => $citizenName,
        ];
    }

    private function applyDateFilter(Builder $query, array $filters): void
    {
        $query
            ->whereDate('scheduled_for', '>=', $filters['date_start'])
            ->whereDate('scheduled_for', '<=', $filters['date_end']);
    }

    private function applyStatusFilter(Builder $query, string $status): void
    {
        if ($status === 'TODOS') {
            return;
        }

        $query->where('status', $status);
    }

    private function applyTextFilters(Builder $query, array $filters): void
    {
        if ($filters['has_feedback'] === 'yes') {
            $query->whereNotNull('feedback_submitted_at');
        }

        if ($filters['has_feedback'] === 'no') {
            $query->whereNull('feedback_submitted_at');
        }

        if ($filters['citizen_name'] !== '') {
            $needle = '%'.Str::lower($filters['citizen_name']).'%';

            $query->whereHas('citizen', function (Builder $citizen) use ($needle): Builder {
                return $citizen->whereRaw('LOWER(full_name) LIKE ?', [$needle]);
            });
        }
    }

    /**
     * @param  Collection<int,WomenClinicAppointment>  $samples
     * @return array{0:float,1:float}
     */
    private function computeAverageTimes(Collection $samples): array
    {
        $waitMinutes = [];
        $serviceMinutes = [];

        foreach ($samples as $sample) {
            if ($sample->scheduled_for !== null && $sample->checked_in_at !== null) {
                $waitMinutes[] = max(0, $sample->scheduled_for->diffInMinutes($sample->checked_in_at, false));
            }

            if ($sample->checked_in_at !== null && $sample->checked_out_at !== null) {
                $serviceMinutes[] = max(0, $sample->checked_in_at->diffInMinutes($sample->checked_out_at, false));
            }
        }

        $averageWait = count($waitMinutes) > 0
            ? round(array_sum($waitMinutes) / count($waitMinutes), 1)
            : 0.0;

        $averageService = count($serviceMinutes) > 0
            ? round(array_sum($serviceMinutes) / count($serviceMinutes), 1)
            : 0.0;

        return [$averageWait, $averageService];
    }
}
