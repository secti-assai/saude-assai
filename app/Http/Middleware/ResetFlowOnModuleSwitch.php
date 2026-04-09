<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ResetFlowOnModuleSwitch
{
    private const MODULE_SESSION_KEY = 'navigation.current_module';

    /**
     * @var array<int, string>
     */
    private const FLOW_SESSION_KEYS = [
        'women_clinic.schedule_flow',
        'central_pharmacy.reception_flow',
        'identity_challenge.women_clinic_schedule',
        'identity_verified.women_clinic_schedule',
        'identity_challenge.central_pharmacy_reception',
        'identity_verified.central_pharmacy_reception',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->user()) {
            return $next($request);
        }

        // Only switch module context on real page navigation requests.
        // Background polling (AJAX/JSON) from another tab must not clear active flows.
        if (! $request->isMethod('GET') || $request->expectsJson() || $request->ajax()) {
            return $next($request);
        }

        $currentModule = $this->resolveModuleKey($request);
        $previousModule = (string) $request->session()->get(self::MODULE_SESSION_KEY, '');

        if ($currentModule !== '' && $previousModule !== '' && $currentModule !== $previousModule) {
            $request->session()->forget(self::FLOW_SESSION_KEYS);
        }

        if ($currentModule !== '') {
            $request->session()->put(self::MODULE_SESSION_KEY, $currentModule);
        }

        return $next($request);
    }

    private function resolveModuleKey(Request $request): string
    {
        $segment = (string) $request->segment(1);

        return match ($segment) {
            'agendador', 'agendamentos' => 'clinic_scheduler',
            'clinica-mulher' => 'women_clinic',
            'policlinica' => 'policlinica',
            'farmacia-central' => 'central_pharmacy',
            'admin' => 'admin',
            'dashboard' => 'dashboard',
            'profile' => 'profile',
            'prescriptions' => 'prescriptions',
            'calls', 'painel' => 'calls',
            default => $segment,
        };
    }
}
