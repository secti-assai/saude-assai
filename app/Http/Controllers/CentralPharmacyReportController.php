<?php

namespace App\Http\Controllers;

use App\Services\AuditService;
use App\Services\PharmacyReportService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Response;

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

    public function exportPdf(Request $request): Response
    {
        $validated = $this->validateFilters($request);
        $export = $this->reportService->buildPdfExport($validated);

        $this->audit->log(
            $request,
            'FARMACIA_CENTRAL',
            'RELATORIO_PDF_EXPORTADO',
            null,
            null,
            ['filters' => $export['filters']]
        );

        // Aumenta os limites temporariamente para suportar milhares de linhas no DomPDF
        ini_set('max_execution_time', '600');
        ini_set('memory_limit', '2048M');

        // Limite máximo de registros no PDF para evitar estouro de RAM do DomPDF (Fatal Error 2GB)
        $maxPdfRows = 1000;
        $totalRows = count($export['data']['rows']);
        if ($totalRows > $maxPdfRows) {
            $export['data']['rows'] = array_slice($export['data']['rows'], 0, $maxPdfRows);
            $export['data']['limit_reached'] = true;
            $export['data']['total_rows_omitted'] = $totalRows - $maxPdfRows;
        } else {
            $export['data']['limit_reached'] = false;
        }

        $pdf = Pdf::loadView('reports.pdf.pharmacy', $export['data'])
            ->setPaper('a4', 'landscape');

        return $pdf->download($export['filename']);
    }

    private function validateFilters(Request $request): array
    {
        return $request->validate([
            'date_start' => ['nullable', 'date'],
            'date_end' => ['nullable', 'date'],
            'status' => ['nullable', 'string', Rule::in([
                'TODOS',
                'REGULARIZADO',
                'NAO_REGULARIZADO',
            ])],
            'citizen_name' => ['nullable', 'string', 'max:255'],
        ]);
    }
}
