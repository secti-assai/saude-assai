<x-app-layout>
    <x-slot name="header">
        <div class="sa-page-header">
            <h2 class="sa-page-title">Gerenciamento do Portal</h2>
            <p class="sa-page-subtitle">Cadastro de notÃcias, avisos e alertas exibidos no portal pÃºblico</p>
        </div>
    </x-slot>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="lg:col-span-1 space-y-6">
            <div class="sa-card sa-fade-in">
                <div class="sa-card-header">
                    <h3 class="sa-card-title">Novo ConteÃºdo</h3>
                </div>

                @if(session('status'))
                    <div class="sa-alert-success mb-4">
                        <svg class="w-5 h-5 text-green-600 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        <span class="text-sm font-medium">{{ session('status') }}</span>
                    </div>
                @endif
                @if($errors->any())
                    <div class="mb-4 bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded m-2">
                        <ul class="list-disc pl-4 text-sm mt-1">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <form method="POST" action="{{ route('admin.portal.store') }}" class="space-y-4">
                    @csrf
                    <div>
                        <label class="sa-label">TÃtulo *</label>
                        <input name="title" type="text" class="sa-input" placeholder="TÃtulo da notÃcia" value="{{ old('title') }}" required>
                    </div>

                    <div>
                        <label class="sa-label">Tipo de ConteÃºdo *</label>
                        <select name="type" class="sa-input" required>
                            <option value="">Selecione...</option>
                            <option value="NotÃcia">NotÃcia</option>
                            <option value="Aviso">Aviso</option>
                            <option value="Alerta">Alerta</option>
                        </select>
                    </div>

                    <div>
                        <label class="sa-label">Corpo/Breve DescriÃ§Ã£o</label>
                        <textarea name="body" class="sa-input" rows="4" placeholder="Detalhes...">{{ old('body') }}</textarea>
                    </div>

                    <button type="submit" class="sa-btn-primary w-full justify-center">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
                        Publicar ConteÃºdo
                    </button>
                </form>
            </div>
        </div>

        <div class="lg:col-span-2 space-y-6">
            <div class="sa-card sa-fade-in" style="animation-delay: 0.1s;">
                <div class="sa-card-header">
                    <h3 class="sa-card-title">ConteÃºdos Publicados</h3>
                    <span class="sa-badge sa-badge-gray">{{ count($contents) }}</span>
                </div>
                <div class="overflow-x-auto">
                    <table class="sa-table">
                        <thead>
                            <tr>
                                <th>TÃtulo</th>
                                <th>Tipo</th>
                                <th>Data</th>
                                <th class="text-right">AÃ§Ãµes</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($contents as $content)
                                <tr>
                                    <td class="font-medium text-gray-900 max-w-[200px] truncate" title="{{ $content->title }}">{{ $content->title }}</td>
                                    <td>
                                        @if(str_contains(strtolower($content->type), 'alerta'))
                                            <span class="sa-badge sa-badge-red">{{ $content->type }}</span>
                                        @elseif(str_contains(strtolower($content->type), 'aviso'))
                                            <span class="sa-badge sa-badge-orange">{{ $content->type }}</span>
                                        @else
                                            <span class="sa-badge sa-badge-blue">{{ $content->type }}</span>
                                        @endif
                                    </td>
                                    <td class="text-gray-500">{{ $content->published_at ? $content->published_at->format('d/m/Y H:i') : '-' }}</td>
                                    <td class="text-right">
                                        <form method="POST" action="{{ route('admin.portal.destroy', $content) }}" onsubmit="return confirm('Certeza que deseja remover?')">
                                            @csrf
                                            <button type="submit" class="text-red-500 hover:text-red-700 text-sm font-medium focus:outline-none">Remover</button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="text-center text-gray-400 py-8">Nenhum conteÃºdo.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
