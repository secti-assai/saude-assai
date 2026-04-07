<?php

namespace App\Services;

use App\Models\WomenClinicAppointment;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;

class WomenClinicNotificationService
{
    public function __construct(private readonly CentralNotificationService $notifications)
    {
    }

    public function sendScheduled(WomenClinicAppointment $appointment): void
    {
        $appointment->loadMissing('citizen');

        $phone = (string) ($appointment->citizen->phone ?? '');
        $formattedDate = $appointment->scheduled_for?->format('d/m/Y H:i');

        if ($formattedDate === null) {
            return;
        }

        $this->notifications->enqueueWhatsapp(
            $phone,
            'Clinica da Mulher - Consulta agendada',
            "Sua consulta foi agendada para {$formattedDate}. Voce recebera outro lembrete 24 horas antes com um link seguro para cancelamento.",
            now(),
            'women-clinic-'.$appointment->id.'-scheduled'
        );
    }

    public function sendReminder24hWithCancelLink(WomenClinicAppointment $appointment): void
    {
        $appointment->loadMissing('citizen');

        if ($appointment->status !== 'AGENDADO') {
            return;
        }

        if ($appointment->reminder_24h_sent_at !== null) {
            return;
        }

        $phone = (string) ($appointment->citizen->phone ?? '');
        $formattedDate = $appointment->scheduled_for?->format('d/m/Y H:i');

        if ($formattedDate === null) {
            return;
        }

        $cancelLink = $this->buildCancelLink($appointment);
        $ttlHours = $this->cancelLinkTtlHours();

        $result = $this->notifications->enqueueWhatsapp(
            $phone,
            'Clinica da Mulher - Lembrete de consulta',
            "Lembrete: sua consulta esta marcada para {$formattedDate}. Caso precise cancelar, use este link seguro (valido por {$ttlHours} horas): {$cancelLink}. Na pagina, informe CPF e data de nascimento para confirmar o cancelamento.",
            now(),
            'women-clinic-'.$appointment->id.'-reminder-24h'
        );

        if ($result['success']) {
            $appointment->update([
                'reminder_24h_sent_at' => now(),
            ]);
        }
    }

    public function sendCheckIn(WomenClinicAppointment $appointment): void
    {
        $appointment->loadMissing('citizen');

        $phone = (string) ($appointment->citizen->phone ?? '');
        $formattedDate = $appointment->scheduled_for?->format('d/m/Y H:i');

        $this->notifications->enqueueWhatsapp(
            $phone,
            'Clinica da Mulher - Check-in realizado',
            'Seu check-in foi realizado com sucesso'.($formattedDate !== null ? " para a consulta de {$formattedDate}" : '').'. Aguarde o atendimento medico.',
            now(),
            'women-clinic-'.$appointment->id.'-checkin-'.($appointment->checked_in_at?->timestamp ?? now()->timestamp)
        );
    }

    public function sendCheckOutAndFeedback(WomenClinicAppointment $appointment): void
    {
        $appointment->loadMissing('citizen');

        $phone = (string) ($appointment->citizen->phone ?? '');
        $feedbackLink = $this->buildFeedbackLink($appointment);

        $this->notifications->enqueueWhatsapp(
            $phone,
            'Clinica da Mulher - Consulta finalizada',
            "Sua consulta foi finalizada. Para avaliar o atendimento, acesse este link: {$feedbackLink}",
            now(),
            'women-clinic-'.$appointment->id.'-checkout-'.($appointment->checked_out_at?->timestamp ?? now()->timestamp)
        );
    }

    private function buildCancelLink(WomenClinicAppointment $appointment): string
    {
        $signedPath = URL::temporarySignedRoute(
            'women-clinic.public.cancel',
            now()->addHours($this->cancelLinkTtlHours()),
            [
                'womenClinicAppointment' => $appointment->id,
                'nonce' => (string) Str::uuid(),
            ],
            absolute: false
        );

        return $this->publicBaseUrl().$signedPath;
    }

    private function buildFeedbackLink(WomenClinicAppointment $appointment): string
    {
        $signedPath = URL::temporarySignedRoute(
            'women-clinic.public.feedback',
            now()->addHours($this->feedbackLinkTtlHours()),
            [
                'womenClinicAppointment' => $appointment->id,
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

    private function cancelLinkTtlHours(): int
    {
        return max(1, (int) config('services.notifications.cancel_link_ttl_hours', 6));
    }

    private function feedbackLinkTtlHours(): int
    {
        return max(1, (int) config('services.notifications.feedback_link_ttl_hours', 168));
    }
}
