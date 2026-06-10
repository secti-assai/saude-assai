<?php

namespace App\Http\Controllers;

use App\Models\WomenClinicAppointment;
use App\Services\WomenClinicReportService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Response;
use App\Services\AuditService;

class WomenClinicReportController extends Controller
{
    public function __construct(private readonly WomenClinicReportService $reportService)
    {
    }

    public function index(Request $request): View
    {
        $validated = $request->validate([
            'date_start' => ['nullable', 'date'],
            'date_end' => ['nullable', 'date'],
            'status' => ['nullable', 'string', Rule::in(['TODOS', 'AGENDADO', 'CHECKIN', 'FINALIZADO', 'CANCELADO'])],
            'has_feedback' => ['nullable', 'string', Rule::in(['all', 'yes', 'no'])],
            'citizen_name' => ['nullable', 'string', 'max:255'],
        ]);

        $payload = $this->reportService->buildForClinic($validated, WomenClinicAppointment::CLINIC_WOMEN);

        return view('women-clinic.reports', $payload);
    }

    public function exportCsv(Request $request, AuditService $audit): StreamedResponse
    {
        $validated = $request->validate([
            'date_start' => ['nullable', 'date'],
            'date_end' => ['nullable', 'date'],
            'status' => ['nullable', 'string', Rule::in(['TODOS', 'AGENDADO', 'CHECKIN', 'FINALIZADO', 'CANCELADO'])],
            'has_feedback' => ['nullable', 'string', Rule::in(['all', 'yes', 'no'])],
            'citizen_name' => ['nullable', 'string', 'max:255'],
        ]);

        $export = $this->reportService->buildCsvExport($validated, WomenClinicAppointment::CLINIC_WOMEN);

        $audit->log(
            $request,
            'CLINICA_MULHER',
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

    public function exportPdf(Request $request, AuditService $audit): Response
    {
        $validated = $request->validate([
            'date_start' => ['nullable', 'date'],
            'date_end' => ['nullable', 'date'],
            'status' => ['nullable', 'string', Rule::in(['TODOS', 'AGENDADO', 'CHECKIN', 'FINALIZADO', 'CANCELADO'])],
            'has_feedback' => ['nullable', 'string', Rule::in(['all', 'yes', 'no'])],
            'citizen_name' => ['nullable', 'string', 'max:255'],
        ]);

        $export = $this->reportService->buildPdfExport($validated, WomenClinicAppointment::CLINIC_WOMEN);

        $audit->log(
            $request,
            'CLINICA_MULHER',
            'RELATORIO_PDF_EXPORTADO',
            null,
            null,
            ['filters' => $export['filters']]
        );

        $pdf = Pdf::loadView('reports.pdf.clinic', $export['data'])
            ->setPaper('a4', 'landscape');

        return $pdf->download($export['filename']);
    }
}
