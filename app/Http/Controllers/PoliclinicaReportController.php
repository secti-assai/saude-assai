<?php

namespace App\Http\Controllers;

use App\Models\WomenClinicAppointment;
use App\Services\WomenClinicReportService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class PoliclinicaReportController extends Controller
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

        $payload = $this->reportService->buildForClinic($validated, WomenClinicAppointment::CLINIC_POLICLINICA);

        return view('policlinica.reports', $payload);
    }
}
