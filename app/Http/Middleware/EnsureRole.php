<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureRole
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if (! $user) {
            abort(403, 'Acesso negado para este perfil.');
        }

        if ($user->role === 'admin') {
            return $next($request);
        }

        if (! in_array($user->role, $roles, true)) {
            abort(403, 'Acesso negado para este perfil.');
        }

        return $next($request);
    }
}
