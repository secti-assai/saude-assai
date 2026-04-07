<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\PharmacyDispensationService;
use App\Models\CentralPharmacyRequest;
use Illuminate\Validation\Rule;

class CentralPharmacyUnifiedController extends Controller
{
    public function __construct(private readonly PharmacyDispensationService $pharmacy)
    {
    }

    public function index(Request $request)
    {
        $cpf = session('pharmacy_unified_cpf');
        $info = null;

        if ($cpf) {
            $info = $this->pharmacy->getCitizenInfo($cpf);
        }

        $requests = CentralPharmacyRequest::with(['citizen'])
            ->where('status', 'DISPENSADO')
            ->latest()
            ->limit(20)
            ->get();

        return view('central-pharmacy.unified', [
            'info' => $info,
            'requests' => $requests,
        ]);
    }

    public function search(Request $request)
    {
        $data = $request->validate([
            'cpf' => ['required', 'string'],
        ]);

        return redirect()->route('central-pharmacy.unified')->with('pharmacy_unified_cpf', $data['cpf']);
    }

    public function dispense(Request $request)
    {
        $data = $request->validate([
            'cpf' => ['required', 'string'],
            'full_name' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'regex:/^\(\d{2}\)\s\d{4,5}-\d{4}$/'],
            'dispense_category' => ['required', 'string', Rule::in(['MEDICACAO', 'LEITE', 'SUPLEMENTO'])],
            'quantity' => ['nullable', 'integer', 'min:1'],
        ]);

        $result = $this->pharmacy->processDispensation($data, $request->user()->id, $request);

        if (!$result['success']) {
            return redirect()->route('central-pharmacy.unified')
                ->withErrors(['error' => $result['message']]);
        }

        return redirect()->route('central-pharmacy.unified')
            ->with('status', $result['message']);
    }

    public function noDispenseBlocked(Request $request)
    {
        $data = $request->validate([
            'cpf' => ['required', 'string'],
            'dispense_category' => ['required', 'string', Rule::in(['MEDICACAO', 'LEITE', 'SUPLEMENTO'])],
        ]);

        $result = $this->pharmacy->registerBlockedNoDispense($data, $request->user()->id, $request);

        if (! $result['success']) {
            return redirect()->route('central-pharmacy.unified')
                ->withErrors(['error' => $result['message']]);
        }

        return redirect()->route('central-pharmacy.unified')
            ->with('status', $result['message']);
    }
}
