<x-app-layout>
    <x-slot name="header">
        <div class="sa-page-header">
            <h2 class="sa-page-title">Administracao - Relatorios Gerenciais</h2>
            <p class="sa-page-subtitle">Visao geral para gestao da equipe e operacao</p>
        </div>
    </x-slot>

    <div class="space-y-6">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div class="sa-card">
                <div class="sa-card-header"><h3 class="sa-card-title">Usuarios por Perfil</h3></div>
                <div class="overflow-x-auto">
                    <table class="sa-table">
                        <thead><tr><th>Perfil</th><th>Total</th></tr></thead>
                        <tbody>
                            @forelse($usersByRole as $row)
                                <tr><td>{{ $row->role }}</td><td>{{ $row->total }}</td></tr>
                            @empty
                                <tr><td colspan="2" class="text-center text-gray-500 py-6">Sem dados.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="sa-card">
                <div class="sa-card-header"><h3 class="sa-card-title">Atividade por Modulo</h3></div>
                <div class="overflow-x-auto">
                    <table class="sa-table">
                        <thead><tr><th>Modulo</th><th>Total Acoes</th></tr></thead>
                        <tbody>
                            @forelse($activityByModule as $row)
                                <tr><td>{{ $row->module }}</td><td>{{ $row->total }}</td></tr>
                            @empty
                                <tr><td colspan="2" class="text-center text-gray-500 py-6">Sem dados.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="sa-card">
            <div class="sa-card-header"><h3 class="sa-card-title">Auditoria Recente</h3></div>
            <div class="overflow-x-auto">
                <table class="sa-table">
                    <thead><tr><th>Data</th><th>Perfil</th><th>Modulo</th><th>Ação</th><th>IP</th></tr></thead>
                    <tbody>
                        @forelse($recentAudits as $log)
                            <tr>
                                <td>{{ \Carbon\Carbon::parse($log->created_at)->format('d/m/Y H:i:s') }}</td>
                                <td>{{ $log->profile }}</td>
                                <td>{{ $log->module }}</td>
                                <td>{{ $log->action }}</td>
                                <td>{{ $log->ip_address }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="text-center text-gray-500 py-6">Sem registros.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-app-layout>
