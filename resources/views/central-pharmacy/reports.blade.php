<x-app-layout>
    <x-slot name="header">
        <div class="sa-page-header">
            <h2 class="sa-page-title">Farmácia - Relatórios</h2>
            <p class="sa-page-subtitle">Visão gerencial simplificada para acompanhamento das dispensações</p>
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

        <div class="sa-card">
            <div class="sa-card-header">
                <h3 class="sa-card-title">Filtros do Relatório</h3>
            </div>
            @php
                $selectedStatus = $filters['status'] ?? 'TODOS';
                $csvFilters = [
                    'date_start' => $filters['date_start'] ?? null,
                    'date_end' => $filters['date_end'] ?? null,
                    'status' => $filters['status'] ?? null,
                    'citizen_name' => $filters['citizen_name'] ?? null,
                ];
            @endphp
            <form method="GET" action="{{ route('central-pharmacy.reports') }}" class="grid grid-cols-1 md:grid-cols-4 gap-3 items-end">
                <div>
                    <label class="sa-label">Data inicial</label>
                    <input type="date" name="date_start" class="sa-input" value="{{ $filters['date_start'] ?? now()->subDays(30)->toDateString() }}">
                </div>
                <div>
                    <label class="sa-label">Data final</label>
                    <input type="date" name="date_end" class="sa-input" value="{{ $filters['date_end'] ?? now()->toDateString() }}">
                </div>
                <div>
                    <label class="sa-label">Status</label>
                    <select name="status" class="sa-select">
                        <option value="TODOS" {{ $selectedStatus === 'TODOS' ? 'selected' : '' }}>Todos</option>
                        <option value="REGULARIZADO" {{ $selectedStatus === 'REGULARIZADO' ? 'selected' : '' }}>Regularizado</option>
                        <option value="NAO_REGULARIZADO" {{ $selectedStatus === 'NAO_REGULARIZADO' ? 'selected' : '' }}>Não Regularizado</option>
                    </select>
                </div>
                <div>
                    <label class="sa-label">Nome do cidadão</label>
                    <input type="text" name="citizen_name" class="sa-input" value="{{ $filters['citizen_name'] ?? '' }}" placeholder="Buscar por nome">
                </div>

                <div class="md:col-span-4 flex flex-wrap justify-end gap-2">
                    <a href="{{ route('central-pharmacy.reports.export-csv', $csvFilters) }}" class="sa-btn-secondary">Exportar CSV</a>
                    <a href="{{ route('central-pharmacy.reports.export-pdf', $csvFilters) }}" class="sa-btn-secondary">Exportar PDF</a>
                    <a href="{{ route('central-pharmacy.reports') }}" class="sa-btn-outline">Limpar</a>
                    <button type="submit" class="sa-btn-primary">Aplicar filtros</button>
                </div>
            </form>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div class="sa-card">
                <p class="text-xs uppercase tracking-wide text-gray-500">Dispensações (Período)</p>
                <p class="text-2xl font-bold text-gray-900">{{ $summary['total_events'] }}</p>
            </div>
            <div class="sa-card">
                <p class="text-xs uppercase tracking-wide text-gray-500">Regularizados</p>
                <p class="text-2xl font-bold text-emerald-700">{{ $summary['total_regular'] }}</p>
            </div>
            <div class="sa-card">
                <p class="text-xs uppercase tracking-wide text-gray-500">Não Regularizados</p>
                <p class="text-2xl font-bold text-red-700">{{ $summary['total_bypass'] }}</p>
            </div>
        </div>

        <div class="sa-card">
            <div class="sa-card-header">
                <h3 class="sa-card-title">Listagem Geral</h3>
                <span class="text-xs text-gray-500">Exibindo os dados compatíveis com importação da Betha.</span>
            </div>
            <div class="overflow-x-auto">
                <table class="sa-table">
                    <thead>
                        <tr>
                            <th>Horário da Dispensação</th>
                            <th>Cidadão</th>
                            <th>Situação</th>
                            <th>Medicamento Dispensado</th>
                            <th>Quantidade</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($rows as $row)
                            @php
                                $statusClass = $row->bypass_detected ? 'bg-red-100 text-red-700' : 'bg-emerald-100 text-emerald-700';
                                $statusLabel = $row->bypass_detected ? 'Não Regularizado' : 'Regularizado';
                                $citizenName = $row->citizen ? $row->citizen->full_name : $row->customer_name_raw;
                            @endphp
                            <tr>
                                <td>{{ $row->dispensed_at?->format('d/m/Y H:i') ?? 'N/A' }}</td>
                                <td class="font-bold">{{ $citizenName }}</td>
                                <td>
                                    <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-semibold {{ $statusClass }}">
                                        {{ $statusLabel }}
                                    </span>
                                </td>
                                <td>{{ $row->medication_name_raw ?? 'N/A' }}</td>
                                <td>{{ $row->quantity ?? '-' }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="text-center text-gray-500 py-6">Nenhum registro encontrado para os filtros informados.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-4">
                {{ $rows->links() }}
            </div>
        </div>
    </div>
</x-app-layout>
