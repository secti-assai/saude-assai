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
        $clinicLabel = WomenClinicAppointment::clinicLabel($appointment->clinic_type);
        $clinicSlug = str_replace('_', '-', strtolower(WomenClinicAppointment::resolveClinicType($appointment->clinic_type)));

        $phone = (string) ($appointment->citizen->phone ?? '');
        $formattedDate = $appointment->scheduled_for?->format('d/m/Y H:i');

        if ($formattedDate === null) {
            return;
        }

        $this->notifications->enqueueWhatsapp(
            $phone,
            $clinicLabel.' - Consulta agendada',
            "Sua consulta está confirmada para o dia {$formattedDate}. Fique atento: enviaremos um lembrete 24h antes com um link, caso precise cancelar. Em caso de dúvidas, entre em contato conosco (43) 3262-8400.",
            now(),
            $clinicSlug.'-'.$appointment->id.'-scheduled'
        );
    }

    public function sendReminder24hWithCancelLink(WomenClinicAppointment $appointment): void
    {
        $appointment->loadMissing('citizen');
        $clinicLabel = WomenClinicAppointment::clinicLabel($appointment->clinic_type);
        $clinicSlug = str_replace('_', '-', strtolower(WomenClinicAppointment::resolveClinicType($appointment->clinic_type)));

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
            $clinicLabel.' - Lembrete de consulta',
            "Olá! Sua consulta está confirmada para {$formattedDate}. Caso precise cancelar, acesse este link seguro (válido por {$ttlHours} horas): {$cancelLink}. Para sua segurança, será necessário informar seu CPF e data de nascimento no portal.",
            now(),
            $clinicSlug.'-'.$appointment->id.'-reminder-24h'
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
        $clinicLabel = WomenClinicAppointment::clinicLabel($appointment->clinic_type);
        $clinicSlug = str_replace('_', '-', strtolower(WomenClinicAppointment::resolveClinicType($appointment->clinic_type)));

        $phone = (string) ($appointment->citizen->phone ?? '');
        $formattedDate = $appointment->scheduled_for?->format('d/m/Y H:i');

        $this->notifications->enqueueWhatsapp(
            $phone,
            $clinicLabel.' - Check-in realizado',
            'Seu check-in foi realizado com sucesso'.($formattedDate !== null ? " para a consulta de {$formattedDate}" : '').'. Por favor, aguarde o chamado do profissional.',
            now(),
            $clinicSlug.'-'.$appointment->id.'-checkin-'.($appointment->checked_in_at?->timestamp ?? now()->timestamp)
        );
    }

    public function sendCheckOutAndFeedback(WomenClinicAppointment $appointment): void
    {
        $appointment->loadMissing('citizen');
        $clinicLabel = WomenClinicAppointment::clinicLabel($appointment->clinic_type);
        $clinicSlug = str_replace('_', '-', strtolower(WomenClinicAppointment::resolveClinicType($appointment->clinic_type)));

        $phone = (string) ($appointment->citizen->phone ?? '');
        $feedbackLink = $this->buildFeedbackLink($appointment);

        $this->notifications->enqueueWhatsapp(
            $phone,
            $clinicLabel.' - Atendimento Concluído',
            "Sua consulta foi finalizada. Sua opinião é muito importante para melhorarmos nossos serviços! Poderia avaliar seu atendimento neste link? {$feedbackLink}",
            now(),
            $clinicSlug.'-'.$appointment->id.'-checkout-'.($appointment->checked_out_at?->timestamp ?? now()->timestamp)
        );
    }

    public function sendCancelled(WomenClinicAppointment $appointment): void
    {
        $appointment->loadMissing('citizen');
        $clinicLabel = WomenClinicAppointment::clinicLabel($appointment->clinic_type);
        $clinicSlug = str_replace('_', '-', strtolower(WomenClinicAppointment::resolveClinicType($appointment->clinic_type)));

        $phone = (string) ($appointment->citizen->phone ?? '');
        $formattedDate = $appointment->scheduled_for?->format('d/m/Y H:i') ?? 'N/A';

        $this->notifications->enqueueWhatsapp(
            $phone,
            $clinicLabel.' - Confirmação de Cancelamento',
            "Sua consulta marcada para o dia {$formattedDate} foi cancelada com sucesso. Caso deseje realizar um novo agendamento, entre em contato conosco pelo telefone (43) 3262-8400.",
            now(),
            $clinicSlug.'-'.$appointment->id.'-cancelled-'.now()->timestamp
        );
    }

    public function sendRescheduled(WomenClinicAppointment $appointment): void
    {
        $appointment->loadMissing('citizen');
        $clinicLabel = WomenClinicAppointment::clinicLabel($appointment->clinic_type);
        $clinicSlug = str_replace('_', '-', strtolower(WomenClinicAppointment::resolveClinicType($appointment->clinic_type)));

        $phone = (string) ($appointment->citizen->phone ?? '');
        $formattedDate = $appointment->scheduled_for?->format('d/m/Y H:i');

        if ($formattedDate === null) {
            return;
        }

        $this->notifications->enqueueWhatsapp(
            $phone,
            $clinicLabel.' - Consulta Reagendada',
            "Sua consulta foi atualizada para o dia {$formattedDate}. Fique atento: enviaremos um novo lembrete 24h antes. Em caso de dúvidas, ligue (43) 3262-8400.",
            now(),
            $clinicSlug.'-'.$appointment->id.'-rescheduled-'.now()->timestamp
        );
    }

    public function sendEdited(WomenClinicAppointment $appointment): void
    {
        $appointment->loadMissing('citizen');
        $clinicLabel = WomenClinicAppointment::clinicLabel($appointment->clinic_type);
        $clinicSlug = str_replace('_', '-', strtolower(WomenClinicAppointment::resolveClinicType($appointment->clinic_type)));

        $phone = (string) ($appointment->citizen->phone ?? '');
        $formattedDate = $appointment->scheduled_for?->format('d/m/Y H:i') ?? 'N/A';

        $this->notifications->enqueueWhatsapp(
            $phone,
            $clinicLabel.' - Consulta atualizada',
            "Os detalhes da sua consulta marcada para {$formattedDate} foram atualizados. Por favor, fique atento aos próximos lembretes. Em caso de dúvidas, ligue para (43) 3262-8400.",
            now(),
            $clinicSlug.'-'.$appointment->id.'-edited-'.now()->timestamp
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
