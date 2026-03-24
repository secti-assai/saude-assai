<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Unidades de Saúde - Saúde Assaí</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700&display=swap" rel="stylesheet" />
    <link href="https://fonts.bunny.net/css?family=sora:400,600,700&display=swap" rel="stylesheet" />

    <!-- Scripts -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    
    <style>
        :root {
            --gov-primary: #1e3a8a; /* Tailwind blue-900 */
            --gov-primary-dark: #1e40af; /* Tailwind blue-800 */
            --gov-secondary: #dbeafe; /* Tailwind blue-50 */
            --gov-accent: #f59e0b; /* Tailwind amber-500 */
        }
        .font-sora { font-family: 'Sora', sans-serif; }
    </style>
</head>
<body class="font-sans antialiased text-gray-900 bg-gray-50 flex flex-col min-h-screen">
    <!-- Componentized Header from Index -->
    <header class="bg-white border-b border-gray-200 sticky top-0 z-50 shadow-sm">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-20 flex items-center justify-between">
            <a href="{{ route('portal.home') }}" class="flex items-center gap-4 group">
                <div class="bg-[var(--gov-primary)] p-2.5 rounded-lg text-white shadow-md group-hover:scale-105 transition-transform">
                    <svg class="w-7 h-7" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
                </div>
                <div>
                    <h1 class="font-bold text-2xl font-sora leading-none text-gray-900">Saúde<span class="text-[var(--gov-primary)]">Assaí</span></h1>
                    <p class="text-[10px] uppercase font-bold tracking-widest text-gray-500 mt-0.5">Rede Municipal Inteligente</p>
                </div>
            </a>
            
            <nav class="hidden md:flex space-x-1">
                <a href="{{ route('portal.home') }}" class="px-4 py-2 rounded-lg text-sm font-bold text-gray-700 hover:bg-gray-50 hover:text-[var(--gov-primary)] transition-all">Início</a>
                <a href="{{ route('portal.units') }}" class="px-4 py-2 rounded-lg text-sm font-bold text-[var(--gov-primary)] bg-[var(--gov-secondary)] transition-all">Unidades</a>
                <a href="{{ route('portal.delivery') }}" class="px-4 py-2 rounded-lg text-sm font-bold text-gray-700 hover:bg-gray-50 hover:text-[var(--gov-primary)] transition-all">Remédio em Casa</a>
            </nav>

            <div class="flex items-center gap-3">
                @auth
                    <a href="{{ url('/dashboard') }}" class="hidden md:block text-sm font-bold text-gray-600 hover:text-gray-900">Acessar Painel</a>
                @endauth
                <a href="{{ route('login') }}" class="px-5 py-2.5 text-sm font-bold text-white bg-[var(--gov-primary)] hover:bg-[var(--gov-primary-dark)] rounded-lg transition-colors shadow-md hover:shadow-lg flex items-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>
                    Acesso Cidadão
                </a>
            </div>
        </div>
    </header>

    <main class="flex-grow">
        <section class="py-16 bg-white">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="mb-10 w-full flex flex-col md:flex-row md:items-end justify-between gap-4 border-b border-gray-200 pb-4">
                    <div>
                        <h2 class="text-3xl font-bold font-sora text-[var(--gov-primary-dark)]">Todas as Unidades de Saúde</h2>
                        <p class="text-gray-500 mt-1 font-medium">Consulte endereços, contatos e encontre o local de atendimento mais próximo de você.</p>
                    </div>
                    <div class="bg-[var(--gov-secondary)] text-[var(--gov-primary-dark)] px-4 py-2 rounded-lg font-bold text-sm inline-flex items-center w-max">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
                        {{ $healthUnits->count() }} Unidades Ativas no Município
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    @forelse ($healthUnits as $unit)
                    <!-- Robust Ficha Oficial -->
                    <div class="bg-white border rounded-lg border-gray-200 shadow-sm hover:shadow-lg transition flex flex-col overflow-hidden">
                        <div class="p-1 min-h-[8px] bg-[var(--gov-primary)] w-full"></div>
                        @if($unit->photo_path)
                            <img src="{{ Storage::url($unit->photo_path) }}" alt="Foto de {{ $unit->name }}" class="w-full h-48 object-cover border-b border-gray-100">
                        @endif
                        <div class="p-6 flex-1">
                            <div class="flex items-start justify-between">
                                <span class="bg-teal-50 text-teal-700 text-[10px] font-bold uppercase tracking-wider py-1 px-2 rounded border border-teal-100">
                                    {{ $unit->kind ?? 'Unidade Básica' }}
                                </span>
                            </div>
                            <h4 class="text-xl font-bold font-sora text-gray-800 mt-3 mb-1">{{ $unit->name }}</h4>
                            @if($unit->description)
                                <p class="text-sm text-gray-500 mt-2 mb-4 line-clamp-3" title="{{ $unit->description }}">{{ $unit->description }}</p>
                            @endif
                            
                            <div class="mt-4 space-y-3">
                                <div class="flex items-start text-sm text-gray-600">
                                    <svg class="w-5 h-5 text-[var(--gov-primary)] mr-2 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path></svg>
                                    <span>{{ $unit->address ?: 'Endereço não informado' }}</span>
                                </div>
                                <div class="flex items-start text-sm text-gray-600">
                                    <svg class="w-5 h-5 text-[var(--gov-primary)] mr-2 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"></path></svg>
                                    <span>{{ $unit->phone ?: 'Telefone Indisponível' }}</span>
                                </div>
                                <div class="flex items-start text-sm text-gray-600">
                                    <svg class="w-5 h-5 text-[var(--gov-primary)] mr-2 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                    <span>Horário Oficial: 08:00 às 17:00</span>
                                </div>
                            </div>
                        </div>
                        <div class="px-6 py-4 bg-gray-50 border-t border-gray-100 w-full text-center hover:bg-gray-100 transition-colors">
                            <a href="{{ $unit->maps_link ?: 'https://maps.google.com/?q=' . urlencode($unit->address . ', Assaí - PR') }}" target="_blank" rel="noopener noreferrer" class="text-sm font-bold text-[var(--gov-primary)] hover:text-[var(--gov-primary-dark)] flex items-center justify-center w-full h-full">Ver no Mapa <svg class="w-4 h-4 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"></path></svg></a>
                        </div>
                    </div>
                    @empty
                    <div class="col-span-1 md:col-span-3 py-10 text-center text-gray-500 bg-gray-50 rounded-xl border border-dashed border-gray-300">
                        <svg class="w-12 h-12 text-gray-400 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path></svg>
                        <p class="text-lg font-medium text-gray-900">Nenhuma unidade de saúde encontrada no momento.</p>
                        <p class="text-sm">Tente novamente mais tarde.</p>
                    </div>
                    @endforelse
                </div>
            </div>
        </section>
    </main>

    <!-- Institutional Footer -->
    <footer class="bg-[var(--gov-primary-dark)] text-white mt-auto py-12 border-t-[6px] border-[var(--gov-accent)]">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-8 mb-8">
                <div class="col-span-1 md:col-span-2">
                    <div class="flex items-center gap-3 mb-4">
                        <div class="bg-white/10 p-2 rounded-lg">
                            <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
                        </div>
                        <h2 class="font-bold text-2xl font-sora text-white">Saúde<span class="text-blue-300">Assaí</span></h2>
                    </div>
                    <p class="text-blue-100 text-sm leading-relaxed max-w-md">Painel oficial da Secretaria de Saúde de Assaí para acompanhamento de filas e serviços unificados de saúde.</p>
                </div>
                <div>
                    <h3 class="font-bold text-lg mb-4 text-white">Links Rápidos</h3>
                    <ul class="space-y-2 text-sm text-blue-100">
                        <li><a href="#" class="hover:text-white transition-colors">Portal de Transparência</a></li>
                        <li><a href="#" class="hover:text-white transition-colors">Carta de Serviços</a></li>
                        <li><a href="#" class="hover:text-white transition-colors">Ouvidoria Municipal</a></li>
                    </ul>
                </div>
                <div>
                    <h3 class="font-bold text-lg mb-4 text-white">Atendimento</h3>
                    <ul class="space-y-2 text-sm text-blue-100">
                        <li><svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"></path></svg> (43) 3262-1212</li>
                        <li><svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path></svg> saude@assai.pr.gov.br</li>
                        <li><svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path></svg> Rua Marechal Rondon, Centro</li>
                    </ul>
                </div>
            </div>
            
            <div class="border-t border-blue-800 pt-8 flex flex-col md:flex-row items-center justify-between gap-4">
                <div class="text-sm justify-between w-full flex items-center text-blue-200">
                    <p>&copy; {{ date('Y') }} Prefeitura de Assaí. Todos os direitos reservados.</p>
                    <p class="font-medium text-white flex items-center gap-2"><svg class="w-4 h-4 text-[var(--gov-accent)]" fill="currentColor" viewBox="0 0 20 20"><path d="M10 2a8 8 0 100 16 8 8 0 000-16zM9 11l-3-3 1.4-1.4L9 8.2l4.6-4.6L15 5l-6 6z"></path></svg> Sistema SECTI</p>
                </div>
            </div>
        </div>
    </footer>
</body>
</html>
