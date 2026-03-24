<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $news->title }} - Saúde Assaí</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700&display=swap" rel="stylesheet" />

    <!-- Scripts -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="font-sans antialiased text-gray-900 bg-gray-50 flex flex-col min-h-screen">
    <!-- Header -->
    <header class="bg-white border-b border-gray-200 sticky top-0 z-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-16 flex items-center justify-between">
            <div class="flex items-center gap-3">
                <div class="bg-assai-primary p-2 rounded-lg text-white">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
                </div>
                <div>
                    <h1 class="font-bold text-xl leading-none text-gray-900">Saúde<span class="text-assai-primary">Assaí</span></h1>
                    <p class="text-[10px] uppercase font-bold tracking-wider text-gray-500">Portal do Cidadão</p>
                </div>
            </div>
            
            <nav class="hidden md:flex space-x-8">
                <a href="{{ route('portal.home') }}" class="text-sm font-medium text-gray-700 hover:text-assai-primary transition-colors">Início</a>
                <a href="{{ route('portal.units') }}" class="text-sm font-medium text-gray-700 hover:text-assai-primary transition-colors">Unidades</a>
                <a href="{{ route('portal.delivery') }}" class="text-sm font-medium text-gray-700 hover:text-assai-primary transition-colors">Remédio em Casa</a>
            </nav>

            <div class="flex items-center gap-3">
                @auth
                    <a href="{{ url('/dashboard') }}" class="text-sm font-medium text-assai-primary hover:underline">Acessar Painel</a>
                @else
                    <a href="{{ route('login') }}" class="px-4 py-2 text-sm font-medium text-white bg-assai-primary hover:bg-assai-secondary rounded-lg transition-colors shadow-sm">
                        Cidadão / Servidor
                    </a>
                @endauth
            </div>
        </div>
    </header>

    <main class="flex-grow max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
        <article class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="p-8 md:p-12">
                <div class="flex items-center space-x-2 text-sm text-gray-500 mb-6">
                    <a href="{{ route('portal.home') }}" class="hover:text-assai-primary cursor-pointer">Início</a>
                    <span>/</span>
                    <span class="text-assai-primary font-medium">{{ $news->type }}</span>
                </div>
                
                <h1 class="text-3xl md:text-4xl font-bold text-gray-900 mb-4 leading-tight">
                    {{ $news->title }}
                </h1>
                
                <div class="flex items-center text-sm text-gray-500 mb-10 pb-6 border-b border-gray-100">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                    Publicado em {{ $news->published_at ? $news->published_at->format('d/m/Y \à\s H:i') : $news->created_at->format('d/m/Y') }}
                </div>
                
                <div class="prose prose-lg max-w-none text-gray-700 leading-relaxed font-serif">
                    {!! nl2br(e($news->body)) !!}
                </div>
                
                <div class="mt-12 pt-8 border-t border-gray-100">
                    <a href="{{ route('portal.home') }}" class="inline-flex items-center text-assai-primary font-medium hover:text-assai-secondary transition-colors">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                        Voltar para o Início
                    </a>
                </div>
            </div>
        </article>
        
        @if($otherNews->isNotEmpty())
            <div class="mt-12">
                <h3 class="text-2xl font-bold text-gray-900 mb-6">Últimas Notícias</h3>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    @foreach($otherNews as $item)
                        <a href="{{ route('portal.news.show', $item->id) }}" class="bg-white rounded-xl shadow-sm border border-gray-100 p-5 hover:border-assai-primary hover:shadow-md transition-all group">
                            <span class="inline-block px-2.5 py-1 bg-gray-50 text-assai-primary text-xs font-semibold rounded-md mb-3">
                                {{ $item->type }}
                            </span>
                            <h4 class="font-bold text-gray-900 mb-2 group-hover:text-assai-primary transition-colors line-clamp-2">{{ $item->title }}</h4>
                            <p class="text-sm text-gray-500 line-clamp-2 mb-4">{{ \Illuminate\Support\Str::limit($item->body, 80) }}</p>
                            <span class="text-xs text-gray-400">{{ $item->published_at ? $item->published_at->format('d/m/Y') : '-' }}</span>
                        </a>
                    @endforeach
                </div>
            </div>
        @endif
    </main>

    <!-- Footer -->
    <footer class="bg-gray-900 text-white mt-auto">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-8">
                <div class="col-span-1 md:col-span-2">
                    <div class="flex items-center gap-3 mb-4">
                        <div class="bg-assai-primary p-2 rounded-lg text-white">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
                        </div>
                        <div>
                            <h2 class="font-bold text-xl text-white">Saúde<span class="text-assai-secondary">Assaí</span></h2>
                        </div>
                    </div>
                    <p class="text-sm text-gray-400 leading-relaxed max-w-sm">Sistema Integrado de Saúde Pública do Município de Assaí. Desenvolvido para facilitar o acesso à saúde pelo cidadão.</p>
                </div>
                <div>
                    <h3 class="font-bold text-lg mb-4">Atendimento</h3>
                    <ul class="space-y-2 text-sm text-gray-400">
                        <li>(43) 3262-XXXX</li>
                        <li>saude@assai.pr.gov.br</li>
                        <li>Rua Nome da Rua, 123 - Centro</li>
                    </ul>
                </div>
                <div>
                    <h3 class="font-bold text-lg mb-4">Links Rápidos</h3>
                    <ul class="space-y-2 text-sm text-gray-400">
                        <li><a href="#" class="hover:text-white transition-colors">Ouvidoria</a></li>
                        <li><a href="#" class="hover:text-white transition-colors">Transparência</a></li>
                        <li><a href="#" class="hover:text-white transition-colors">Termos de Uso</a></li>
                    </ul>
                </div>
            </div>
            <div class="border-t border-gray-800 mt-12 pt-8 flex flex-col md:flex-row justify-between items-center text-xs text-gray-500">
                <p>&copy; {{ date('Y') }} Prefeitura Municipal de Assaí. Todos os direitos reservados.</p>
                <p class="mt-2 md:mt-0">Desenvolvido pela SECTI (Secretaria de Ciência e Tecnologia de Inovação)</p>
            </div>
        </div>
    </footer>
</body>
</html>
