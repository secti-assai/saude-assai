<?php

namespace App\Services;

use App\Models\CentralPharmacyRequest;
use App\Models\Citizen;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;

class PharmacyNotificationService
{
    public function __construct(private readonly CentralNotificationService $notifications)
    {
    }

    public function sendRegularizationGuidance(Citizen $citizen, int $level, string $eventKey): void
    {
        $phone = (string) ($citizen->phone ?? '');

        $message = "Seu nivel atual no Gov.Assai e {$level}. Para continuar retirando medicamentos nas proximas vezes, regularize para nivel 2. "
            ."\n\n1) Cadastro Gov.Assai - Nivel 1: acesse https://gov.assai.pr.gov.br e clique em Cadastrar conta Gov.Assai. "
            ."\n\n2) Elevacao para nivel 2: compareca na Secretaria de Ciencia, Tecnologia e Inovacao - Agencia de Inovacao do Vale do Sol (Rua Edgar Bardal, s/n, anexo ao CEEP, Assai/PR). "
            ."\n\nDocumentos maiores de 18: CPF, CNH (se houver), comprovante de residencia ate 90 dias, certidao de casamento/uniao estavel (quando aplicavel), comprovante CadUnico/NIS (se houver), CNS. "
            ."\n\nDocumentos menores: responsavel legal nivel 2, CPF do menor, certidao de nascimento ou guarda/tutela/curatela, cartao de vacinacao (obrigatorio ate 12 anos), comprovante de escolaridade e NIS (se houver).";

        $this->notifications->enqueueWhatsapp(
            $phone,
            'Farmacia Central - Regularizacao Gov.Assai',
            $message,
            now(),
            'pharmacy-regularization-'.$eventKey
        );
    }

    public function sendDispenseFeedback(CentralPharmacyRequest $pharmacyRequest): void
    {
        $pharmacyRequest->loadMissing('citizen');

        if (! in_array((string) $pharmacyRequest->status, ['DISPENSADO', 'DISPENSADO_EQUIVALENTE'], true)) {
            return;
        }

        $phone = (string) ($pharmacyRequest->citizen->phone ?? '');
        $category = $this->categoryLabel((string) $pharmacyRequest->medication_name);
        $feedbackLink = $this->buildFeedbackLink($pharmacyRequest);

        $this->notifications->enqueueWhatsapp(
            $phone,
            'Farmacia Central - Avaliacao de atendimento',
            "Sua dispensacao de {$category} foi registrada. Avalie o atendimento da Farmacia Central neste link: {$feedbackLink}",
            now(),
            'pharmacy-feedback-'.$pharmacyRequest->id.'-'.(string) Str::lower($pharmacyRequest->status)
        );
    }

    private function buildFeedbackLink(CentralPharmacyRequest $pharmacyRequest): string
    {
        $ttlHours = max(1, (int) config('services.notifications.feedback_link_ttl_hours', 168));

        $signedPath = URL::temporarySignedRoute(
            'central-pharmacy.public.feedback',
            now()->addHours($ttlHours),
            [
                'centralPharmacyRequest' => $pharmacyRequest->id,
                'nonce' => (string) Str::uuid(),
            ],
            absolute: false
        );

        return $this->publicBaseUrl().$signedPath;
    }

    private function publicBaseUrl(): string
    {
        return rtrim((string) config('services.notifications.public_base_url', config('app.url', 'http://localhost')), '/');
    }

    private function categoryLabel(string $raw): string
    {
        return match (strtoupper(trim($raw))) {
            'LEITE' => 'LEITE',
            'SUPLEMENTO' => 'SUPLEMENTO',
            default => 'MEDICACAO',
        };
    }
}
