<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class Ensure2FA
{
    /**
     * Perfis que DEVEM ter 2FA ativo para acessar rotas autenticadas.
     */
    private const REQUIRED_ROLES = [
        'agendador',
        'recepcao_clinica',
        'medico_clinica',
        'recepcao_farmacia',
        'atendimento_farmacia',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user) {
            return $next($request);
        }

        if (
            in_array($user->role, self::REQUIRED_ROLES, true)
            && ! $user->two_factor_enabled
        ) {
            // Se o usuário está na rota de configuração de 2FA, deixa passar
            if ($request->routeIs('2fa.*') || $request->routeIs('logout')) {
                return $next($request);
            }

            return redirect()->route('2fa.setup')
                ->with('warning', 'Você precisa ativar a autenticação de dois fatores para continuar.');
        }

        return $next($request);
    }
}
