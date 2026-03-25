<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $news->title }} - Saúde Assaí</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@300;400;500;600;700&family=Sora:wght@600;700;800&display=swap" rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    
    <style>
        :root {
            --gov-primary: #005a50;
            --gov-primary-dark: #00453d;
            --gov-secondary: #f0f7f6;
            --gov-accent: #ffb81c;
            --gov-text: #2d3748;
        }
        body { font-family: 'Manrope', sans-serif; }
        h1, h2, h3, h4, h5, h6, .font-sora { font-family: 'Sora', sans-serif; }

        /* CSS para redimensionar as imagens que vêm do editor Trix */
        .prose [data-trix-attachment] {
            width: 100% !important;
            max-width: 400px !important;
            margin: 2rem auto !important;
            display: block !important;
        }
        .prose [data-trix-attachment] img {
            width: 100% !important;
            height: auto !important;
            border-radius: 8px !important;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1) !important;
        }
        .prose [data-trix-attachment] figcaption {
            display: none !important;
        }
    </style>
</head>
<body class="antialiased text-gray-900 bg-gray-50 flex flex-col min-h-screen">

    <header class="bg-white shadow-sm sticky top-0 z-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-16 md:h-20 flex items-center justify-between">
            <div class="flex items-center gap-3">
                <svg class="w-8 h-8 md:w-10 md:h-10 text-[var(--gov-primary)]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                </svg>
                <div>
                    <h1 class="font-bold text-xl md:text-2xl font-sora text-[var(--gov-primary)] leading-none">Saúde Assaí</h1>
                    <span class="hidden sm:block text-[10px] md:text-xs font-semibold text-gray-500 uppercase tracking-widest">Portal do Cidadão</span>
                </div>
            </div>

            <nav class="hidden md:flex space-x-8">
                <a href="{{ route('portal.home') }}" class="text-sm font-medium text-gray-700 hover:text-[var(--gov-primary)] transition-colors">Início</a>
                <a href="{{ route('portal.news.index') }}" class="text-sm font-medium text-gray-700 hover:text-[var(--gov-primary)] transition-colors">Notícias</a>
                <a href="{{ route('portal.units') }}" class="text-sm font-medium text-gray-700 hover:text-[var(--gov-primary)] transition-colors">Unidades</a>
            </nav>

            <div class="flex items-center">
                @auth
                    <a href="{{ url('/dashboard') }}" class="text-sm font-medium text-[var(--gov-primary)] hover:underline">Acessar Painel</a>
                @else
                    <a href="{{ route('login') }}" class="inline-flex items-center justify-center px-4 py-2 text-sm font-semibold text-white bg-[var(--gov-primary)] hover:bg-[var(--gov-primary-dark)] rounded-md transition-colors shadow-sm">
                        Acesso Profissional
                    </a>
                @endauth
            </div>
        </div>
    </header>

    <div class="bg-[var(--gov-primary)] border-b-4 border-[var(--gov-accent)]" style="padding-top: 5rem; padding-bottom: 7rem;">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <nav class="flex text-white/80 text-sm mb-6" aria-label="Breadcrumb">
                <ol class="inline-flex items-center space-x-2">
                    <li class="inline-flex items-center">
                        <a href="{{ route('portal.home') }}" class="inline-flex items-center hover:text-white transition-colors">
                            <svg class="w-4 h-4 mr-2" fill="currentColor" viewBox="0 0 20 20"><path d="M10.707 2.293a1 1 0 00-1.414 0l-7 7a1 1 0 001.414 1.414L4 10.414V17a1 1 0 001 1h2a1 1 0 011-1h2a1 1 0 011 1v2a1 1 0 001 1h2a1 1 0 001-1v-6.586l.293.293a1 1 0 001.414-1.414l-7-7z"></path></svg>
                            Início
                        </a>
                    </li>
                    <li>
                        <div class="flex items-center">
                            <svg class="w-5 h-5 text-white/60 mx-1" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd"></path></svg>
                            <a href="{{ route('portal.news.index') }}" class="hover:text-white transition-colors font-medium">Notícias e Campanhas</a>
                        </div>
                    </li>
                    <li>
                        <div class="flex items-center">
                            <svg class="w-5 h-5 text-white/60 mx-1" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd"></path></svg>
                            <span class="text-white font-medium">{{ $news->type }}</span>
                        </div>
                    </li>
                </ol>
            </nav>
            <h1 class="text-3xl md:text-5xl font-extrabold text-white font-sora tracking-tight leading-tight max-w-4xl">
                {{ $news->title }}
            </h1>
            <div class="mt-4 flex items-center text-[var(--gov-secondary)] text-sm font-medium">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                Publicado em {{ $news->published_at ? $news->published_at->format('d/m/Y \à\s H:i') : $news->created_at->format('d/m/Y') }}
            </div>
        </div>
    </div>

    <main class="flex-grow max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-12 -mt-12 relative z-10">
        <article class="bg-white rounded-2xl shadow-xl border border-gray-100 overflow-hidden">
            @if($news->cover_image)
                <div class="w-full h-64 md:h-96 overflow-hidden">
                    <img src="{{ Storage::url($news->cover_image) }}" alt="{{ $news->title }}" class="w-full h-full object-cover">
                </div>
            @endif

            <div class="p-8 md:p-12">
                <div class="prose prose-lg max-w-none text-gray-700 leading-relaxed font-serif">
                    {!! $news->body !!}
                </div>
                
                <div class="mt-12 pt-8 border-t border-gray-100 flex flex-col sm:flex-row sm:justify-between items-center gap-4">
                    <a href="{{ route('portal.news.index') }}" class="inline-flex items-center text-[var(--gov-primary)] font-bold hover:text-[var(--gov-primary-dark)] transition-colors">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                        Voltar para todas as Notícias
                    </a>
                    
                    <button onclick="navigator.clipboard.writeText(window.location.href); alert('Link copiado!');" class="inline-flex items-center px-4 py-2 bg-gray-50 hover:bg-gray-100 text-gray-700 text-sm font-medium rounded-lg border border-gray-200 transition-colors w-full sm:w-auto justify-center">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m0 2.684l6.632 3.316m-6.632-6l6.632-3.316m0 0a3 3 0 105.367-2.684 3 3 0 00-5.367 2.684zm0 9.316a3 3 0 105.368 2.684 3 3 0 00-5.368-2.684z"></path></svg>
                        Compartilhar Notícia
                    </button>
                </div>
            </div>
        </article>
        
        @if($otherNews->isNotEmpty())
            <div class="mt-16">
                <h3 class="text-2xl font-bold font-sora text-[var(--gov-primary-dark)] mb-6">Últimas Notícias</h3>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    @foreach($otherNews as $item)
                        <a href="{{ route('portal.news.show', $item->id) }}" class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden hover:shadow-lg hover:-translate-y-1 transition-all group flex flex-col">
                            <div class="h-32 w-full bg-gray-100 overflow-hidden">
                                <img src="{{ $item->cover_image ? Storage::url($item->cover_image) : 'https://images.unsplash.com/photo-1584515933487-779824d29309?auto=format&fit=crop&w=400&q=80' }}" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500">
                            </div>
                            <div class="p-5 flex flex-col flex-grow">
                                <span class="text-[10px] font-bold uppercase tracking-wider text-[var(--gov-primary)] mb-2 block">
                                    {{ $item->type }}
                                </span>
                                <h4 class="font-bold text-gray-900 mb-2 group-hover:text-[var(--gov-primary)] transition-colors line-clamp-2 leading-tight">{{ $item->title }}</h4>
                                <div class="mt-auto pt-4 flex items-center text-xs text-gray-400">
                                    <svg class="w-3.5 h-3.5 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                    {{ optional($item->published_at)->format('d/m/Y') }}
                                </div>
                            </div>
                        </a>
                    @endforeach
                </div>
            </div>
        @endif
    </main>

    <footer class="bg-[var(--gov-primary-dark)] text-white mt-auto py-12 border-t-[6px] border-[var(--gov-accent)]">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-8">
                <div class="col-span-1 md:col-span-2">
                    <div class="flex items-center gap-3 mb-4">
                        <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                        </svg>
                        <div>
                            <h2 class="font-bold text-xl font-sora text-white">Saúde Assaí</h2>
                        </div>
                    </div>
                    <p class="text-sm text-gray-300 leading-relaxed max-w-sm">Secretaria Municipal da Saúde<br>Prefeitura Municipal de Assaí - PR</p>
                </div>
                <div>
                    <h3 class="font-bold text-lg font-sora mb-4 text-white">Atendimento</h3>
                    <ul class="space-y-2 text-sm text-gray-300">
                        <li>(43) 3262-1000</li>
                        <li>saude@assai.pr.gov.br</li>
                        <li>Av. Brasil, 000 - Centro</li>
                    </ul>
                </div>
                <div>
                    <h3 class="font-bold text-lg font-sora mb-4 text-white">Links Rápidos</h3>
                    <ul class="space-y-2 text-sm text-gray-300">
                        <li><a href="#" class="hover:text-[var(--gov-accent)] transition-colors">Ouvidoria</a></li>
                        <li><a href="#" class="hover:text-[var(--gov-accent)] transition-colors">Portal da Transparência</a></li>
                    </ul>
                </div>
            </div>
            <div class="border-t border-white/20 mt-12 pt-8 flex flex-col md:flex-row justify-between items-center text-xs text-gray-400">
                <p>© {{ date('Y') }} Prefeitura Municipal de Assaí. Todos os direitos reservados.</p>
                <p class="mt-2 md:mt-0">Desenvolvido pela Divisão de TI</p>
            </div>
        </div>
    </footer>
</body>
</html>