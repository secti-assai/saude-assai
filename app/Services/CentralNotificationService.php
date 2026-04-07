<?php

namespace App\Services;

use DateTimeInterface;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;
use Throwable;

class CentralNotificationService
{
    /**
     * @return array{success:bool,status:int,message:string,payload:?array}
     */
    public function health(): array
    {
        $baseUrl = $this->baseUrl();

        try {
            $response = Http::acceptJson()
                ->timeout($this->timeout())
                ->connectTimeout($this->connectTimeout())
                ->get($baseUrl.'/health');
        } catch (Throwable) {
            return [
                'success' => false,
                'status' => 503,
                'message' => 'Central de notificacoes indisponivel no momento.',
                'payload' => null,
            ];
        }

        $payload = $response->json();
        if (! is_array($payload)) {
            return [
                'success' => false,
                'status' => $response->status(),
                'message' => 'Central de notificacoes retornou payload invalido em /health.',
                'payload' => null,
            ];
        }

        return [
            'success' => $response->successful(),
            'status' => $response->status(),
            'message' => (string) Arr::get($payload, 'status', 'unknown'),
            'payload' => $payload,
        ];
    }

    public function isConfigured(): bool
    {
        return $this->apiKey() !== '';
    }

    /**
     * @return array{success:bool,status:int,message:string,notification_id:?int,payload:?array}
     */
    public function enqueueWhatsapp(
        string $to,
        string $subject,
        string $body,
        DateTimeInterface|int|string|null $scheduleAt = null,
        ?string $idempotencyKey = null,
    ): array {
        return $this->enqueue([
            'type' => 'whatsapp',
            'to' => $to,
            'subject' => $subject,
            'body' => $body,
            'schedule_at' => $scheduleAt,
        ], $idempotencyKey);
    }

    /**
     * @param  array{type:string,to:string,subject:string,body:string,schedule_at:DateTimeInterface|int|string|null}  $payload
     * @return array{success:bool,status:int,message:string,notification_id:?int,payload:?array}
     */
    public function enqueue(array $payload, ?string $idempotencyKey = null): array
    {
        if (! $this->isConfigured()) {
            return [
                'success' => false,
                'status' => 503,
                'message' => 'Central de notificacoes nao configurada (NOTIFICATIONS_API_KEY ausente).',
                'notification_id' => null,
                'payload' => null,
            ];
        }

        $type = strtolower(trim((string) ($payload['type'] ?? '')));
        if (! in_array($type, ['whatsapp', 'email'], true)) {
            return [
                'success' => false,
                'status' => 400,
                'message' => 'Tipo de notificacao invalido. Use email ou whatsapp.',
                'notification_id' => null,
                'payload' => null,
            ];
        }

        $recipient = $this->normalizeRecipient($type, (string) ($payload['to'] ?? ''));
        if ($recipient === null) {
            return [
                'success' => false,
                'status' => 400,
                'message' => 'Destinatario invalido para o tipo de notificacao informado.',
                'notification_id' => null,
                'payload' => null,
            ];
        }

        $subject = trim((string) ($payload['subject'] ?? ''));
        $body = trim((string) ($payload['body'] ?? ''));

        if ($subject === '' || $body === '') {
            return [
                'success' => false,
                'status' => 400,
                'message' => 'Subject e body sao obrigatorios para envio de notificacao.',
                'notification_id' => null,
                'payload' => null,
            ];
        }

        $normalizedSchedule = $this->normalizeScheduleAt($payload['schedule_at'] ?? null);
        if ($normalizedSchedule === null) {
            return [
                'success' => false,
                'status' => 400,
                'message' => 'schedule_at invalido para notificacao.',
                'notification_id' => null,
                'payload' => null,
            ];
        }

        $requestPayload = [
            'type' => $type,
            'to' => $recipient,
            'subject' => $subject,
            'body' => $body,
            'schedule_at' => $normalizedSchedule,
        ];

        $headers = [
            'x-api-key' => $this->apiKey(),
            'Accept' => 'application/json',
        ];

        if ($idempotencyKey !== null && trim($idempotencyKey) !== '') {
            $headers['Idempotency-Key'] = trim($idempotencyKey);
        }

        try {
            $response = Http::asJson()
                ->timeout($this->timeout())
                ->connectTimeout($this->connectTimeout())
                ->withHeaders($headers)
                ->post($this->baseUrl().'/notify', $requestPayload);
        } catch (Throwable) {
            return [
                'success' => false,
                'status' => 503,
                'message' => 'Falha de conexao com a Central de notificacoes.',
                'notification_id' => null,
                'payload' => null,
            ];
        }

        $responsePayload = $response->json();
        if (! is_array($responsePayload)) {
            return [
                'success' => false,
                'status' => $response->status(),
                'message' => 'Central de notificacoes retornou payload nao JSON.',
                'notification_id' => null,
                'payload' => null,
            ];
        }

        $notificationId = Arr::get($responsePayload, 'notification.id');

        return [
            'success' => in_array($response->status(), [200, 202], true),
            'status' => $response->status(),
            'message' => (string) Arr::get($responsePayload, 'message', 'Resposta recebida da central de notificacoes.'),
            'notification_id' => is_numeric($notificationId) ? (int) $notificationId : null,
            'payload' => $responsePayload,
        ];
    }

    private function normalizeRecipient(string $type, string $to): ?string
    {
        if ($type === 'email') {
            $normalized = trim($to);

            return filter_var($normalized, FILTER_VALIDATE_EMAIL) ? $normalized : null;
        }

        $digits = preg_replace('/\D+/', '', $to) ?? '';

        if ($digits === '') {
            return null;
        }

        if (strlen($digits) === 10 || strlen($digits) === 11) {
            $digits = '55'.$digits;
        }

        if (! str_starts_with($digits, '55')) {
            return null;
        }

        if (! in_array(strlen($digits), [12, 13], true)) {
            return null;
        }

        return $digits;
    }

    private function normalizeScheduleAt(DateTimeInterface|int|string|null $value): ?string
    {
        if ($value === null) {
            return now()->toIso8601String();
        }

        if ($value instanceof DateTimeInterface) {
            return $value->format(DateTimeInterface::ATOM);
        }

        if (is_int($value) || (is_string($value) && preg_match('/^\d+$/', trim($value)))) {
            $timestamp = (int) $value;

            // Supports unix timestamp in seconds or milliseconds.
            if ($timestamp > 9999999999) {
                $timestamp = (int) floor($timestamp / 1000);
            }

            return date(DATE_ATOM, $timestamp);
        }

        if (is_string($value)) {
            $parsed = strtotime($value);

            if ($parsed !== false) {
                return date(DATE_ATOM, $parsed);
            }
        }

        return null;
    }

    private function apiKey(): string
    {
        return trim((string) config('services.notifications.api_key', ''));
    }

    private function baseUrl(): string
    {
        return rtrim((string) config('services.notifications.base_url', 'http://notificacoes.assai.pr.gov.br'), '/');
    }

    private function timeout(): int
    {
        return (int) config('services.notifications.timeout', 10);
    }

    private function connectTimeout(): int
    {
        return (int) config('services.notifications.connect_timeout', 5);
    }
}
