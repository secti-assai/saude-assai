<x-app-layout>
    <x-slot name="header">
        <div class="sa-page-header">
            <h2 class="sa-page-title">Dashboard</h2>
            <p class="sa-page-subtitle">Visão geral da rede municipal de saúde</p>
        </div>
    </x-slot>

    <div class="space-y-6">
        {{-- KPI Cards --}}
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            <div class="sa-kpi sa-fade-in">
                <div class="sa-kpi-label">Atendimentos</div>
                <div class="sa-kpi-value" style="color: var(--sa-primary);">{{ $stats['atendimentos'] ?? 0 }}</div>
            </div>
            <div class="sa-kpi sa-fade-in">
                <div class="sa-kpi-label">Triagens</div>
                <div class="sa-kpi-value" style="color: var(--sa-info);">{{ $stats['triagens'] ?? 0 }}</div>
            </div>
            <div class="sa-kpi sa-fade-in">
                <div class="sa-kpi-label">Prescrições</div>
                <div class="sa-kpi-value" style="color: var(--sa-accent);">{{ $stats['prescricoes'] ?? 0 }}</div>
            </div>
            <div class="sa-kpi sa-fade-in">
                <div class="sa-kpi-label">Dispensações</div>
                <div class="sa-kpi-value" style="color: var(--sa-success);">{{ $stats['dispensacoes'] ?? 0 }}</div>
            </div>
        </div>

        {{-- Usage Table --}}
        <div class="sa-card sa-fade-in">
            <div class="sa-card-header">
                <h3 class="sa-card-title">Uso por Servidor</h3>
                <span class="sa-badge sa-badge-gray">{{ count($usage) }} servidores</span>
            </div>
            <div class="overflow-x-auto">
                <table class="sa-table">
                    <thead>
                        <tr>
                            <th>Servidor</th>
                            <th>Perfil</th>
                            <th class="text-center">Ações</th>
                            <th>Último Acesso</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($usage as $row)
                            <tr>
                                <td class="font-medium text-gray-900">{{ $row['name'] }}</td>
                                <td>
                                    <span class="sa-badge sa-badge-primary">{{ str_replace('_', ' ', $row['role']) }}</span>
                                </td>
                                <td class="text-center font-semibold">{{ $row['actions'] }}</td>
                                <td class="text-gray-500 text-xs">{{ $row['last'] ?? '—' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="text-center text-gray-400 py-8">Nenhum dado registrado ainda.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-app-layout>
