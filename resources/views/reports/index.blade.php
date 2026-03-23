<x-app-layout>
    <x-slot name="header">
        <div class="sa-page-header">
            <h2 class="sa-page-title">Relatórios</h2>
            <p class="sa-page-subtitle">Indicadores e dados epidemiológicos</p>
        </div>
    </x-slot>

    <div class="space-y-6">
        {{-- KPI Row --}}
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
            <div class="sa-kpi sa-fade-in">
                <div class="sa-kpi-label">Atendimentos</div>
                <div class="sa-kpi-value" style="color: var(--sa-primary);">{{ $totals['attendances'] ?? 0 }}</div>
            </div>
            <div class="sa-kpi sa-fade-in">
                <div class="sa-kpi-label">Prescrições</div>
                <div class="sa-kpi-value" style="color: var(--sa-info);">{{ $totals['prescriptions'] ?? 0 }}</div>
            </div>
            <div class="sa-kpi sa-fade-in">
                <div class="sa-kpi-label">Dispensações</div>
                <div class="sa-kpi-value" style="color: var(--sa-success);">{{ $totals['dispensed'] ?? 0 }}</div>
            </div>
            <div class="sa-kpi sa-fade-in">
                <div class="sa-kpi-label">Entregas</div>
                <div class="sa-kpi-value" style="color: var(--sa-accent);">{{ $totals['deliveries'] ?? 0 }}</div>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            {{-- By Residence --}}
            <div class="sa-card sa-fade-in">
                <div class="sa-card-header">
                    <h3 class="sa-card-title">Por Zona de Residência</h3>
                </div>
                <div class="space-y-3">
                    @foreach ($byResidence ?? [] as $zone => $count)
                        @php
                            $max = max(array_values($byResidence ?? [1]));
                            $pct = $max > 0 ? ($count / $max) * 100 : 0;
                            $barColors = [
                                'URBANA' => 'bg-gray-400',
                                'RURAL' => 'bg-green-500',
                                'INDIGENA' => 'bg-amber-500',
                                'QUILOMBOLA' => 'bg-purple-500',
                                'RIBEIRINHA' => 'bg-blue-500',
                            ];
                        @endphp
                        <div>
                            <div class="flex justify-between text-sm mb-1">
                                <span class="font-medium text-gray-700">{{ $zone }}</span>
                                <span class="text-gray-500">{{ $count }}</span>
                            </div>
                            <div class="w-full bg-gray-100 rounded-full h-2.5">
                                <div class="{{ $barColors[$zone] ?? 'bg-sa-primary' }} h-2.5 rounded-full transition-all duration-500" style="width: {{ $pct }}%"></div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            {{-- By Manchester --}}
            <div class="sa-card sa-fade-in">
                <div class="sa-card-header">
                    <h3 class="sa-card-title">Classificação Manchester</h3>
                </div>
                <div class="space-y-3">
                    @foreach ($byManchester ?? [] as $level => $count)
                        @php
                            $maxM = max(array_values($byManchester ?? [1]));
                            $pctM = $maxM > 0 ? ($count / $maxM) * 100 : 0;
                            $manColors = [
                                'VERMELHO' => 'bg-red-500',
                                'LARANJA' => 'bg-orange-500',
                                'AMARELO' => 'bg-yellow-400',
                                'VERDE' => 'bg-green-500',
                                'AZUL' => 'bg-blue-500',
                            ];
                        @endphp
                        <div>
                            <div class="flex justify-between text-sm mb-1">
                                <span class="font-medium text-gray-700">{{ $level }}</span>
                                <span class="text-gray-500">{{ $count }}</span>
                            </div>
                            <div class="w-full bg-gray-100 rounded-full h-2.5">
                                <div class="{{ $manColors[$level] ?? 'bg-gray-400' }} h-2.5 rounded-full transition-all duration-500" style="width: {{ $pctM }}%"></div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        {{-- Export --}}
        <div class="sa-card sa-fade-in">
            <div class="sa-card-header">
                <h3 class="sa-card-title">Exportar Dados</h3>
            </div>
            <div class="flex flex-wrap gap-3">
                <a href="{{ route('reports.export', ['format' => 'csv']) }}" class="sa-btn-primary">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3"/></svg>
                    Exportar CSV
                </a>
                <a href="{{ route('reports.export', ['format' => 'pdf']) }}" class="sa-btn-outline">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z"/></svg>
                    Exportar PDF
                </a>
            </div>
        </div>
    </div>
</x-app-layout>
