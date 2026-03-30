<x-app-layout>
    <x-slot name="header">
        <div class="sa-page-header">
            <h2 class="sa-page-title">Farmácia Central - ATENDIMENTO</h2>
            <p class="sa-page-subtitle">Área exclusiva de dispensação</p>
        </div>
    </x-slot>

    <div class="space-y-6">
        @if ($errors->any())
            <div class="bg-red-50 border-l-4 border-red-500 p-4">
                <ul class="text-sm text-red-700 list-disc list-inside">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        @if (session('status'))
            <div class="sa-alert-success"><span class="text-sm font-medium">{{ session('status') }}</span></div>
        @endif

        <div class="sa-card">
            <div class="sa-card-header"><h3 class="sa-card-title">Fila de Dispensação</h3></div>
            <div class="overflow-x-auto">
                <table class="sa-table">
                    <thead><tr><th>Cidadão</th><th>Medicação</th><th>Qtd</th><th>Ação</th></tr></thead>
                    <tbody>
                        @forelse($requests as $row)
                            <tr>
                                <td>{{ $row->citizen->full_name ?? '—' }}</td>
                                <td>{{ $row->medication_name }}</td>
                                <td>{{ $row->quantity }}</td>
                                <td>
                                    <form method="POST" action="{{ route('central-pharmacy.dispense', $row) }}">
                                        @csrf
                                        <button type="submit" class="sa-btn-success">Dispensar</button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="text-center text-gray-500 py-6">Nenhuma solicitação pendente.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-app-layout>
