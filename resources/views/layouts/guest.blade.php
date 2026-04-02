<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Saúde Assaí') }}</title>

        <link rel="apple-touch-icon" sizes="180x180" href="{{ asset('assets/apple-touch-icon.png') }}">
        <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('assets/favicon-32x32.png') }}">
        <link rel="icon" type="image/png" sizes="16x16" href="{{ asset('assets/favicon-16x16.png') }}">
        <link rel="icon" type="image/x-icon" href="{{ asset('assets/favicon.ico') }}">
        <link rel="manifest" href="{{ asset('assets/site.webmanifest') }}">

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700,800&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans antialiased" style="font-family: 'Inter', sans-serif;">
        <div class="min-h-screen flex">
            {{-- LEFT: Hero Panel --}}
            <div class="hidden lg:flex lg:w-1/2 relative overflow-hidden items-center justify-center"
                 style="background: linear-gradient(135deg, #071b2a 0%, #0c2d40 50%, #0a8f7b 100%);">
                {{-- Decorative circles --}}
                <div class="absolute -top-20 -left-20 w-80 h-80 rounded-full opacity-10" style="background: radial-gradient(circle, #2db5ff, transparent);"></div>
                <div class="absolute -bottom-32 -right-32 w-96 h-96 rounded-full opacity-10" style="background: radial-gradient(circle, #0a8f7b, transparent);"></div>

                <div class="relative z-10 px-12 text-center max-w-md">
                    {{-- Logo / Brand --}}
                    <div class="mb-8">
                        <div class="w-16 h-16 mx-auto rounded-2xl bg-white/10 backdrop-blur-sm flex items-center justify-center mb-4">
                            <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M21 8.25c0-2.485-2.099-4.5-4.688-4.5-1.935 0-3.597 1.126-4.312 2.733-.715-1.607-2.377-2.733-4.313-2.733C5.1 3.75 3 5.765 3 8.25c0 7.22 9 12 9 12s9-4.78 9-12z"/>
                            </svg>
                        </div>
                        <h1 class="text-3xl font-extrabold text-white tracking-tight">Saúde Assaí</h1>
                        <p class="text-white/60 text-sm mt-2">Sistema de Gestão de Saúde</p>
                    </div>

                    <div class="space-y-4 text-left">
                        <div class="flex items-start gap-3 text-white/80">
                            <svg class="w-5 h-5 text-emerald-400 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            <span class="text-sm">Recepção com senha de atendimento</span>
                        </div>
                        <div class="flex items-start gap-3 text-white/80">
                            <svg class="w-5 h-5 text-emerald-400 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            <span class="text-sm">Triagem com Protocolo de Manchester</span>
                        </div>
                        <div class="flex items-start gap-3 text-white/80">
                            <svg class="w-5 h-5 text-emerald-400 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            <span class="text-sm">Prontuário SOAP e dispensação de medicamentos</span>
                        </div>
                    </div>

                    <p class="text-white/30 text-xs mt-10">Prefeitura Municipal de Assaí · SECTI</p>
                </div>
            </div>

            {{-- RIGHT: Form --}}
            <div class="w-full lg:w-1/2 flex flex-col items-center justify-center px-6 sm:px-12" style="background: var(--sa-paper, #f0f6fb);">
                <div class="w-full max-w-sm">
                    {{-- Mobile logo --}}
                    <div class="lg:hidden text-center mb-8">
                        <h1 class="text-2xl font-extrabold tracking-tight" style="color: var(--sa-primary, #0a8f7b);">Saúde Assaí</h1>
                        <p class="text-gray-500 text-sm mt-1">Sistema de Gestão de Saúde</p>
                    </div>

                    {{ $slot }}
                </div>
            </div>
        </div>
    </body>
</html>
