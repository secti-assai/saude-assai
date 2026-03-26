<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Notícias e Campanhas - Saúde Assaí</title>

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
                <a href="{{ route('portal.news.index') }}" class="text-sm font-bold text-[var(--gov-primary)] transition-colors border-b-2 border-[var(--gov-primary)] pb-1">Notícias</a>
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
            <nav class="flex text-white/80 text-sm mb-4" aria-label="Breadcrumb">
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
                            <span class="text-white font-medium">Notícias e Campanhas</span>
                        </div>
                    </li>
                </ol>
            </nav>
            <h1 class="text-3xl md:text-4xl font-extrabold text-white font-sora tracking-tight">Todas as Notícias e Campanhas</h1>
            <p class="mt-2 text-[var(--gov-secondary)] max-w-2xl">Acompanhe as novidades, ações e comunicados oficiais da Secretaria Municipal de Saúde de Assaí.</p>
        </div>
    </div>

    <main class="flex-grow max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12 -mt-10">
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 relative z-10">
            {{-- O comando where('type', '!=', 'Alerta') bloqueia os alertas de aparecerem aqui --}}
            @forelse($news->where('type', '!=', 'Alerta') as $item)
                <a href="{{ route('portal.news.show', $item->id) }}" class="flex flex-col bg-white rounded-xl shadow-md border border-gray-100 overflow-hidden hover:shadow-xl transition-all hover:-translate-y-1 group">
                    <div class="w-full h-48 overflow-hidden relative bg-gray-100 flex items-center justify-center">
                        @if($item->cover_image)
                            <img src="{{ Storage::url($item->cover_image) }}" alt="Capa" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500">
                        @else
                            <svg class="w-12 h-12 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                        @endif
                    </div>
                    <div class="p-6 flex-1 flex flex-col justify-between">
                        <div>
                            <span class="text-[10px] font-bold uppercase tracking-wider text-[var(--gov-primary)] mb-2 block">
                                {{ $item->type ?? 'Notícia' }}
                            </span>
                            <h3 class="text-xl font-bold font-sora text-gray-900 leading-snug line-clamp-2 mb-3 group-hover:text-[var(--gov-primary)] transition-colors">
                                {{ $item->title }}
                            </h3>
                            <p class="text-gray-600 text-sm line-clamp-3 mb-4">
                                {{ $item->excerpt ?: \Illuminate\Support\Str::limit(strip_tags($item->body), 120) }}
                            </p>
                        </div>
                        <div class="text-xs text-gray-500 flex items-center mt-auto border-t border-gray-50 pt-4">
                            <svg class="w-4 h-4 mr-1.5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                            {{ optional($item->published_at)->format('d/m/Y') ?? 'Recente' }}
                        </div>
                    </div>
                </a>
            @empty
                <div class="col-span-full py-16 text-center text-gray-500 bg-white rounded-xl shadow-sm border border-gray-200">
                    <svg class="w-16 h-16 mx-auto text-gray-300 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 20H5a2 2 0 01-2-2V6a2 2 0 012-2h10a2 2 0 012 2v1m2 13a2 2 0 01-2-2V7m2 13a2 2 0 002-2V9.5a2.5 2.5 0 00-2.5-2.5H14"></path></svg>
                    <p class="text-lg font-medium text-gray-600">Nenhuma notícia publicada ainda.</p>
                    <p class="text-sm mt-1">Acompanhe nosso portal para futuras atualizações.</p>
                </div>
            @endforelse
        </div>

        <div class="mt-12">
            {{ $news->links() }}
        </div>
    </main>

    <footer class="bg-[var(--gov-primary-dark)] text-white mt-auto py-12 border-t-[6px] border-[var(--gov-accent)]">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-8">
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

                <div>
                    <h4 class="text-lg font-bold text-white mb-4">Acesso Rápido</h4>
                    <ul class="space-y-2 text-sm text-gray-300">
                        <li><a href="{{ route('portal.home') }}" class="hover:text-white transition">Início</a></li>
                        <li><a href="{{ route('portal.units') }}" class="hover:text-white transition">Unidades de Saúde</a></li>
                        <li><a href="#" class="hover:text-white transition">Lista de Medicamentos</a></li>
                        <li><a href="#" class="hover:text-white transition">Editais e Licitações</a></li>
                        <li><a href="{{ route('login') }}" class="hover:text-[var(--gov-accent)] transition">Acesso ao Servidor</a></li>
                    </ul>
                </div>

                <div>
                    <h4 class="text-lg font-bold text-white mb-4">Transparência</h4>
                    <ul class="space-y-2 text-sm text-gray-300">
                        <li><a href="#" class="hover:text-white transition">Portal da Transparência</a></li>
                        <li><a href="#" class="hover:text-white transition">Diário Oficial</a></li>
                        <li><a href="#" class="hover:text-white transition">Política de Privacidade (LGPD)</a></li>
                        <li><a href="#" class="hover:text-white transition">Dados Abertos</a></li>
                    </ul>
                </div>

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
                <p>© {{ date('Y') }} Prefeitura Municipal de Assaí. Todos os direitos reservados.</p>
                <div class="mt-4 md:mt-0 flex space-x-4">
                    <span>Desenvolvido pela Divisão de TI</span>
                </div>
            </div>
        </div>
    </footer>
</body>
</html>