<x-app-layout>
    <x-slot name="header">
        <div class="sa-page-header">
            <h2 class="sa-page-title">Editar Conteúdo do Portal</h2>
            <p class="sa-page-subtitle">Modificando o conteúdo selecionado.</p>
        </div>
    </x-slot>

    <div class="sa-card sa-fade-in max-w-3xl">
        <div class="sa-card-header">
            <h3 class="sa-card-title">Dados do Conteúdo</h3>
        </div>

        @if($errors->any())
            <div class="mb-4 bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded m-2">
                <ul class="list-disc pl-4 text-sm mt-1">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ route('admin.portal.update', $content) }}" class="space-y-4 p-4">
            @csrf
            @method('PUT')
            
            <div>
                <label class="sa-label">Título *</label>
                <input name="title" type="text" class="sa-input" value="{{ old('title', $content->title) }}" required>
            </div>

            <div>
                <label class="sa-label">Tipo de Conteúdo *</label>
                <select name="type" class="sa-input" required>
                    <option value="Notícia" {{ $content->type == 'Notícia' ? 'selected' : '' }}>Notícia</option>
                    <option value="Aviso" {{ $content->type == 'Aviso' ? 'selected' : '' }}>Aviso</option>
                    <option value="Alerta" {{ $content->type == 'Alerta' ? 'selected' : '' }}>Alerta</option>
                </select>
            </div>

            <div>
                <label class="sa-label">Corpo/Descrição * (Exibido integralmente em notícias ou como subtítulo em Alertas)</label>
                <textarea name="body" class="sa-input" rows="8" required>{{ old('body', $content->body) }}</textarea>
                <p class="text-xs text-gray-500 mt-1">Dica: Use parágrafos normais, a exibição da notícia irá respeitar as quebras de linha.</p>
            </div>

            <div class="flex items-center mt-4">
                <input type="checkbox" name="published" id="published" value="1" class="rounded border-gray-300 text-blue-600 shadow-sm focus:ring-blue-500" {{ old('published', $content->published) ? 'checked' : '' }}>
                <label for="published" class="ml-2 text-sm text-gray-600">Conteúdo Publicado / Visível no site</label>
            </div>

            <div class="pt-4 flex justify-end">
                <a href="{{ route('admin.portal') }}" class="sa-btn-secondary mr-2">Cancelar</a>
                <button type="submit" class="sa-btn-primary">Atualizar Conteúdo</button>
            </div>
        </form>
    </div>
</x-app-layout>