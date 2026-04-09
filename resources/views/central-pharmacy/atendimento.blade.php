<x-app-layout>
    <x-slot name="header">
        <div class="sa-page-header">
            <h2 class="sa-page-title">Farmacia Central - Atendimento</h2>
            <p class="sa-page-subtitle">Fila de dispensacao validada na recepcao</p>
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
            <div class="sa-card-header"><h3 class="sa-card-title">Pendentes para Dispensacao</h3></div>
            <div class="overflow-x-auto">
                <table class="sa-table">
                    <thead>
                        <tr>
                            <th>Data Receita</th>
                            <th>Cidadao</th>
                            <th>Medicamento</th>
                            <th>Concentracao</th>
                            <th>Quantidade</th>
                            <th>Acoes</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($requests as $requestItem)
                            <tr>
                                <td>{{ $requestItem->prescription_date?->format('d/m/Y') ?? '—' }}</td>
                                <td>{{ $requestItem->citizen->full_name ?? '—' }}</td>
                                <td>{{ $requestItem->medication_name }}</td>
                                <td>{{ $requestItem->concentration ?? '—' }}</td>
                                <td>{{ $requestItem->quantity }}</td>
                                <td class="space-y-2 min-w-[20rem]">
                                    <form method="POST" action="{{ route('central-pharmacy.dispense', $requestItem) }}">
                                        @csrf
                                        <button type="submit" class="sa-btn-success">Dispensar</button>
                                    </form>

                                    <form method="POST" action="{{ route('central-pharmacy.refuse', $requestItem) }}" class="grid grid-cols-1 gap-2">
                                        @csrf
                                        <input type="text" name="refusal_reason" class="sa-input" placeholder="Motivo da recusa" required>
                                        <button type="submit" class="sa-btn-danger">Recusar com motivo</button>
                                    </form>

                                    <form method="POST" action="{{ route('central-pharmacy.dispense-equivalent', $requestItem) }}" class="grid grid-cols-1 gap-2">
                                        @csrf
                                        <input type="text" name="equivalent_medication_name" class="sa-input" placeholder="Medicamento equivalente" required>
                                        <input type="text" name="equivalent_concentration" class="sa-input" placeholder="Concentracao equivalente" required>
                                        <button type="submit" class="sa-btn-primary">Dispensar equivalente</button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="text-center text-gray-500 py-6">Nenhuma solicitacao pendente.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-app-layout>
