<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Saúde Assaí - Portal Oficial</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@300;400;500;600;700&family=Sora:wght@600;700;800&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css'])
    <style>
        :root {
            --gov-primary: #005a50;
            --gov-primary-dark: #00453d;
            --gov-secondary: #f0f7f6;
            --gov-accent: #ffb81c;
            --gov-text: #2d3748;
        }

        body {
            font-family: 'Manrope', sans-serif;
            color: var(--gov-text);
            background-color: #f8fafc;
        }

        h1, h2, h3, h4, h5, h6, .font-sora {
            font-family: 'Sora', sans-serif;
        }

        /* Hero Image with Overlay */
        .hero-bg {
            background-image: linear-gradient(rgba(0, 90, 80, 0.85), rgba(0, 90, 80, 0.75)), url('https://images.unsplash.com/photo-1519494026892-80bbd2d6fd0d?ixlib=rb-4.0.3&auto=format&fit=crop&w=2000&q=80');
            background-size: cover;
            background-position: center;
        }

        /* News Cards */
        .news-card-main {
            background-image: linear-gradient(to top, rgba(0,0,0,0.8) 0%, rgba(0,0,0,0) 100%);
        }

        /* Dynamic Alert Border Pulse */
        @keyframes borderPulse {
            0% { border-color: rgba(220, 38, 38, 0.4); box-shadow: 0 0 0 0 rgba(220, 38, 38, 0.4); }
            70% { border-color: rgba(220, 38, 38, 1); box-shadow: 0 0 0 10px rgba(220, 38, 38, 0); }
            100% { border-color: rgba(220, 38, 38, 0.4); box-shadow: 0 0 0 0 rgba(220, 38, 38, 0); }
        }
        .alert-banner {
            border: 2px solid;
            animation: borderPulse 2s infinite;
        }

        /* Smooth inputs */
        input:focus {
            outline: none;
            box-shadow: 0 0 0 3px rgba(0, 90, 80, 0.3);
        }
    </style>
</head>
<body class="antialiased flex flex-col min-h-screen">

    <!-- Top Header / Gov Bar -->
    <div class="bg-gray-100 border-b border-gray-200 text-sm hidden md:block">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-2 flex justify-between items-center text-gray-600">
            <div class="flex space-x-4">
                <a href="#" class="hover:text-[var(--gov-primary)] transition">Prefeitura de Assaí</a>
                <span>|</span>
                <a href="#" class="hover:text-[var(--gov-primary)] transition">Transparência</a>
                <span>|</span>
                <a href="#" class="hover:text-[var(--gov-primary)] transition">Ouvidoria Geral</a>
            </div>
            <div>
                <span>Atendimento: (43) 3262-1000</span>
            </div>
        </div>
    </div>

    <!-- Main Navigation -->
    <nav class="bg-white shadow-sm sticky top-0 z-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-20 items-center">
                <div class="flex items-center space-x-3">
                    <svg class="w-10 h-10 text-[var(--gov-primary)]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                    </svg>
                    <div>
                        <h1 class="text-2xl font-bold font-sora text-[var(--gov-primary)] leading-none">Saúde Assaí</h1>
                        <span class="text-xs font-semibold text-gray-500 uppercase tracking-widest">Secretaria Municipal</span>
                    </div>
                </div>
                <div>
                    <a href="{{ route('login') }}" class="inline-flex items-center justify-center px-4 py-2 border border-transparent text-sm font-semibold rounded-md text-white bg-[var(--gov-primary)] hover:bg-[var(--gov-primary-dark)] transition-colors focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-[var(--gov-primary)]">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1"></path></svg>
                        Acesso Profissional
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <!-- Dynamic Alert Banner -->
    @if ($featuredAlert)
    <div class="bg-red-50 py-3 border-b-2 border-red-500 alert-banner shadow-sm z-40 relative">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 flex flex-col sm:flex-row items-center justify-between gap-4">
            <div class="flex items-center gap-3">
                <div class="bg-red-100 rounded-full p-2 text-red-600 shrink-0">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                </div>
                <div>
                    <h4 class="font-bold text-red-800 text-lg">Comunicados Importantes: {{ $featuredAlert->title }}</h4>
                    <p class="text-sm text-red-600">{{ \Illuminate\Support\Str::limit($featuredAlert->body ?? '', 150) }}</p>
                </div>
            </div>
            <a href="#" class="shrink-0 text-sm font-bold text-red-700 hover:text-red-900 border border-red-300 hover:bg-red-100 px-4 py-2 rounded-md transition">
                Ler mais
            </a>
        </div>
    </div>
    @endif

    <!-- Hero Section -->
    <section class="hero-bg py-24 lg:py-32 relative">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10 text-center">
            <h1 class="text-4xl md:text-5xl lg:text-6xl font-extrabold text-white font-sora tracking-tight mb-6 drop-shadow-lg">
                Acesso à Saúde de Qualidade <br class="hidden md:block">para todos os Assaienses
            </h1>
            <p class="text-lg md:text-xl text-[var(--gov-secondary)] mb-10 max-w-2xl mx-auto font-light">
                Encontre unidades de atendimento, agende consultas, acesse comunicados oficiais e serviços da Secretaria de Saúde de forma rápida e digital.
            </p>
            
            <!-- Central Search Bar -->
            <div class="max-w-3xl mx-auto bg-white rounded-lg p-2 shadow-2xl flex items-center">
                <div class="px-4 text-gray-400">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                </div>
                <input type="text" placeholder="O que você precisa encontrar? (Ex: Vacinas, Consultas, Medicamentos)" class="w-full py-4 text-gray-700 border-none bg-transparent placeholder-gray-400 text-lg font-medium leading-tight focus:ring-0">
                <button class="bg-[var(--gov-accent)] hover:bg-yellow-500 text-yellow-900 font-bold px-8 py-4 rounded-md transition-colors whitespace-nowrap">
                    Buscar
                </button>
            </div>
        </div>
        
        <!-- Curvas / Wave Decor (optional) -->
        <div class="absolute bottom-0 inset-x-0 h-16 bg-white" style="clip-path: polygon(0 100%, 100% 100%, 100% 0, 0 100%);"></div>
    </section>

    <!-- Services Grid (Icons-First) -->
    <section class="py-16 bg-white">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-12">
                <h2 class="text-3xl font-bold font-sora text-[var(--gov-primary-dark)]">Serviços Mais Procurados</h2>
                <div class="w-24 h-1 bg-[var(--gov-accent)] mx-auto mt-4 rounded-full"></div>
            </div>

            <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-6">
                <!-- Serv 1 -->
                <a href="#" class="group flex flex-col items-center p-6 border border-gray-100 rounded-xl hover:shadow-xl transition-all hover:-translate-y-1 bg-gray-50 hover:bg-white text-center">
                    <div class="w-16 h-16 rounded-full bg-teal-100 text-teal-600 flex items-center justify-center mb-4 group-hover:scale-110 transition-transform">
                        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                    </div>
                    <span class="font-semibold text-gray-800 leading-tight group-hover:text-[var(--gov-primary)]">Agendar<br>Consulta</span>
                </a>
                <!-- Serv 2 -->
                <a href="#" class="group flex flex-col items-center p-6 border border-gray-100 rounded-xl hover:shadow-xl transition-all hover:-translate-y-1 bg-gray-50 hover:bg-white text-center">
                    <div class="w-16 h-16 rounded-full bg-blue-100 text-blue-600 flex items-center justify-center mb-4 group-hover:scale-110 transition-transform">
                        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                    </div>
                    <span class="font-semibold text-gray-800 leading-tight group-hover:text-[var(--gov-primary)]">Resultado<br>de Exames</span>
                </a>
                <!-- Serv 3 -->
                <a href="#" class="group flex flex-col items-center p-6 border border-gray-100 rounded-xl hover:shadow-xl transition-all hover:-translate-y-1 bg-gray-50 hover:bg-white text-center">
                    <div class="w-16 h-16 rounded-full bg-green-100 text-green-600 flex items-center justify-center mb-4 group-hover:scale-110 transition-transform">
                        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 6l3 1m0 0l-3 9a5.002 5.002 0 006.001 0M6 7l3 9M6 7l6-2m6 2l3-1m-3 1l-3 9a5.002 5.002 0 006.001 0M18 7l3 9m-3-9l-6-2m0-2v2m0 16V5m0 16H9m3 0h3"></path></svg>
                    </div>
                    <span class="font-semibold text-gray-800 leading-tight group-hover:text-[var(--gov-primary)]">Farmácia<br>Municipal</span>
                </a>
                <!-- Serv 4 -->
                <a href="#" class="group flex flex-col items-center p-6 border border-gray-100 rounded-xl hover:shadow-xl transition-all hover:-translate-y-1 bg-gray-50 hover:bg-white text-center">
                    <div class="w-16 h-16 rounded-full bg-purple-100 text-purple-600 flex items-center justify-center mb-4 group-hover:scale-110 transition-transform">
                        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z"></path></svg>
                    </div>
                    <span class="font-semibold text-gray-800 leading-tight group-hover:text-[var(--gov-primary)]">Calendário de<br>Vacinação</span>
                </a>
                <!-- Serv 5 -->
                <a href="#" class="group flex flex-col items-center p-6 border border-gray-100 rounded-xl hover:shadow-xl transition-all hover:-translate-y-1 bg-gray-50 hover:bg-white text-center">
                    <div class="w-16 h-16 rounded-full bg-orange-100 text-orange-600 flex items-center justify-center mb-4 group-hover:scale-110 transition-transform">
                        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V8a2 2 0 00-2-2h-5m-4 0V5a2 2 0 114 0v1m-4 0a2 2 0 104 0m-5 8a2 2 0 100-4 2 2 0 000 4zm0 0c1.306 0 2.417.835 2.83 2M9 14a3.001 3.001 0 00-2.83 2M15 11h3m-3 4h2"></path></svg>
                    </div>
                    <span class="font-semibold text-gray-800 leading-tight group-hover:text-[var(--gov-primary)]">Cartão<br>SUS</span>
                </a>
                <!-- Serv 6 -->
                <a href="#" class="group flex flex-col items-center p-6 border border-gray-100 rounded-xl hover:shadow-xl transition-all hover:-translate-y-1 bg-gray-50 hover:bg-white text-center">
                    <div class="w-16 h-16 rounded-full bg-rose-100 text-rose-600 flex items-center justify-center mb-4 group-hover:scale-110 transition-transform">
                        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"></path></svg>
                    </div>
                    <span class="font-semibold text-gray-800 leading-tight group-hover:text-[var(--gov-primary)]">Ouvidoria da<br>Saúde</span>
                </a>
            </div>
        </div>
    </section>

    <!-- News & Campaigns -->
    <section class="py-16 bg-gray-50 border-y border-gray-200">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-end mb-8">
                <div>
                    <h2 class="text-3xl font-bold font-sora text-[var(--gov-primary-dark)]">Destaques de Notícias</h2>
                    <p class="text-gray-500 mt-2 font-medium">Acompanhe as últimas campanhas e novidades oficiais</p>
                </div>
                <a href="#" class="hidden md:inline-flex items-center text-[var(--gov-primary)] font-bold hover:underline">
                    Ver todas as notícias
                    <svg class="w-4 h-4 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg>
                </a>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
                @php
                    $mainNews = $news->first();
                    $sideItems = collect($news->slice(1, 2))->merge($notices->take(1))->take(3);
                @endphp

                <!-- Main Left (Large) -->
                @if($mainNews)
                <div class="lg:col-span-7 relative rounded-2xl overflow-hidden shadow-lg group block h-[400px] md:h-[500px]">
                    <img src="https://images.unsplash.com/photo-1584515933487-779824d29309?auto=format&fit=crop&w=1200&q=80" alt="News Image" class="absolute inset-0 w-full h-full object-cover group-hover:scale-105 transition-transform duration-700">
                    <div class="absolute inset-0 news-card-main flex flex-col justify-end p-6 md:p-8">
                        <span class="bg-[var(--gov-accent)] text-yellow-900 text-xs font-bold uppercase tracking-wider py-1 px-3 rounded w-max mb-4">
                            {{ $mainNews->type === 'campanha' ? 'Campanha' : 'Destaque' }}
                        </span>
                        <h3 class="text-2xl md:text-3xl font-bold font-sora text-white leading-tight mb-2 group-hover:text-[var(--gov-accent)] transition-colors">
                            {{ $mainNews->title }}
                        </h3>
                        <p class="text-gray-200 text-sm md:text-base mb-4 line-clamp-2">
                            {{ \Illuminate\Support\Str::limit($mainNews->body ?? '', 120) }}
                        </p>
                        <div class="text-gray-300 text-sm flex items-center">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                            {{ optional($mainNews->published_at)->format('d/m/Y') ?? 'Atualizado recentemente' }}
                        </div>
                    </div>
                </div>
                @else
                <div class="lg:col-span-7 bg-white rounded-2xl shadow border border-gray-100 flex items-center justify-center h-[400px]">
                    <p class="text-gray-500">Nenhuma notícia em destaque.</p>
                </div>
                @endif

                <!-- Smaller Right Side -->
                <div class="lg:col-span-5 flex flex-col space-y-4">
                    @forelse($sideItems as $item)
                    <a href="#" class="flex bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden hover:shadow-md transition-shadow group h-[125px] md:h-[155px]">
                        <div class="w-1/3 shrink-0 overflow-hidden relative">
                            <img src="https://images.unsplash.com/photo-1579684385127-1ef15d508118?auto=format&fit=crop&w=400&q=80" class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-500">
                        </div>
                        <div class="p-4 flex flex-col justify-between w-2/3">
                            <div>
                                <span class="text-[10px] font-bold uppercase tracking-wider text-[var(--gov-primary)] mb-1 block">
                                    {{ $item->type ?? 'Aviso Oficial' }}
                                </span>
                                <h4 class="font-bold text-gray-800 text-sm md:text-base leading-snug line-clamp-2 group-hover:text-[var(--gov-primary)] transition-colors">
                                    {{ $item->title }}
                                </h4>
                            </div>
                            <span class="text-xs text-gray-500 flex items-center mt-2">
                                <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                {{ optional($item->published_at)->format('d/m/Y') ?? 'Recente' }}
                            </span>
                        </div>
                    </a>
                    @empty
                    <div class="p-6 bg-white rounded-xl border border-dashed border-gray-300 text-center">
                        <p class="text-gray-500">Sem avisos adicionais.</p>
                    </div>
                    @endforelse
                </div>
            </div>
            
            <div class="mt-6 text-center md:hidden">
                <a href="#" class="inline-flex items-center text-[var(--gov-primary)] font-bold hover:underline">
                    Ver todas as notícias
                    <svg class="w-4 h-4 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg>
                </a>
            </div>
        </div>
    </section>

    <!-- Health Units Section -->
    <section class="py-16 bg-white">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="mb-10 w-full flex flex-col md:flex-row md:items-end justify-between gap-4 border-b border-gray-200 pb-4">
                <div>
                    <h2 class="text-3xl font-bold font-sora text-[var(--gov-primary-dark)]">Unidades de Saúde</h2>
                    <p class="text-gray-500 mt-1 font-medium">Encontre o local de atendimento mais próximo</p>
                </div>
                <div class="bg-[var(--gov-secondary)] text-[var(--gov-primary-dark)] px-4 py-2 rounded-lg font-bold text-sm inline-flex items-center w-max">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
                    {{ $healthUnits->count() }} Unidades Ativas
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                @forelse ($healthUnits as $unit)
                <!-- Robust Ficha Oficial -->
                <div class="bg-white border rounded-lg border-gray-200 shadow-sm hover:shadow-lg transition flex flex-col">
                    <div class="p-1 min-h-[8px] bg-[var(--gov-primary)] w-full rounded-t-lg"></div>
                    <div class="p-6 flex-1">
                        <div class="flex items-start justify-between">
                            <span class="bg-teal-50 text-teal-700 text-[10px] font-bold uppercase tracking-wider py-1 px-2 rounded border border-teal-100">
                                {{ $unit->kind ?? 'Unidade Básica' }}
                            </span>
                        </div>
                        <h4 class="text-xl font-bold font-sora text-gray-800 mt-3 mb-1">{{ $unit->name }}</h4>
                        
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
                    <div class="px-6 py-4 bg-gray-50 border-t border-gray-100 w-full text-center">
                        <a href="#" class="text-sm font-bold text-[var(--gov-primary)] hover:text-[var(--gov-primary-dark)]">Ver no Mapa &rarr;</a>
                    </div>
                </div>
                @empty
                <div class="col-span-1 md:col-span-3 py-10 text-center text-gray-500">
                    <p>Nenhuma unidade de saúde ativa no momento.</p>
                </div>
                @endforelse
            </div>
            
            <div class="mt-10 text-center">
               <a href="#" class="inline-flex justify-center px-6 py-3 border border-gray-300 shadow-sm text-base font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 transition-colors">
                    Consultar todas as unidades
               </a>
            </div>
        </div>
    </section>

    <!-- Institutional Footer -->
    <footer class="bg-[var(--gov-primary-dark)] text-white mt-auto py-12 border-t-[6px] border-[var(--gov-accent)]">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-8">
                <!-- Branding -->
                <div>
                    <h3 class="text-2xl font-bold font-sora text-white mb-4">Saúde Assaí</h3>
                    <p class="text-gray-300 text-sm mb-4 leading-relaxed">
                        Secretaria Municipal da Saúde<br>
                        Prefeitura Municipal de Assaí - PR
                    </p>
                    <div class="flex space-x-3">
                        <a href="#" class="w-10 h-10 rounded-full bg-white/10 hover:bg-white/20 flex items-center justify-center transition">
                            <span class="sr-only">Facebook</span>
                            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M22 12c0-5.523-4.477-10-10-10S2 6.477 2 12c0 4.991 3.657 9.128 8.438 9.878v-6.987h-2.54V12h2.54V9.797c0-2.506 1.492-3.89 3.777-3.89 1.094 0 2.238.195 2.238.195v2.46h-1.26c-1.243 0-1.63.771-1.63 1.562V12h2.773l-.443 2.89h-2.33v6.988C18.343 21.128 22 16.991 22 12z" /></svg>
                        </a>
                        <a href="#" class="w-10 h-10 rounded-full bg-white/10 hover:bg-white/20 flex items-center justify-center transition">
                            <span class="sr-only">Instagram</span>
                            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path fill-rule="evenodd" d="M12.315 2c2.43 0 2.784.013 3.808.06 1.064.049 1.791.218 2.427.465a4.902 4.902 0 011.772 1.153 4.902 4.902 0 011.153 1.772c.247.636.416 1.363.465 2.427.048 1.067.06 1.407.06 4.123v.08c0 2.643-.012 2.987-.06 4.043-.049 1.064-.218 1.791-.465 2.427a4.902 4.902 0 01-1.153 1.772 4.902 4.902 0 01-1.772 1.153c-.636.247-1.363.416-2.427.465-1.067.048-1.407.06-4.123.06h-.08c-2.643 0-2.987-.012-4.043-.06-1.064-.049-1.791-.218-2.427-.465a4.902 4.902 0 01-1.772-1.153 4.902 4.902 0 01-1.153-1.772c-.247-.636-.416-1.363-.465-2.427-.047-1.024-.06-1.379-.06-3.808v-.63c0-2.43.013-2.784.06-3.808.049-1.064.218-1.791.465-2.427a4.902 4.902 0 011.153-1.772A4.902 4.902 0 015.45 2.525c.636-.247 1.363-.416 2.427-.465C8.901 2.013 9.256 2 11.685 2h.63zm-.081 1.802h-.468c-2.456 0-2.784.011-3.807.058-.975.045-1.504.207-1.857.344-.467.182-.8.398-1.15.748-.35.35-.566.683-.748 1.15-.137.353-.3.882-.344 1.857-.047 1.023-.058 1.351-.058 3.807v.468c0 2.456.011 2.784.058 3.807.045.975.207 1.504.344 1.857.182.466.399.8.748 1.15.35.35.683.566 1.15.748.353.137.882.3 1.857.344 1.054.048 1.37.058 4.041.058h.08c2.597 0 2.917-.01 3.96-.058.976-.045 1.505-.207 1.858-.344.466-.182.8-.398 1.15-.748.35-.35.566-.683.748-1.15.137-.353.3-.882.344-1.857.048-1.055.058-1.37.058-4.041v-.08c0-2.597-.01-2.917-.058-3.96-.045-.976-.207-1.505-.344-1.858a3.097 3.097 0 00-.748-1.15 3.098 3.098 0 00-1.15-.748c-.353-.137-.882-.3-1.857-.344-1.023-.047-1.351-.058-3.807-.058zM12 6.865a5.135 5.135 0 110 10.27 5.135 5.135 0 010-10.27zm0 1.802a3.333 3.333 0 100 6.666 3.333 3.333 0 000-6.666zm5.338-3.205a1.2 1.2 0 110 2.4 1.2 1.2 0 010-2.4z" clip-rule="evenodd" /></svg>
                        </a>
                    </div>
                </div>

                <!-- Links 1 -->
                <div>
                    <h4 class="text-lg font-bold text-white mb-4">Acesso Rápido</h4>
                    <ul class="space-y-2 text-sm text-gray-300">
                        <li><a href="#" class="hover:text-white transition">Início</a></li>
                        <li><a href="#" class="hover:text-white transition">Unidades de Saúde</a></li>
                        <li><a href="#" class="hover:text-white transition">Lista de Medicamentos</a></li>
                        <li><a href="#" class="hover:text-white transition">Editais e Licitações</a></li>
                        <li><a href="#" class="hover:text-[var(--gov-accent)] transition">Acesso ao Servidor</a></li>
                    </ul>
                </div>

                <!-- Links 2 (Gov) -->
                <div>
                    <h4 class="text-lg font-bold text-white mb-4">Transparência</h4>
                    <ul class="space-y-2 text-sm text-gray-300">
                        <li><a href="#" class="hover:text-white transition">Portal da Transparência</a></li>
                        <li><a href="#" class="hover:text-white transition">Diário Oficial</a></li>
                        <li><a href="#" class="hover:text-white transition">Política de Privacidade (LGPD)</a></li>
                        <li><a href="#" class="hover:text-white transition">Dados Abertos</a></li>
                    </ul>
                </div>

                <!-- Contact -->
                <div>
                    <h4 class="text-lg font-bold text-white mb-4">Contato</h4>
                    <ul class="space-y-3 text-sm text-gray-300">
                        <li class="flex items-start">
                            <svg class="w-5 h-5 mr-2 shrink-0 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path></svg>
                            <span>Av. Brasil, 000 - Centro<br>Assaí - PR, 86220-000</span>
                        </li>
                        <li class="flex items-center">
                            <svg class="w-5 h-5 mr-2 shrink-0 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"></path></svg>
                            <span>Telefone: (43) 3262-1000</span>
                        </li>
                        <li class="flex items-center">
                            <svg class="w-5 h-5 mr-2 shrink-0 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path></svg>
                            <span>saude@assai.pr.gov.br</span>
                        </li>
                    </ul>
                </div>
            </div>

            <div class="border-t border-gray-700 mt-10 pt-6 flex flex-col md:flex-row justify-between items-center text-xs text-gray-400">
                <p>&copy; 2026 Prefeitura Municipal de Assaí. Todos os direitos reservados.</p>
                <div class="mt-4 md:mt-0 flex space-x-4">
                    <span>Desenvolvido pela Divisão de TI</span>
                </div>
            </div>
        </div>
    </footer>
</body>
</html>
