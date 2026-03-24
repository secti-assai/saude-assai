<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Autenticação de Dois Fatores (2FA)') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-8 text-center space-y-6">
                    <div class="flex justify-center">
                        <div class="bg-blue-100 p-4 rounded-full text-blue-600">
                            <svg class="w-16 h-16" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path></svg>
                        </div>
                    </div>
                    
                    <div>
                        <h3 class="text-2xl font-bold text-gray-900 mb-2">Proteja sua conta</h3>
                        <p class="text-gray-500 max-w-lg mx-auto">
                            Como profissional de saúde (`{{ str_replace('_', ' ', auth()->user()->role) }}`), o seu perfil tem acesso a dados sensíveis de cidadãos (LGPD). Por isso, <strong>a Autenticação de Dois Fatores é obrigatória</strong> para liberar o seu acesso ao sistema.
                        </p>
                    </div>

                    <div class="bg-gray-50 border border-gray-200 rounded-lg p-6 text-sm text-gray-600 text-left">
                        <p class="font-semibold mb-2">Para ambiente de desenvolvimento:</p>
                        <p>Nesta versão (MVP), a geração do QR Code e validação TOTP (ex: Google Authenticator) estão simuladas. Ao clicar no botão abaixo, a flag <code>two_factor_enabled</code> será marcada como true no banco de dados para o seu usuário.</p>
                    </div>

                    <form method="POST" action="{{ route('2fa.enable') }}" class="pt-4">
                        @csrf
                        <button type="submit" class="sa-btn-primary w-full max-w-xs mx-auto text-lg py-3">
                            <svg class="w-5 h-5 mr-2 inline-block" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path></svg>
                            Ativar 2FA e Continuar
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
