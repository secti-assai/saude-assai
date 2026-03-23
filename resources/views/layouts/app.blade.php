<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Saúde Assaí') }}</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&display=swap" rel="stylesheet">

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans antialiased" style="background: var(--sa-paper);">
        <div class="min-h-screen flex">
            {{-- Sidebar --}}
            @include('layouts.navigation')

            {{-- Main Content --}}
            <div class="flex-1 flex flex-col" style="margin-left: var(--sa-sidebar-w);" id="sa-main-area">
                @isset($header)
                    <header class="bg-white/80 backdrop-blur-sm border-b border-gray-100">
                        <div class="max-w-7xl mx-auto py-5 px-6 lg:px-8">
                            {{ $header }}
                        </div>
                    </header>
                @endisset

                <main class="flex-1 py-6 px-6 lg:px-8">
                    <div class="max-w-7xl mx-auto">
                        {{ $slot }}
                    </div>
                </main>

                <footer class="border-t border-gray-100 py-4 px-6">
                    <p class="text-xs text-gray-400 text-center">Saúde Assaí · Rede Municipal Inteligente · SECTI {{ date('Y') }}</p>
                </footer>
            </div>
        </div>

        {{-- Mobile sidebar overlay --}}
        <div x-data x-show="$store.sidebar.open" x-transition.opacity
             @click="$store.sidebar.open = false"
             class="fixed inset-0 bg-black/40 z-30 lg:hidden" style="display:none;">
        </div>

        <script>
            document.addEventListener('alpine:init', () => {
                Alpine.store('sidebar', { open: false });
            });

            // Responsive: reset sidebar margin on mobile
            function handleResize() {
                const main = document.getElementById('sa-main-area');
                if (window.innerWidth < 1024) {
                    main.style.marginLeft = '0';
                } else {
                    main.style.marginLeft = 'var(--sa-sidebar-w)';
                }
            }
            window.addEventListener('resize', handleResize);
            handleResize();
        </script>
    </body>
</html>
