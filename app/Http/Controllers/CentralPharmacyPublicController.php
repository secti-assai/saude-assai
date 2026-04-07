<?php

namespace App\Http\Controllers;

use App\Models\CentralPharmacyRequest;
use App\Models\Citizen;
use App\Services\AuditService;
use App\Services\GovAssaiService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CentralPharmacyPublicController extends Controller
{
    public function __construct(
        private readonly GovAssaiService $govAssai,
        private readonly AuditService $audit,
    ) {
    }

    public function feedback(Request $request, CentralPharmacyRequest $centralPharmacyRequest): View|RedirectResponse
    {
        $centralPharmacyRequest->loadMissing('citizen');

        if ($request->isMethod('get')) {
            return view('central-pharmacy.public-feedback', [
                'pharmacyRequest' => $centralPharmacyRequest,
            ]);
        }

        $data = $request->validate([
            'cpf' => ['required', 'string'],
            'birth_date' => ['required', 'date'],
            'feedback_score' => ['required', 'integer', 'min:1', 'max:5'],
            'feedback_comment' => ['nullable', 'string', 'max:1000'],
        ]);

        if (! in_array((string) $centralPharmacyRequest->status, ['DISPENSADO', 'DISPENSADO_EQUIVALENTE'], true)) {
            return back()->withErrors(['status' => 'Somente atendimentos dispensados podem ser avaliados.'])->withInput();
        }

        if ($centralPharmacyRequest->feedback_submitted_at !== null) {
            return back()->withErrors(['status' => 'Avaliacao ja registrada para este atendimento.']);
        }

        $citizen = $centralPharmacyRequest->citizen;
        if (! $citizen || ! $this->matchesCitizenIdentity($citizen, (string) $data['cpf'], (string) $data['birth_date'])) {
            return back()->withErrors(['cpf' => 'Nao foi possivel validar CPF e data de nascimento para esta avaliacao.'])->withInput();
        }

        $centralPharmacyRequest->update([
            'feedback_score' => (int) $data['feedback_score'],
            'feedback_comment' => trim((string) ($data['feedback_comment'] ?? '')) ?: null,
            'feedback_submitted_at' => now(),
        ]);

        $this->audit->log($request, 'FARMACIA_CENTRAL', 'AVALIACAO_LINK_CIDADAO', CentralPharmacyRequest::class, null, [
            'pharmacy_request_id' => $centralPharmacyRequest->id,
            'citizen_id' => $centralPharmacyRequest->citizen_id,
            'feedback_score' => (int) $data['feedback_score'],
        ]);

        return back()->with('status', 'Avaliacao registrada com sucesso. Obrigado pelo retorno.');
    }

    private function matchesCitizenIdentity(Citizen $citizen, string $cpf, string $birthDate): bool
    {
        $inputCpf = $this->govAssai->normalizeCpf($cpf);
        $citizenCpf = $this->govAssai->normalizeCpf((string) $citizen->cpf);
        $citizenBirth = $citizen->birth_date?->format('Y-m-d');

        return $inputCpf !== ''
            && $citizenCpf !== ''
            && hash_equals($citizenCpf, $inputCpf)
            && $citizenBirth === $birthDate;
    }
}
