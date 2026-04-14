<?php

namespace App\Http\Controllers;

use App\Services\AuditService;
use App\Services\PharmacyReportService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CentralPharmacyReportController extends Controller
{
    public function __construct(
        private readonly PharmacyReportService $reportService,
        private readonly AuditService $audit,
    )
    {
    }

    public function index(Request $request): View
    {
        $validated = $this->validateFilters($request);

        $payload = $this->reportService->build($validated);

        return view('central-pharmacy.reports', $payload);
    }

    public function exportCsv(Request $request): StreamedResponse
    {
        $validated = $this->validateFilters($request);
        $export = $this->reportService->buildCsvExport($validated);

        $this->audit->log(
            $request,
            'FARMACIA_CENTRAL',
            'RELATORIO_CSV_EXPORTADO',
            null,
            null,
            ['filters' => $export['filters']]
        );

        return response()->streamDownload(function () use ($export): void {
            $output = fopen('php://output', 'w');

            if (! is_resource($output)) {
                return;
            }

            fwrite($output, "\xEF\xBB\xBF");
            fputcsv($output, $export['headers'], ';', '"', '', "\r\n");

            foreach ($export['rows'] as $row) {
                fputcsv($output, $row, ';', '"', '', "\r\n");
            }

            fclose($output);
        }, $export['filename'], [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    private function validateFilters(Request $request): array
    {
        return $request->validate([
            'date_start' => ['nullable', 'date'],
            'date_end' => ['nullable', 'date'],
            'status' => ['nullable', 'string', Rule::in([
                'DISPENSADOS',
                'TODOS',
                'RECEPCAO_VALIDADA',
                'DISPENSADO',
                'DISPENSADO_EQUIVALENTE',
                'NAO_DISPENSADO',
            ])],
            'dispense_category' => ['nullable', 'string', Rule::in(['ALL', 'MEDICACAO', 'LEITE', 'SUPLEMENTO'])],
            'gov_level' => ['nullable', 'string', 'max:10'],
            'needs_validation' => ['nullable', 'string', Rule::in(['all', 'yes', 'no'])],
            'citizen_name' => ['nullable', 'string', 'max:255'],
        ]);
    }
}
