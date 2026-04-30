<x-app-layout>
    <x-slot name="header">
        <div class="sa-page-header">
            <h2 class="sa-page-title">Administracao - Relatorios Gerenciais</h2>
            <p class="sa-page-subtitle">Visao geral para gestao da equipe e operacao</p>
        </div>
    </x-slot>

    <div class="space-y-6">
        <div class="sa-card">
            <div class="sa-card-header">
                <h3 class="sa-card-title">Importacao Externa Farmacia Central (BETHA + Usuario)</h3>
            </div>

            <form method="POST" action="{{ route('admin.pharmacy-import.store') }}" enctype="multipart/form-data" class="space-y-4">
                @csrf

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label for="betha_csv" class="block text-sm font-medium text-gray-700 mb-1">Arquivo BETHA (CSV)</label>
                        <input id="betha_csv" name="betha_csv" type="file" accept=".csv,.txt" class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                        @error('betha_csv')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="daily_txt" class="block text-sm font-medium text-gray-700 mb-1">Arquivo por Usuario (TXT)</label>
                        <input id="daily_txt" name="daily_txt" type="file" accept=".txt,.csv" class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                        @error('daily_txt')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <div class="flex justify-end">
                    <button type="submit" class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500">
                        Importar e Reconciliar
                    </button>
                </div>
            </form>

            @if(session('import_summary'))
                @php
                    $importResult = session('import_summary');
                    $importSummary = is_array($importResult) ? ($importResult['summary'] ?? []) : [];
                @endphp

                <div class="mt-6 grid grid-cols-1 md:grid-cols-4 gap-3">
                    <div class="rounded-lg bg-slate-50 border border-slate-200 p-3">
                        <p class="text-xs uppercase text-slate-500">Processados</p>
                        <p class="text-xl font-bold text-slate-900">{{ (int) ($importSummary['processed_rows'] ?? 0) }}</p>
                    </div>
                    <div class="rounded-lg bg-emerald-50 border border-emerald-200 p-3">
                        <p class="text-xs uppercase text-emerald-600">Atendimentos Criados</p>
                        <p class="text-xl font-bold text-emerald-700">{{ (int) ($importSummary['synthetic_created'] ?? 0) }}</p>
                    </div>
                    <div class="rounded-lg bg-amber-50 border border-amber-200 p-3">
                        <p class="text-xs uppercase text-amber-700">Bypass Detectados</p>
                        <p class="text-xl font-bold text-amber-800">{{ (int) ($importSummary['bypass_detected'] ?? 0) }}</p>
                    </div>
                    <div class="rounded-lg bg-rose-50 border border-rose-200 p-3">
                        <p class="text-xs uppercase text-rose-600">Bloqueios Acionados</p>
                        <p class="text-xl font-bold text-rose-700">{{ (int) ($importSummary['citizens_locked'] ?? 0) }}</p>
                    </div>
                </div>
            @endif
        </div>

        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div class="rounded-lg bg-white border border-gray-200 p-4">
                <p class="text-xs uppercase text-gray-500">Lotes Importados</p>
                <p class="text-2xl font-bold text-gray-900">{{ (int) ($externalImportTotals['imports'] ?? 0) }}</p>
            </div>
            <div class="rounded-lg bg-white border border-gray-200 p-4">
                <p class="text-xs uppercase text-gray-500">Registros de Bypass</p>
                <p class="text-2xl font-bold text-gray-900">{{ (int) ($externalImportTotals['bypass_rows'] ?? 0) }}</p>
            </div>
            <div class="rounded-lg bg-white border border-gray-200 p-4">
                <p class="text-xs uppercase text-gray-500">Alertas Altos</p>
                <p class="text-2xl font-bold text-rose-700">{{ (int) ($externalImportTotals['alerts_high'] ?? 0) }}</p>
            </div>
            <div class="rounded-lg bg-white border border-gray-200 p-4">
                <p class="text-xs uppercase text-gray-500">Alertas Medios</p>
                <p class="text-2xl font-bold text-amber-700">{{ (int) ($externalImportTotals['alerts_medium'] ?? 0) }}</p>
            </div>
        </div>

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
            <div class="sa-card-header"><h3 class="sa-card-title">Lotes Recentes de Importacao Externa</h3></div>
            <div class="overflow-x-auto">
                <table class="sa-table">
                    <thead>
                        <tr>
                            <th>Data</th>
                            <th>Usuario</th>
                            <th>Arquivo CSV</th>
                            <th>Arquivo TXT</th>
                            <th>Processados</th>
                            <th>Criados</th>
                            <th>Bypass</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($recentPharmacyImports as $import)
                            @php $stats = is_array($import->stats) ? $import->stats : []; @endphp
                            <tr>
                                <td>{{ optional($import->created_at)->format('d/m/Y H:i:s') }}</td>
                                <td>{{ $import->uploader->name ?? 'N/A' }}</td>
                                <td>{{ $import->betha_filename }}</td>
                                <td>{{ $import->daily_filename }}</td>
                                <td>{{ (int) ($stats['processed_rows'] ?? 0) }}</td>
                                <td>{{ (int) ($stats['synthetic_created'] ?? 0) }}</td>
                                <td>{{ (int) ($stats['bypass_detected'] ?? 0) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="7" class="text-center text-gray-500 py-6">Nenhum lote importado ainda.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="grid grid-cols-1 xl:grid-cols-2 gap-6">
            <div class="sa-card">
                <div class="sa-card-header"><h3 class="sa-card-title">Bypass Identificado (Recentes)</h3></div>
                <div class="overflow-x-auto">
                    <table class="sa-table">
                        <thead>
                            <tr>
                                <th>Data</th>
                                <th>Cidadao</th>
                                <th>Farmaceutica</th>
                                <th>Dispensa Externa</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($recentBypassRows as $row)
                                <tr>
                                    <td>{{ optional($row->dispensed_at)->format('d/m/Y H:i') }}</td>
                                    <td>{{ $row->citizen->full_name ?? $row->customer_name_raw }}</td>
                                    <td>{{ $row->pharmacistUser->name ?? $row->pharmacist_name_raw ?? 'N/A' }}</td>
                                    <td>{{ $row->external_dispense_number ?? 'N/A' }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="4" class="text-center text-gray-500 py-6">Sem registros de bypass.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="sa-card">
                <div class="sa-card-header"><h3 class="sa-card-title">Alertas de Recorrencia</h3></div>
                <div class="overflow-x-auto">
                    <table class="sa-table">
                        <thead>
                            <tr>
                                <th>Nivel</th>
                                <th>Cidadao</th>
                                <th>Intervalo (dias)</th>
                                <th>Dispensa</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($recurrenceAlerts as $row)
                                <tr>
                                    <td>
                                        @if($row->recurrence_alert_level === 'ALTO')
                                            <span class="px-2 py-1 rounded text-xs font-semibold bg-rose-100 text-rose-700">ALTO</span>
                                        @else
                                            <span class="px-2 py-1 rounded text-xs font-semibold bg-amber-100 text-amber-700">MEDIO</span>
                                        @endif
                                    </td>
                                    <td>{{ $row->citizen->full_name ?? $row->customer_name_raw }}</td>
                                    <td>{{ $row->recurrence_interval_days ?? 'N/A' }}</td>
                                    <td>{{ $row->external_dispense_number ?? 'N/A' }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="4" class="text-center text-gray-500 py-6">Sem alertas de recorrencia.</td></tr>
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
