<?php

namespace App\Services;

use Illuminate\Support\Arr;
use Illuminate\Support\Str;

class CitizenIdentityChallengeService
{
    /**
     * @return array{token:string,prompt:string,mask_hint:string,context:string}
     */
    public function createChallenge(array $govData, string $context): array
    {
        $birthDate = (string) Arr::get($govData, 'cidadao.data_nascimento', '');
        $name = (string) Arr::get($govData, 'cidadao.nome', '');

        $year = substr($birthDate, 0, 4);
        $month = substr($birthDate, 5, 2);
        $day = substr($birthDate, 8, 2);

        $candidates = [];

        if (preg_match('/^\d{4}$/', $year) === 1) {
            $candidates[] = [
                'prompt' => 'Confirme o ANO de nascimento do cidadao.',
                'expected' => $year,
                'kind' => 'year',
                'mask_hint' => '**/**/'.$year,
            ];
        }

        if (preg_match('/^\d{2}$/', $month) === 1) {
            $candidates[] = [
                'prompt' => 'Confirme o MES de nascimento do cidadao (2 digitos).',
                'expected' => $month,
                'kind' => 'month',
                'mask_hint' => '**/'.$month.'/**',
            ];
        }

        if (preg_match('/^\d{2}$/', $day) === 1) {
            $candidates[] = [
                'prompt' => 'Confirme o DIA de nascimento do cidadao (2 digitos).',
                'expected' => $day,
                'kind' => 'day',
                'mask_hint' => $day.'/**/**',
            ];
        }

        if ($candidates === []) {
            // Fallback when birth date is unavailable.
            $expected = mb_strtoupper(mb_substr(trim($name), 0, 1));
            $candidates[] = [
                'prompt' => 'Confirme a PRIMEIRA LETRA do nome completo do cidadao.',
                'expected' => $expected,
                'kind' => 'initial',
                'mask_hint' => ($expected !== '' ? $expected : '*').'********',
            ];
        }

        $picked = $candidates[random_int(0, count($candidates) - 1)];
        $token = (string) Str::uuid();

        session()->put($this->sessionKey($context), [
            'token' => $token,
            'context' => $context,
            'expected' => hash('sha256', trim((string) $picked['expected'])),
            'expected_kind' => $picked['kind'] ?? 'initial',
            'created_at' => now()->timestamp,
            'expires_at' => now()->addMinutes(10)->timestamp,
        ]);

        return [
            'token' => $token,
            'prompt' => $picked['prompt'],
            'mask_hint' => $picked['mask_hint'],
            'context' => $context,
        ];
    }

    public function verify(string $context, string $token, string $answer): bool
    {
        $payload = session()->get($this->sessionKey($context));

        if (! is_array($payload)) {
            return false;
        }

        if (($payload['token'] ?? null) !== $token) {
            return false;
        }

        if ((int) ($payload['expires_at'] ?? 0) < now()->timestamp) {
            return false;
        }

        $kind = (string) ($payload['expected_kind'] ?? 'initial');
        $normalizedAnswer = $this->normalizeAnswer($kind, $answer);

        return hash_equals((string) ($payload['expected'] ?? ''), hash('sha256', $normalizedAnswer));
    }

    public function clear(string $context): void
    {
        session()->forget($this->sessionKey($context));
    }

    public function markVerified(string $context, string $cpf): void
    {
        session()->put('identity_verified.'.$context, [
            'cpf' => preg_replace('/\D+/', '', $cpf) ?? '',
            'verified_at' => now()->timestamp,
            'expires_at' => now()->addMinutes(20)->timestamp,
        ]);
    }

    public function isVerified(string $context, string $cpf): bool
    {
        $payload = session()->get('identity_verified.'.$context);

        if (! is_array($payload)) {
            return false;
        }

        if ((int) ($payload['expires_at'] ?? 0) < now()->timestamp) {
            return false;
        }

        $normalized = preg_replace('/\D+/', '', $cpf) ?? '';

        return ($payload['cpf'] ?? null) === $normalized;
    }

    public function consumeVerified(string $context): void
    {
        session()->forget('identity_verified.'.$context);
    }

    private function sessionKey(string $context): string
    {
        return 'identity_challenge.'.$context;
    }

    private function normalizeAnswer(string $kind, string $answer): string
    {
        $trimmed = trim($answer);
        $digits = preg_replace('/\D+/', '', $trimmed) ?? '';

        if ($kind === 'initial') {
            return mb_strtoupper(mb_substr($trimmed, 0, 1));
        }

        [$day, $month, $year] = $this->extractDateParts($trimmed, $digits);

        return match ($kind) {
            'day' => $day,
            'month' => $month,
            'year' => $year,
            default => $trimmed,
        };
    }

    /**
     * @return array{0:string,1:string,2:string}
     */
    private function extractDateParts(string $answer, string $digits): array
    {
        $day = '';
        $month = '';
        $year = '';

        if (preg_match('/^\s*(\d{1,2})[\/\-.](\d{1,2})[\/\-.](\d{2,4})\s*$/', $answer, $matches) === 1) {
            $day = str_pad($matches[1], 2, '0', STR_PAD_LEFT);
            $month = str_pad($matches[2], 2, '0', STR_PAD_LEFT);
            $year = str_pad(substr($matches[3], -4), 4, '0', STR_PAD_LEFT);

            return [$day, $month, $year];
        }

        if (preg_match('/^\s*(\d{4})[\/\-.](\d{1,2})[\/\-.](\d{1,2})\s*$/', $answer, $matches) === 1) {
            $year = str_pad($matches[1], 4, '0', STR_PAD_LEFT);
            $month = str_pad($matches[2], 2, '0', STR_PAD_LEFT);
            $day = str_pad($matches[3], 2, '0', STR_PAD_LEFT);

            return [$day, $month, $year];
        }

        if (strlen($digits) === 8) {
            // Preferencia local BR: DDMMYYYY.
            $day = substr($digits, 0, 2);
            $month = substr($digits, 2, 2);
            $year = substr($digits, 4, 4);

            return [$day, $month, $year];
        }

        if (preg_match('/(19|20)\d{2}/', $answer, $yearMatch) === 1) {
            $year = $yearMatch[0];
        } elseif (strlen($digits) === 4) {
            $year = $digits;
        }

        if (strlen($digits) === 2) {
            $day = $digits;
            $month = $digits;
        }

        return [$day, $month, $year];
    }
}
