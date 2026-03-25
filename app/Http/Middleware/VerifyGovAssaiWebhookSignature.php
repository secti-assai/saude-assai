<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class VerifyGovAssaiWebhookSignature
{
    public function handle(Request $request, Closure $next): Response
    {
        $secret = (string) env('GOV_ASSAI_WEBHOOK_SECRET', '');

        if ($secret === '') {
            if (app()->environment('production')) {
                return new JsonResponse([
                    'success' => false,
                    'message' => 'Webhook secret nao configurado.',
                    'error_code' => 'WEBHOOK_SECRET_NOT_CONFIGURED',
                ], 500);
            }

            return $next($request);
        }

        $signatureHeader = (string) $request->header('X-GovAssai-Signature', '');
        $signature = str_starts_with($signatureHeader, 'sha256=')
            ? substr($signatureHeader, 7)
            : $signatureHeader;

        $computed = hash_hmac('sha256', (string) $request->getContent(), $secret);

        if ($signature === '' || ! hash_equals($computed, $signature)) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Assinatura de webhook invalida.',
                'error_code' => 'INVALID_WEBHOOK_SIGNATURE',
            ], 401);
        }

        return $next($request);
    }
}
