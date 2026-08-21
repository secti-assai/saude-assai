<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class SanitizeUserAgent
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        if ($request->headers->has('user-agent')) {
            $userAgent = $request->header('user-agent');
            if (is_string($userAgent)) {
                // Remove caracteres UTF-8 inválidos para prevenir erros 22021 no PostgreSQL
                $sanitized = mb_scrub($userAgent, 'UTF-8');
                $request->headers->set('user-agent', $sanitized);
                $_SERVER['HTTP_USER_AGENT'] = $sanitized;
            }
        }

        return $next($request);
    }
}
