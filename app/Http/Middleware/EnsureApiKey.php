<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureApiKey
{
    public function handle(Request $request, Closure $next): Response
    {
        $provided = (string) ($request->header('X-API-Key') ?? $request->query('api_key', ''));
        $configured = array_filter(array_map('trim', explode(',', (string) env('INTEGRATION_API_KEYS', ''))));

        if (empty($configured) && config('services.gov_assai.api_key')) {
            $configured = [(string) config('services.gov_assai.api_key')];
        }

        $valid = false;
        foreach ($configured as $key) {
            if ($provided !== '' && hash_equals((string) $key, $provided)) {
                $valid = true;
                break;
            }
        }

        if (! $valid) {
            return new JsonResponse([
                'success' => false,
                'message' => 'API key ausente ou invalida.',
                'error_code' => 'INVALID_API_KEY',
            ], 401);
        }

        return $next($request);
    }
}
