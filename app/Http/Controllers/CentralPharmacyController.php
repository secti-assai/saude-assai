<?php

namespace App\Http\Controllers;

use App\Models\CentralPharmacyRequest;
use App\Services\AuditService;
use App\Services\CitizenEligibilityService;
use App\Services\CitizenIdentityChallengeService;
use App\Services\GovAssaiService;
use Illuminate\Http\JsonResponse;
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

    public function recepcaoArea(Request $request): View
    {
        $today = now()->toDateString();

        $dateStart = trim((string) $request->query('date_start', $today));
        $dateEnd = trim((string) $request->query('date_end', $today));

        if (! preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateStart)) {
            $dateStart = $today;
        }

        if (! preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateEnd)) {
            $dateEnd = $today;
        }

        if ($dateStart > $dateEnd) {
            [$dateStart, $dateEnd] = [$dateEnd, $dateStart];
        }

        $requests = CentralPharmacyRequest::with(['citizen', 'reception', 'attendant'])
            ->whereIn('status', ['RECEPCAO_VALIDADA', 'DISPENSADO', 'NAO_DISPENSADO', 'DISPENSADO_EQUIVALENTE'])
            ->whereDate('prescription_date', '>=', $dateStart)
            ->whereDate('prescription_date', '<=', $dateEnd)
            ->latest()
            ->limit(60)
            ->get();

        return view('central-pharmacy.recepcao', [
            'requests' => $requests,
            'flow' => session()->get('central_pharmacy.reception_flow'),
            'filters' => [
                'date_start' => $dateStart,
                'date_end' => $dateEnd,
            ],
        ]);
    }

    public function startReceptionFlow(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'cpf' => ['required', 'string'],
        ]);

        $cpf = $this->govAssai->normalizeCpf($data['cpf']);
        $validation = $this->eligibility->validateAndSync($cpf);

        if (! $validation['eligible']) {
            return back()->withErrors(['cpf' => $validation['message']])->withInput();
        }

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
    public function cancelReceptionFlow(): RedirectResponse
    {
        $this->identityChallenge->clear('central_pharmacy_reception');
        session()->forget('central_pharmacy.reception_flow');

        return redirect()->route('central-pharmacy.recepcao')->with('status', 'Solicitacao cancelada. Voce pode iniciar a consulta de um novo cidadao.');
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

    public function atendimentoData(): JsonResponse
    {
        $requests = CentralPharmacyRequest::with(['citizen'])
            ->where('status', 'RECEPCAO_VALIDADA')
            ->latest()
            ->limit(100)
            ->get();

        return response()->json([
            'rows' => $requests->map(fn (CentralPharmacyRequest $row): array => [
                'id' => (string) $row->id,
                'prescription_date' => $row->prescription_date?->format('d/m/Y') ?? '—',
                'prescriber_name' => (string) ($row->prescriber_name ?? '—'),
                'citizen_name' => (string) ($row->citizen->full_name ?? '—'),
                'medication_name' => (string) $row->medication_name,
                'concentration' => (string) ($row->concentration ?? '—'),
                'quantity' => (int) $row->quantity,
                'dosage' => (string) ($row->dosage ?? '—'),
                'dispense_url' => route('central-pharmacy.dispense', $row),
                'refuse_url' => route('central-pharmacy.refuse', $row),
                'dispense_equivalent_url' => route('central-pharmacy.dispense-equivalent', $row),
            ])->values(),
            'generated_at' => now()->toIso8601String(),
        ]);
    }

    public function registerReception(Request $request): RedirectResponse
    {
        $flow = session()->get('central_pharmacy.reception_flow');
        $fallbackCpf = $this->govAssai->normalizeCpf((string) $request->input('flow_cpf', ''));

        if ((! is_array($flow) || ! isset($flow['cpf'])) && $this->govAssai->isValidCpfFormat($fallbackCpf)) {
            $validation = $this->eligibility->validateAndSync($fallbackCpf);

            if (! $validation['eligible']) {
                return back()->withErrors(['cpf' => $validation['message']])->withInput();
            }
        }

        if (! is_array($flow) || ! isset($flow['cpf'])) {
            return back()->withErrors(['cpf' => 'Sessao da recepcao expirada. Revalide o CPF e confirme a identidade para concluir o cadastro.'])->withInput();
        }

        if (! $this->identityChallenge->isVerified('central_pharmacy_reception', (string) $flow['cpf'])) {
            return back()->withErrors(['cpf' => 'Confirmacao de identidade expirada. Revalide o CPF e confirme novamente para finalizar.'])->withInput();
        }

        $data = $request->validate([
            'prescription_code' => ['nullable', 'string', 'max:100'],
            'prescription_date' => ['required', 'date'],
            'prescriber_name' => ['required', 'string', 'max:255'],
            'medication_name' => ['required', 'string', 'max:255'],
            'concentration' => ['required', 'string', 'max:100'],
            'quantity' => ['required', 'integer', 'min:1'],
            'dosage' => ['required', 'string', 'max:1000'],
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
            'prescription_date' => $data['prescription_date'],
            'prescriber_name' => $data['prescriber_name'],
            'medication_name' => $data['medication_name'],
            'concentration' => $data['concentration'],
            'quantity' => (int) $data['quantity'],
            'dosage' => $data['dosage'],
            'gov_assai_level' => $validation['gov_assai_level'],
            'residence_status' => $validation['residence_status'],
            'status' => 'RECEPCAO_VALIDADA',
            'notes' => $data['notes'] ?? null,
        ]);

        $this->audit->log($request, 'FARMACIA_CENTRAL', 'RECEPCAO_CADASTROU_MEDICACAO', CentralPharmacyRequest::class, null, [
            'request_id' => $pharmacyRequest->id,
            'citizen_id' => $pharmacyRequest->citizen_id,
            'prescription_date' => $pharmacyRequest->prescription_date?->toDateString(),
            'prescriber_name' => $pharmacyRequest->prescriber_name,
            'medication_name' => $pharmacyRequest->medication_name,
            'concentration' => $pharmacyRequest->concentration,
            'quantity' => $pharmacyRequest->quantity,
            'dosage' => $pharmacyRequest->dosage,
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
            'refusal_reason' => null,
            'equivalent_medication_name' => null,
            'equivalent_concentration' => null,
            'dispensed_at' => now(),
        ]);

        $this->audit->log($request, 'FARMACIA_CENTRAL', 'ATENDENTE_DISPENSOU_MEDICACAO', CentralPharmacyRequest::class, null, [
            'request_id' => $centralPharmacyRequest->id,
        ]);

        return back()->with('status', 'Medicacao dispensada e atendimento finalizado.');
    }

    public function refuse(Request $request, CentralPharmacyRequest $centralPharmacyRequest): RedirectResponse
    {
        if ($centralPharmacyRequest->status !== 'RECEPCAO_VALIDADA') {
            return back()->withErrors(['status' => 'Somente solicitacoes validadas na recepcao podem receber recusa motivada.']);
        }

        $data = $request->validate([
            'refusal_reason' => ['required', 'string', 'max:1000'],
        ]);

        $centralPharmacyRequest->update([
            'attendant_user_id' => (int) $request->user()->id,
            'status' => 'NAO_DISPENSADO',
            'refusal_reason' => $data['refusal_reason'],
            'equivalent_medication_name' => null,
            'equivalent_concentration' => null,
            'dispensed_at' => null,
        ]);

        $this->audit->log($request, 'FARMACIA_CENTRAL', 'ATENDENTE_RECUSOU_DISPENSACAO', CentralPharmacyRequest::class, null, [
            'request_id' => $centralPharmacyRequest->id,
            'refusal_reason' => $centralPharmacyRequest->refusal_reason,
        ]);

        return back()->with('status', 'Dispensacao recusada com motivo registrado.');
    }

    public function dispenseEquivalent(Request $request, CentralPharmacyRequest $centralPharmacyRequest): RedirectResponse
    {
        if ($centralPharmacyRequest->status !== 'RECEPCAO_VALIDADA') {
            return back()->withErrors(['status' => 'Somente solicitacoes validadas na recepcao podem receber intercambialidade.']);
        }

        $data = $request->validate([
            'equivalent_medication_name' => ['required', 'string', 'max:255'],
            'equivalent_concentration' => ['required', 'string', 'max:100'],
        ]);

        $centralPharmacyRequest->update([
            'attendant_user_id' => (int) $request->user()->id,
            'status' => 'DISPENSADO_EQUIVALENTE',
            'refusal_reason' => null,
            'equivalent_medication_name' => $data['equivalent_medication_name'],
            'equivalent_concentration' => $data['equivalent_concentration'],
            'dispensed_at' => now(),
        ]);

        $this->audit->log($request, 'FARMACIA_CENTRAL', 'ATENDENTE_DISPENSOU_EQUIVALENTE', CentralPharmacyRequest::class, null, [
            'request_id' => $centralPharmacyRequest->id,
            'equivalent_medication_name' => $centralPharmacyRequest->equivalent_medication_name,
            'equivalent_concentration' => $centralPharmacyRequest->equivalent_concentration,
        ]);

        return back()->with('status', 'Dispensacao equivalente registrada com sucesso.');
    }
}
