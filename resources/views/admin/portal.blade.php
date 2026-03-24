<x-app-layout>
    <x-slot name="header">
        <div class="sa-page-header">
            <h2 class="sa-page-title">Gerenciamento do Portal</h2>
            <p class="sa-page-subtitle">Cadastro de notícias, avisos e alertas exibidos no portal público</p>
        </div>
    </x-slot>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="lg:col-span-1 space-y-6">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900 border-b border-gray-200">
                    <h3 class="text-lg font-bold mb-4">Novo Conteúdo</h3>

                    @if(session('status'))
                        <div class="mb-4 bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded flex items-center gap-2">
                            <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            <span class="text-sm font-medium">{{ session('status') }}</span>
                        </div>
                    @endif
                    
                    @if($errors->any())
                        <div class="mb-4 bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded">
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
                            <label class="block text-sm font-medium text-gray-700 mb-1">Título *</label>
                            <input name="title" type="text" class="w-full border-gray-300 focus:border-assai-primary focus:ring-assai-primary rounded-md shadow-sm" placeholder="Título da notícia" value="{{ old('title') }}" required>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Tipo de Conteúdo *</label>
                            <select name="type" class="w-full border-gray-300 focus:border-assai-primary focus:ring-assai-primary rounded-md shadow-sm" required>
                                <option value="">Selecione...</option>
                                <option value="Notícia">Notícia</option>
                                <option value="Aviso">Aviso</option>
                                <option value="Alerta">Alerta</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Corpo/Breve Descrição</label>
                            <textarea name="body" class="w-full border-gray-300 focus:border-assai-primary focus:ring-assai-primary rounded-md shadow-sm" rows="4" placeholder="Detalhes...">{{ old('body') }}</textarea>
                        </div>

                        <button type="submit" class="w-full inline-flex justify-center items-center px-4 py-2 bg-assai-primary border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-assai-secondary focus:bg-assai-secondary active:bg-assai-primary focus:outline-none focus:ring-2 focus:ring-assai-primary focus:ring-offset-2 transition ease-in-out duration-150">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
                            Publicar Conteúdo
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <div class="lg:col-span-2 space-y-6">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 pb-0">
                    <div class="flex justify-between items-center mb-6">
                        <h3 class="text-lg font-bold text-gray-900">Conteúdos Publicados</h3>
                        <span class="bg-gray-100 text-gray-700 py-1 px-3 rounded-full text-xs font-bold">{{ count($contents) }}</span>
                    </div>
                </div>
                
                <div class="overflow-x-auto pb-6 px-6">
                    <table class="w-full whitespace-nowrap text-left text-sm">
                        <thead class="bg-gray-50 text-gray-500 rounded-lg">
                            <tr>
                                <th class="px-4 py-3 font-semibold rounded-l-lg">Título</th>
                                <th class="px-4 py-3 font-semibold">Tipo</th>
                                <th class="px-4 py-3 font-semibold">Data</th>
                                <th class="px-4 py-3 font-semibold text-right rounded-r-lg">Ações</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @forelse($contents as $content)
                                <tr class="hover:bg-gray-50 transition-colors">
                                    <td class="px-4 py-4 font-medium text-gray-900 max-w-[200px] truncate" title="{{ $content->title }}">{{ $content->title }}</td>
                                    <td class="px-4 py-4">
                                        @if(str_contains(strtolower($content->type), 'alerta'))
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                                {{ $content->type }}
                                            </span>
                                        @elseif(str_contains(strtolower($content->type), 'aviso'))
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-orange-100 text-orange-800">
                                                {{ $content->type }}
                                            </span>
                                        @else
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                                {{ $content->type }}
                                            </span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-4 text-gray-500">
                                        {{ $content->published_at ? $content->published_at->format('d/m/Y H:i') : '-' }}
                                    </td>
                                    <td class="px-4 py-4 text-right">
                                        <div class="flex space-x-3 justify-end items-center">
                                            <a href="{{ route('admin.portal.edit', $content) }}" class="text-blue-600 hover:text-blue-900 font-medium">Editar</a>
                                            <form method="POST" action="{{ route('admin.portal.destroy', $content) }}" class="inline m-0 p-0" onsubmit="return confirm('Certeza que deseja remover?')">
                                                @csrf
                                                <button type="submit" class="text-red-500 hover:text-red-700 font-medium focus:outline-none">Remover</button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="px-4 py-8 text-center text-gray-400">Nenhum conteúdo publicado.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
