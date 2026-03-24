<x-app-layout>
    <!-- Header/Breadcrumb -->
    <div class="bg-[var(--gov-primary)] pt-12 pb-6 border-b-4 border-[var(--gov-accent)]">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <nav class="flex text-white/80 text-sm mb-4" aria-label="Breadcrumb">
                <ol class="inline-flex items-center space-x-1 md:space-x-3">
                    <li class="inline-flex items-center">
                        <a href="{{ route('portal.home') }}" class="inline-flex items-center hover:text-white transition-colors">
                            <svg class="w-4 h-4 mr-2" fill="currentColor" viewBox="0 0 20 20"><path d="M10.707 2.293a1 1 0 00-1.414 0l-7 7a1 1 0 001.414 1.414L4 10.414V17a1 1 0 001 1h2a1 1 0 001-1v-2a1 1 0 011-1h2a1 1 0 011 1v2a1 1 0 001 1h2a1 1 0 001-1v-6.586l.293.293a1 1 0 001.414-1.414l-7-7z"></path></svg>
                            Início
                        </a>
                    </li>
                    <li aria-current="page">
                        <div class="flex items-center">
                            <svg class="w-6 h-6 text-white/60" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd"></path></svg>
                            <span class="ml-1 md:ml-2 text-white font-medium">Todas as Notícias e Campanhas</span>
                        </div>
                    </li>
                </ol>
            </nav>
            <h1 class="text-3xl md:text-4xl font-extrabold text-white font-sora tracking-tight">Notícias e Campanhas</h1>
        </div>
    </div>

    <!-- Lista de Notícias -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            @forelse($news as $item)
                <a href="{{ route('portal.news.show', $item->id) }}" class="flex flex-col bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden hover:shadow-lg transition-all hover:-translate-y-1 group">
                    <div class="w-full h-48 overflow-hidden relative">
                        <img src="https://images.unsplash.com/photo-1584515933487-779824d29309?auto=format&fit=crop&w=800&q=80" alt="News Image" class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-700">
                    </div>
                    <div class="p-6 flex-1 flex flex-col justify-between">
                        <div>
                            <span class="text-[10px] font-bold uppercase tracking-wider text-[var(--gov-primary)] mb-2 block">
                                {{ $item->type ?? 'Notícia' }}
                            </span>
                            <h3 class="text-xl font-bold font-sora text-gray-800 leading-snug line-clamp-2 mb-3 group-hover:text-[var(--gov-primary)] transition-colors">
                                {{ $item->title }}
                            </h3>
                            <p class="text-gray-600 text-sm line-clamp-3 mb-4">
                                {{ \Illuminate\Support\Str::limit($item->body ?? '', 120) }}
                            </p>
                        </div>
                        <div class="text-xs text-gray-500 flex items-center mt-auto">
                            <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                            {{ optional($item->published_at)->format('d/m/Y') ?? 'Recente' }}
                        </div>
                    </div>
                </a>
            @empty
                <div class="col-span-full py-12 text-center text-gray-500 bg-gray-50 rounded-xl border border-dashed border-gray-300">
                    <p class="text-lg">Nenhuma notícia encontrada no momento.</p>
                </div>
            @endforelse
        </div>

        <div class="mt-12">
            {{ $news->links() }}
        </div>
    </div>
</x-app-layout>