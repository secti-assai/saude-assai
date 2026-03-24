<x-app-layout>
    <x-slot name="header">
        <div class="sa-page-header">
            <h2 class="sa-page-title">{{ isset($healthUnit) ? 'Editar Unidade' : 'Nova Unidade' }}</h2>
            <p class="sa-page-subtitle">Preencha os dados abaixo.</p>
        </div>
    </x-slot>

    <div class="sa-card sa-fade-in max-w-2xl">
        <div class="sa-card-header">
            <h3 class="sa-card-title">Dados da Unidade</h3>
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

        <form method="POST" action="{{ isset($healthUnit) ? route('admin.health-units.update', $healthUnit) : route('admin.health-units.store') }}" enctype="multipart/form-data" class="space-y-4 p-4">
            @csrf
            @if(isset($healthUnit))
                @method('PUT')
            @endif

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="sa-label">Nome *</label>
                    <input name="name" type="text" class="sa-input" value="{{ old('name', $healthUnit->name ?? '') }}" required>
                </div>
                <div>
                    <label class="sa-label">Tipo (UBS, Hospital, etc) *</label>
                    <input name="kind" type="text" class="sa-input" value="{{ old('kind', $healthUnit->kind ?? '') }}" required>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="sa-label">Código (Ex: UBS-01) *</label>
                    <input name="code" type="text" class="sa-input" value="{{ old('code', $healthUnit->code ?? '') }}" required>
                </div>
                <div>
                    <label class="sa-label">Telefone</label>
                    <input name="phone" type="text" class="sa-input" value="{{ old('phone', $healthUnit->phone ?? '') }}">
                </div>
            </div>

            <div>
                <label class="sa-label">Endereço *</label>
                <input name="address" type="text" class="sa-input" value="{{ old('address', $healthUnit->address ?? '') }}" required>
            </div>

            <div>
                <label class="sa-label">Link do Google Maps</label>
                <input name="maps_link" type="url" class="sa-input" placeholder="https://maps.app.goo.gl/..." value="{{ old('maps_link', $healthUnit->maps_link ?? '') }}">
            </div>

            <div>
                <label class="sa-label">Descrição (Exibida no portal)</label>
                <textarea name="description" class="sa-input" rows="4">{{ old('description', $healthUnit->description ?? '') }}</textarea>
            </div>

            <div>
                <label class="sa-label">Foto da Unidade (JPEG, PNG)</label>
                @if(isset($healthUnit) && $healthUnit->photo_path)
                    <div class="mb-2">
                        <img src="{{ Storage::url($healthUnit->photo_path) }}" alt="Foto" class="h-32 rounded object-cover">
                    </div>
                @endif
                <input name="photo" type="file" accept="image/*" class="w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100 p-2 border border-gray-300 rounded">
            </div>

            <div class="flex items-center mt-4">
                <input type="checkbox" name="is_active" id="is_active" value="1" class="rounded border-gray-300 text-blue-600 shadow-sm focus:ring-blue-500" {{ old('is_active', $healthUnit->is_active ?? true) ? 'checked' : '' }}>
                <label for="is_active" class="ml-2 text-sm text-gray-600">Unidade Ativa</label>
            </div>

            <div class="pt-4 flex justify-end">
                <a href="{{ route('admin.health-units.index') }}" class="sa-btn-secondary mr-2">Cancelar</a>
                <button type="submit" class="sa-btn-primary">Salvar Unidade</button>
            </div>
        </form>
    </div>
</x-app-layout>
