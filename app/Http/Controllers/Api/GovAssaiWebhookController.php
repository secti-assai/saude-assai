<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Jobs\SyncCitizenFromGovAssaiJob;
use App\Models\Citizen;
use App\Services\AuditService;
use App\Services\GovAssaiService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class GovAssaiWebhookController extends Controller
{
    public function __construct(
        private readonly GovAssaiService $govAssai,
        private readonly AuditService $audit,
    ) {
    }

    public function citizenUpdated(Request $request): JsonResponse
    {
        $eventId = (string) ($request->header('X-Event-Id') ?? $request->input('event_id') ?? sha1((string) $request->getContent()));

        if (! Cache::add('govassai:webhook:'.$eventId, true, now()->addHours(24))) {
            return response()->json([
                'success' => true,
                'message' => 'Evento duplicado ignorado.',
            ]);
        }

        $cpf = (string) ($request->input('data.cidadao.cpf') ?? $request->input('cidadao.cpf') ?? '');
        $cpf = $this->govAssai->normalizeCpf($cpf);

        if (! $this->govAssai->isValidCpfFormat($cpf)) {
            return response()->json([
                'success' => false,
                'message' => 'CPF ausente ou invalido no payload.',
                'error_code' => 'INVALID_CPF_FORMAT',
            ], 422);
        }

        $citizen = Citizen::where('cpf_hash', hash('sha256', $cpf))->first();

        if ($citizen) {
            SyncCitizenFromGovAssaiJob::dispatch($citizen->id);
        }

        $this->audit->log(
            $request,
            'INTEGRACAO',
            'GOV_ASSAI_WEBHOOK_CIDADAO_UPDATED',
            Citizen::class,
            $citizen?->id,
            [
                'event_id' => $eventId,
                'cpf' => $cpf,
                'citizen_found' => (bool) $citizen,
            ]
        );

        return response()->json([
            'success' => true,
            'message' => 'Webhook processado com sucesso.',
            'queued' => (bool) $citizen,
        ]);
    }
}
