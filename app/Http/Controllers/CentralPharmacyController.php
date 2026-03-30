<?php

namespace App\Http\Controllers;

use App\Models\CentralPharmacyRequest;
use App\Services\AuditService;
use App\Services\CitizenEligibilityService;
use App\Services\CitizenIdentityChallengeService;
use App\Services\GovAssaiService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CentralPharmacyController extends Controller
{
    public function __construct(
        private readonly CitizenEligibilityService $eligibility,
        private readonly GovAssaiService $govAssai,
        private readonly CitizenIdentityChallengeService $identityChallenge,
        private readonly AuditService $audit,
    ) {
    }

    public function recepcaoArea(): View
    {
        $requests = CentralPharmacyRequest::with(['citizen', 'reception', 'attendant'])
            ->whereIn('status', ['RECEPCAO_VALIDADA', 'DISPENSADO'])
            ->latest()
            ->limit(60)
            ->get();

        return view('central-pharmacy.recepcao', [
            'requests' => $requests,
            'flow' => session()->get('central_pharmacy.reception_flow'),
        ]);
    }

    public function startReceptionFlow(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'cpf' => ['required', 'string'],
        ]);

        $cpf = $this->govAssai->normalizeCpf($data['cpf']);
        $gov = $this->govAssai->fetchCitizenByCpf($cpf);

        if (! $gov['success'] || ! is_array($gov['data'])) {
            return back()->withErrors(['cpf' => $gov['message']]);
        }

        $challenge = $this->identityChallenge->createChallenge($gov['data'], 'central_pharmacy_reception');

        session()->put('central_pharmacy.reception_flow', [
            'cpf' => $cpf,
            'citizen_name' => (string) data_get($gov['data'], 'cidadao.nome', ''),
            'identity_verified' => false,
            'challenge' => $challenge,
        ]);

        return back()->with('status', 'CPF validado. Confirme a identidade no passo seguinte.');
    }

    public function verifyReceptionIdentity(Request $request): RedirectResponse
    {
        $flow = session()->get('central_pharmacy.reception_flow');

        if (! is_array($flow) || ! isset($flow['cpf'], $flow['challenge']['token'])) {
            return back()->withErrors(['cpf' => 'Inicie novamente o fluxo de CPF.']);
        }

        $data = $request->validate([
            'answer' => ['required', 'string', 'max:50'],
        ]);

        $ok = $this->identityChallenge->verify('central_pharmacy_reception', (string) $flow['challenge']['token'], $data['answer']);

        if (! $ok) {
            return back()->withErrors(['answer' => 'Dado de confirmacao invalido.']);
        }

        $flow['identity_verified'] = true;
        session()->put('central_pharmacy.reception_flow', $flow);
        $this->identityChallenge->markVerified('central_pharmacy_reception', (string) $flow['cpf']);

        return back()->with('status', 'Identidade confirmada. Complete o cadastro no passo final.');
    }

    public function atendimentoArea(): View
    {
        $requests = CentralPharmacyRequest::with(['citizen', 'reception'])
            ->where('status', 'RECEPCAO_VALIDADA')
            ->latest()
            ->limit(60)
            ->get();

        return view('central-pharmacy.atendimento', [
            'requests' => $requests,
        ]);
    }

    public function registerReception(Request $request): RedirectResponse
    {
        $flow = session()->get('central_pharmacy.reception_flow');

        if (! is_array($flow) || ! isset($flow['cpf'])) {
            return back()->withErrors(['cpf' => 'Fluxo de CPF nao iniciado.']);
        }

        if (! $this->identityChallenge->isVerified('central_pharmacy_reception', (string) $flow['cpf'])) {
            return back()->withErrors(['cpf' => 'Confirme a identidade do cidadao antes de finalizar.']);
        }

        $data = $request->validate([
            'prescription_code' => ['nullable', 'string', 'max:100'],
            'medication_name' => ['required', 'string', 'max:255'],
            'quantity' => ['required', 'integer', 'min:1'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $validation = $this->eligibility->validateAndSync((string) $flow['cpf']);

        if (! $validation['eligible'] || ! $validation['citizen']) {
            $this->audit->log($request, 'FARMACIA_CENTRAL', 'RECEPCAO_BLOQUEADA', CentralPharmacyRequest::class, null, [
                'cpf' => $validation['cpf'],
                'error_code' => $validation['error_code'],
                'residence_status' => $validation['residence_status'],
                'gov_assai_level' => $validation['gov_assai_level'],
            ]);

            return back()->withErrors(['cpf' => $validation['message']])->withInput();
        }

        $pharmacyRequest = CentralPharmacyRequest::create([
            'citizen_id' => $validation['citizen']->id,
            'reception_user_id' => (int) $request->user()->id,
            'prescription_code' => $data['prescription_code'] ?? null,
            'medication_name' => $data['medication_name'],
            'quantity' => (int) $data['quantity'],
            'gov_assai_level' => $validation['gov_assai_level'],
            'residence_status' => $validation['residence_status'],
            'status' => 'RECEPCAO_VALIDADA',
            'notes' => $data['notes'] ?? null,
        ]);

        $this->audit->log($request, 'FARMACIA_CENTRAL', 'RECEPCAO_CADASTROU_MEDICACAO', CentralPharmacyRequest::class, null, [
            'request_id' => $pharmacyRequest->id,
            'citizen_id' => $pharmacyRequest->citizen_id,
            'medication_name' => $pharmacyRequest->medication_name,
            'quantity' => $pharmacyRequest->quantity,
        ]);

        $this->identityChallenge->clear('central_pharmacy_reception');
        $this->identityChallenge->consumeVerified('central_pharmacy_reception');
        session()->forget('central_pharmacy.reception_flow');

        return back()->with('status', 'Recepcao cadastrou a solicitacao de medicacao com sucesso.');
    }

    public function dispense(Request $request, CentralPharmacyRequest $centralPharmacyRequest): RedirectResponse
    {
        if ($centralPharmacyRequest->status !== 'RECEPCAO_VALIDADA') {
            return back()->withErrors(['status' => 'Somente solicitacoes validadas na recepcao podem ser dispensadas.']);
        }

        $centralPharmacyRequest->update([
            'attendant_user_id' => (int) $request->user()->id,
            'status' => 'DISPENSADO',
            'dispensed_at' => now(),
        ]);

        $this->audit->log($request, 'FARMACIA_CENTRAL', 'ATENDENTE_DISPENSOU_MEDICACAO', CentralPharmacyRequest::class, null, [
            'request_id' => $centralPharmacyRequest->id,
        ]);

        return back()->with('status', 'Medicacao dispensada e atendimento finalizado.');
    }
}
