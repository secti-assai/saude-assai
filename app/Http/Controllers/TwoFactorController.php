<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TwoFactorController extends Controller
{
    public function setup(Request $request): View|RedirectResponse
    {
        // Se o usuário já ativou, não precisa ver esta tela
        if ($request->user()->two_factor_enabled) {
            return redirect()->route('dashboard');
        }

        return view('auth.2fa-setup');
    }

    public function enable(Request $request): RedirectResponse
    {
        $user = $request->user();

        // Ativa o 2FA para o usuário
        $user->forceFill([
            'two_factor_enabled' => true,
        ])->save();

        return redirect()->route('dashboard')->with('status', '2FA ativado com sucesso! Como esta é uma versão de desenvolvimento, a verificação OTP foi simulada.');
    }
}
