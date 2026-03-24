<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center w-full">
            <div class="sa-page-header">
                <h2 class="sa-page-title">Unidades de Saúde</h2>
                <p class="sa-page-subtitle">Gerenciar informações e fotos das unidades.</p>
            </div>
            <a href="{{ route('admin.health-units.create') }}" class="sa-btn-primary">
                + Nova Unidade
            </a>
        </div>
    </x-slot>

    <div class="sa-card sa-fade-in">
        <div class="sa-card-header">
            <h3 class="sa-card-title">Unidades Cadastradas</h3>
        </div>

        @if(session('status'))
            <div class="sa-alert-success mb-4">
                <span class="text-sm font-medium">{{ session('status') }}</span>
            </div>
        @endif

        <div class="overflow-x-auto">
            <table class="sa-table">
                <thead>
                    <tr>
                        <th>Nome</th>
                        <th>Tipo</th>
                        <th>Status</th>
                        <th class="text-right">Ações</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($healthUnits as $unit)
                        <tr>
                            <td class="font-medium text-gray-900">{{ $unit->name }}</td>
                            <td>{{ $unit->kind }}</td>
                            <td>
                                @if($unit->is_active)
                                    <span class="sa-badge sa-badge-green">Ativo</span>
                                @else
                                    <span class="sa-badge sa-badge-gray">Inativo</span>
                                @endif
                            </td>
                            <td class="text-right space-x-2">
                                <a href="{{ route('admin.health-units.edit', $unit) }}" class="text-blue-600 hover:text-blue-800 text-sm font-medium">Editar</a>
                                <form method="POST" action="{{ route('admin.health-units.destroy', $unit) }}" class="inline" onsubmit="return confirm('Certeza que deseja remover?')">
                                    @csrf
                                    <!-- Use method DELETE if using actual Route::resource, but typical standard lets check routes -->
                                    @method('DELETE')
                                    <button type="submit" class="text-red-500 hover:text-red-700 text-sm font-medium">Remover</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="text-center text-gray-400 py-8">Nenhuma unidade cadastrada.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</x-app-layout>