<x-guest-layout>
    <div class="sa-fade-in">
        <h2 class="text-xl font-bold text-gray-900 mb-1">Entrar no Sistema</h2>
        <p class="text-sm text-gray-500 mb-6">Acesse com suas credenciais de servidor</p>

        {{-- Session Status --}}
        <x-auth-session-status class="mb-4" :status="session('status')" />

        <form method="POST" action="{{ route('login') }}" class="space-y-4">
            @csrf

            {{-- Email --}}
            <div>
                <label for="email" class="sa-label">E-mail</label>
                <input id="email" name="email" type="email" value="{{ old('email') }}" required autofocus autocomplete="username"
                       class="sa-input" placeholder="seu.email@saude.assai.pr.gov.br">
                @error('email')
                    <p class="text-red-500 text-xs mt-1.5">{{ $message }}</p>
                @enderror
            </div>

            {{-- Password --}}
            <div>
                <label for="password" class="sa-label">Senha</label>
                <input id="password" name="password" type="password" required autocomplete="current-password"
                       class="sa-input" placeholder="••••••••">
                @error('password')
                    <p class="text-red-500 text-xs mt-1.5">{{ $message }}</p>
                @enderror
            </div>

            {{-- Remember + Forgot --}}
            <div class="flex items-center justify-between">
                <label for="remember_me" class="inline-flex items-center cursor-pointer">
                    <input id="remember_me" type="checkbox" name="remember"
                           class="rounded border-gray-300 text-emerald-600 shadow-sm focus:ring-emerald-500 focus:ring-offset-0">
                    <span class="ms-2 text-sm text-gray-600">Lembrar-me</span>
                </label>

                @if (Route::has('password.request'))
                    <a class="text-sm font-medium hover:underline transition" style="color: var(--sa-primary);" href="{{ route('password.request') }}">
                        Esqueceu a senha?
                    </a>
                @endif
            </div>

            {{-- Submit --}}
            <button type="submit" class="sa-btn-primary w-full !py-3">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0013.5 3h-6a2.25 2.25 0 00-2.25 2.25v13.5A2.25 2.25 0 007.5 21h6a2.25 2.25 0 002.25-2.25V15m3 0l3-3m0 0l-3-3m3 3H9"/>
                </svg>
                Entrar
            </button>
        </form>
    </div>
</x-guest-layout>
