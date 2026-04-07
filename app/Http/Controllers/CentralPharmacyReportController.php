<?php

namespace App\Http\Controllers;

use App\Services\PharmacyReportService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class CentralPharmacyReportController extends Controller
{
    public function __construct(private readonly PharmacyReportService $reportService)
    {
    }

    public function index(Request $request): View
    {
        $validated = $request->validate([
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

        $payload = $this->reportService->build($validated);

        return view('central-pharmacy.reports', $payload);
    }
}
