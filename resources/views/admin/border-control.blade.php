<x-app-layout>
    <x-slot name="header">
        <div class="sa-page-header flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            <div>
                <h2 class="sa-page-title">Administração - Controle de Borda</h2>
                <p class="sa-page-subtitle">Auditoria e cruzamento de dispensações da Betha vs. validações internas (Consulta à População à descrita de Assaí Nível 2 / ACS)</p>
            </div>
            <div>
                <a href="{{ route('admin.border-control.export', request()->query()) }}" class="sa-btn sa-btn-secondary inline-flex items-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3" />
                    </svg>
                    Exportar Relatório (CSV)
                </a>
            </div>
        </div>
    </x-slot>

    <div class="space-y-6">
        {{-- Filtros Avançados --}}
        <div class="sa-card">
            <div class="sa-card-header">
                <h3 class="sa-card-title flex items-center gap-2">
                    <svg class="w-5 h-5 text-emerald-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 3c2.755 0 5.455.232 8.083.678.533.09.917.556.917 1.096v1.044a2.25 2.25 0 01-.659 1.591l-5.432 5.432a2.25 2.25 0 00-.659 1.591v2.927a2.25 2.25 0 01-1.244 2.013L9.75 21v-6.568a2.25 2.25 0 00-.659-1.591L3.659 7.409A2.25 2.25 0 013 5.818V4.774c0-.54.384-1.006.917-1.096A48.32 48.32 0 0112 3z" />
                    </svg>
                    Filtros de Auditoria
                </h3>
            </div>

            <form method="GET" action="{{ route('admin.border-control') }}" class="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-6 gap-4 items-end">
                <div>
                    <label for="date_start" class="sa-label">Data Início</label>
                    <input type="date" id="date_start" name="date_start" value="{{ $date_start }}" class="sa-input">
                </div>

                <div>
                    <label for="date_end" class="sa-label">Data Fim</label>
                    <input type="date" id="date_end" name="date_end" value="{{ $date_end }}" class="sa-input">
                </div>

                <div>
                    <label for="citizen_search" class="sa-label">Buscar Cidadão</label>
                    <input type="text" id="citizen_search" name="citizen_search" value="{{ $citizen_search }}" placeholder="Nome ou CPF..." class="sa-input">
                </div>

                <div>
                    <label for="medication_search" class="sa-label">Buscar Medicamento</label>
                    <input type="text" id="medication_search" name="medication_search" value="{{ $medication_search }}" placeholder="Nome do medicamento..." class="sa-input">
                </div>

                <div>
                    <label for="view_type" class="sa-label">Visualizar por</label>
                    <select id="view_type" name="view_type" class="sa-select">
                        <option value="item" {{ ($view_type ?? 'item') === 'item' ? 'selected' : '' }}>Medicamentos Dispensados</option>
                        <option value="citizen" {{ ($view_type ?? 'item') === 'citizen' ? 'selected' : '' }}>Cidadãos que Retiraram</option>
                    </select>
                </div>

                <div class="flex flex-col gap-2">
                    <div class="flex flex-col gap-1.5 py-1">
                        <label class="inline-flex items-center cursor-pointer">
                            <input type="checkbox" name="bypass_only" value="1" {{ $bypass_only ? 'checked' : '' }} class="rounded border-gray-300 text-emerald-600 focus:ring-emerald-500 w-4 h-4">
                            <span class="ml-2 text-xs font-semibold text-gray-700">Apenas Bypass</span>
                        </label>

                        <label class="inline-flex items-center cursor-pointer">
                            <input type="checkbox" name="high_cost_only" value="1" {{ $high_cost_only ? 'checked' : '' }} class="rounded border-gray-300 text-rose-600 focus:ring-rose-500 w-4 h-4">
                            <span class="ml-2 text-xs font-semibold text-gray-700">Apenas Alto Custo 💎</span>
                        </label>
                    </div>

                    <div class="flex gap-2">
                        <button type="submit" class="sa-btn sa-btn-primary flex-1">
                            Filtrar
                        </button>
                        <a href="{{ route('admin.border-control') }}" class="sa-btn sa-btn-outline" title="Limpar Filtros">
                            Limpar
                        </a>
                    </div>
                </div>
            </form>
        </div>

        {{-- Métricas e KPI Cards (Linha 1) --}}
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4">
            <div class="sa-kpi">
                <p class="sa-kpi-label">Total Analisado</p>
                <p class="sa-kpi-value">{{ $stats['total'] }}</p>
                <div class="text-[10px] text-gray-400 mt-1">{{ ($view_type ?? 'item') === 'citizen' ? 'Cidadãos únicos analisados' : 'Dispensações importadas no período' }}</div>
            </div>

            <div class="sa-kpi cursor-pointer hover:shadow-md hover:border-emerald-200 transition-all duration-200" onclick="openDetailsModal('regular', '{{ ($view_type ?? 'item') === 'citizen' ? 'Cidadãos Regulares' : 'Fluxo Regular (Cadastro em Dia)' }}')">
                <p class="sa-kpi-label text-emerald-600">{{ ($view_type ?? 'item') === 'citizen' ? 'Cidadãos Regularizados' : 'Fluxo Regular' }}</p>
                <p class="sa-kpi-value text-emerald-700">{{ $stats['regular'] }}</p>
                <div class="text-[10px] text-emerald-500 mt-1">
                    Consulta à População à descrita de Assaí: <strong>{{ ($view_type ?? 'item') === 'citizen' ? $stats['citizens_level_2'] : $stats['dispensations_gov_assai'] }}</strong> · ACS: <strong>{{ ($view_type ?? 'item') === 'citizen' ? $stats['citizens_validated_acs'] : $stats['dispensations_acs'] }}</strong>
                </div>
            </div>

            <div class="sa-kpi cursor-pointer hover:shadow-md hover:border-red-200 transition-all duration-200" onclick="openDetailsModal('bypass', '{{ ($view_type ?? 'item') === 'citizen' ? 'Cidadãos com Desvios' : 'Dispensações Irregulares (Bypass)' }}')">
                <p class="sa-kpi-label text-red-600">{{ ($view_type ?? 'item') === 'citizen' ? 'Cidadãos Não Regularizados' : 'Dispensações Irregulares' }}</p>
                <p class="sa-kpi-value text-red-700">{{ $stats['bypass'] }}</p>
                <div class="text-[10px] text-red-500 mt-1">{{ ($view_type ?? 'item') === 'citizen' ? 'Cidadãos com retiradas sem cadastro regular' : 'Retiradas sem cadastro regular (Bypass)' }}</div>
            </div>



            <div class="sa-kpi flex flex-col justify-between">
                <div>
                    <p class="sa-kpi-label">Conformidade de Borda</p>
                    <p class="sa-kpi-value @if($stats['compliance_rate'] >= 90) text-emerald-700 @elseif($stats['compliance_rate'] >= 75) text-amber-700 @else text-red-700 @endif">
                        {{ $stats['compliance_rate'] }}%
                    </p>
                </div>
                <div class="mt-2">
                    <div class="sa-progress">
                        <div class="sa-progress-bar @if($stats['compliance_rate'] >= 90) bg-emerald-600 @elseif($stats['compliance_rate'] >= 75) bg-amber-500 @else bg-red-600 @endif" 
                             style="width: {{ $stats['compliance_rate'] }}%"></div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Métricas e KPI Cards (Linha 2) --}}
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4">
            <div class="sa-kpi">
                <p class="sa-kpi-label">Mais Dispensado</p>
                <p class="sa-kpi-value text-xs font-bold text-slate-800 truncate mt-1" title="{{ $stats['most_dispensed_med'] }}">
                    {{ $stats['most_dispensed_med'] }}
                </p>
                <div class="text-[10px] text-gray-400 mt-1">Medicamento com maior saída</div>
            </div>

            <div class="sa-kpi">
                <p class="sa-kpi-label text-rose-600">Alto Custo Mais Dispensado 💎</p>
                <p class="sa-kpi-value text-xs font-bold text-rose-800 truncate mt-1" title="{{ $stats['most_dispensed_high_cost_med'] }}">
                    {{ $stats['most_dispensed_high_cost_med'] }}
                </p>
                <div class="text-[10px] text-rose-400 mt-1">Maior saída em medicamentos DIRES</div>
            </div>

            <div class="sa-kpi">
                <p class="sa-kpi-label">Dispensações por Dia</p>
                <p class="sa-kpi-value">{{ $stats['avg_dispensations_per_day'] }}</p>
                <div class="text-[10px] text-gray-400 mt-1">Média diária no período filtrado</div>
            </div>

            <div class="sa-kpi">
                <p class="sa-kpi-label text-emerald-600">Perfil dos Cidadãos</p>
                <p class="sa-kpi-value text-sm font-bold text-emerald-800 mt-1 leading-tight">
                    Nível 2: <span class="text-slate-800 font-semibold">{{ $stats['citizens_level_2'] }}</span><br>
                    ACS: <span class="text-slate-800 font-semibold">{{ $stats['citizens_validated_acs'] }}</span>
                </p>
                <div class="text-[10px] text-emerald-500 mt-1">Nível 2 vs. Validados ACS (período)</div>
            </div>

            <div class="sa-kpi">
                <p class="sa-kpi-label text-indigo-600">Clínica da Mulher 👩</p>
                <p class="sa-kpi-value text-indigo-700">{{ $stats['women_clinic_appointments'] }}</p>
                <div class="text-[10px] text-indigo-500 mt-1">Agendamentos no período filtrado</div>
            </div>
        </div>

        <div class="grid grid-cols-1 xl:grid-cols-3 gap-6">
            {{-- Relatório Dinâmico Diário --}}
            <div class="sa-card xl:col-span-1 flex flex-col h-full">
                <div class="sa-card-header">
                    <h3 class="sa-card-title flex items-center gap-2">
                        <svg class="w-5 h-5 text-emerald-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 11.25v7.5" />
                        </svg>
                        Evolução Diária
                    </h3>
                </div>

                <div class="flex-1 overflow-y-auto max-h-[500px] pr-2 space-y-3">
                    @forelse($dailyData as $day)
                        <div class="p-3.5 rounded-xl border border-gray-100 bg-slate-50/70 hover:bg-slate-50 transition flex items-center justify-between gap-4 cursor-pointer hover:border-emerald-300 hover:shadow-sm" onclick="openDetailsModal('day', 'Auditoria do dia {{ $day['date'] }}', '{{ $day['date_raw'] }}')">
                            <div>
                                <span class="text-sm font-bold text-gray-800">{{ $day['date'] }}</span>
                                <div class="flex gap-2 text-xs text-gray-500 mt-1">
                                    <span>Total: <strong>{{ $day['total'] }}</strong></span>
                                    <span>·</span>
                                    <span class="text-emerald-600">Regulares: <strong>{{ $day['regular'] }}</strong></span>
                                    <span>·</span>
                                    <span class="text-red-500">Bypass: <strong>{{ $day['bypass'] }}</strong></span>
                                </div>
                            </div>
                            
                            <div class="text-right">
                                <span class="sa-badge @if($day['rate'] >= 90) sa-badge-success @elseif($day['rate'] >= 75) sa-badge-warning @else sa-badge-danger @endif">
                                    {{ $day['rate'] }}% conf.
                                </span>
                                @if($day['bypass'] > 0)
                                    <p class="text-[10px] text-red-500 mt-1 font-semibold">{{ $day['bypass'] }} desvios</p>
                                @endif
                            </div>
                        </div>
                    @empty
                        <div class="text-center text-gray-400 py-12">
                            Sem registros de dispensação externa para o período selecionado.
                        </div>
                    @endforelse
                </div>
            </div>

            {{-- Área de Abas e Tabelas --}}
            <div class="xl:col-span-2 space-y-6">
                {{-- Navegação por Abas --}}
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-1 flex gap-2">
                    <button id="tab-btn-audit" onclick="switchTab('audit')" class="flex-1 py-3 text-sm font-bold rounded-xl transition duration-200 bg-emerald-50 text-emerald-700">
                        Registros Auditados ({{ $rows->total() }})
                    </button>
                    <button id="tab-btn-meds" onclick="switchTab('meds')" class="flex-1 py-3 text-sm font-bold rounded-xl transition duration-200 text-gray-500 hover:bg-gray-50">
                        Relatório por Medicamento ({{ $medicationsReport->count() }})
                    </button>
                </div>

                {{-- Aba 1: Tabela de Auditoria --}}
                <div id="tab-content-audit" class="sa-card">
                    <div class="sa-card-header">
                        <h3 class="sa-card-title flex items-center gap-2">
                            <svg class="w-5 h-5 text-emerald-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 002.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 00-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75 2.25 0 00.75-.75 2.25 2.25 0 00-.1-.664m-5.8 0A2.251 2.251 0 0113.5 2.25H15a2.251 2.251 0 012.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m0 0H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V9.375c0-.621-.504-1.125-1.125-1.125H8.25z" />
                            </svg>
                            Registros Auditados
                        </h3>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="sa-table">
                            <thead>
                                <tr>
                                    @if(($view_type ?? 'item') === 'citizen')
                                        <th>Cidadão / Status</th>
                                        <th>Dispensações (Itens)</th>
                                        <th>Total Unidades</th>
                                        <th>Última Retirada</th>
                                        <th>Situação</th>
                                    @else
                                        <th>Data / Guia</th>
                                        <th>Cidadão / Status</th>
                                        <th>Item</th>
                                        <th>Situação</th>
                                    @endif
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($rows as $row)
                                    <tr>
                                        @if(($view_type ?? 'item') === 'citizen')
                                            <td>
                                                @if($row->citizen)
                                                    <div class="font-bold text-gray-900 leading-tight">
                                                        {{ $row->citizen->full_name }}
                                                    </div>
                                                    <div class="flex items-center gap-2 mt-1">
                                                        <span class="text-xs text-gray-500 font-mono">
                                                            {{ $row->citizen->cpf ? preg_replace("/(\d{3})(\d{3})(\d{3})(\d{2})/", "$1.$2.$3-$4", $row->citizen->cpf) : 'N/A' }}
                                                        </span>
                                                        <span class="sa-badge @if($row->citizen->is_resident_assai) sa-badge-info @else sa-badge-gray @endif">
                                                            {{ $row->citizen->is_resident_assai ? 'Assaí' : 'Pendente' }}
                                                        </span>
                                                        @if($row->citizen->birth_date && $row->citizen->birth_date->format('Y-m-d') === '1900-01-01')
                                                            <button type="button" class="text-[10px] ml-1 bg-blue-50 text-blue-600 hover:bg-blue-100 px-2 py-0.5 rounded border border-blue-200 font-semibold uppercase tracking-wider" onclick="openManualSyncModal({{ $row->citizen->id }}, '{{ addslashes($row->citizen->full_name) }}')">
                                                                Vincular CPF
                                                            </button>
                                                        @endif
                                                    </div>
                                                @else
                                                    <div class="font-bold text-gray-900 leading-tight">
                                                        Cidadão Não Identificado
                                                    </div>
                                                @endif
                                            </td>

                                            <td>
                                                <span class="text-sm font-semibold text-slate-800">
                                                    {{ $row->total_dispensations }}
                                                </span>
                                            </td>
                                            <td>
                                                <span class="text-sm font-semibold text-slate-800">
                                                    {{ $row->total_quantity }}
                                                </span>
                                            </td>
                                            <td>
                                                <span class="text-xs text-gray-500 font-semibold">
                                                    {{ \Carbon\Carbon::parse($row->last_dispensed_at)->format('d/m/Y H:i') }}
                                                </span>
                                            </td>
                                            <td>
                                                @if($row->has_bypass > 0)
                                                    <span class="sa-badge sa-badge-danger inline-flex items-center gap-1">
                                                        <span class="w-1.5 h-1.5 rounded-full bg-red-500"></span>
                                                        DESVIO
                                                    </span>
                                                @else
                                                    <span class="sa-badge sa-badge-success inline-flex items-center gap-1">
                                                        <span class="w-1.5 h-1.5 rounded-full bg-green-500"></span>
                                                        REGULADO
                                                    </span>
                                                @endif
                                            </td>
                                        @else
                                            <td>
                                                <div class="text-xs text-gray-500 font-semibold">
                                                    {{ optional($row->dispensed_at)->format('d/m/Y H:i') ?? 'N/A' }}
                                                </div>
                                                <div class="text-xs text-slate-700 font-mono mt-0.5">
                                                    #{{ $row->external_dispense_number ?? 'N/A' }}
                                                </div>
                                            </td>
                                            <td>
                                                @if($row->citizen)
                                                    <div class="font-bold text-gray-900 leading-tight">
                                                        {{ $row->citizen->full_name }}
                                                    </div>
                                                    <div class="flex items-center gap-2 mt-1">
                                                        <span class="text-xs text-gray-500 font-mono">
                                                            {{ $row->citizen->cpf ? preg_replace("/(\d{3})(\d{3})(\d{3})(\d{2})/", "$1.$2.$3-$4", $row->citizen->cpf) : 'N/A' }}
                                                        </span>
                                                        @php($matchingRequest = $row->centralPharmacyRequest)
                                                        <span class="sa-badge @if((int) ($matchingRequest->gov_assai_level ?? 0) >= 2) sa-badge-primary @else sa-badge-warning @endif">
                                                            Gov N{{ $matchingRequest->gov_assai_level ?? '0' }}
                                                        </span>
                                                        <span class="sa-badge @if($row->citizen->is_resident_assai) sa-badge-info @else sa-badge-gray @endif">
                                                            {{ $row->citizen->is_resident_assai ? 'Assaí' : 'Pendente' }}
                                                        </span>
                                                        @if($row->citizen->birth_date && $row->citizen->birth_date->format('Y-m-d') === '1900-01-01')
                                                            <button type="button" class="text-[10px] ml-1 bg-blue-50 text-blue-600 hover:bg-blue-100 px-2 py-0.5 rounded border border-blue-200 font-semibold uppercase tracking-wider" onclick="openManualSyncModal({{ $row->citizen->id }}, '{{ addslashes($row->citizen->full_name) }}')">
                                                                Vincular CPF
                                                            </button>
                                                        @endif
                                                    </div>
                                                @else
                                                    <div class="font-bold text-gray-900 leading-tight">
                                                        {{ $row->customer_name_raw }}
                                                    </div>
                                                    <span class="sa-badge sa-badge-gray mt-1">Cidadão Não Sincronizado</span>
                                                @endif
                                            </td>

                                            <td>
                                                <div class="text-sm font-semibold text-slate-800 leading-tight">
                                                    {{ $row->medication_name_raw }}
                                                </div>
                                                <div class="text-xs text-gray-500 mt-0.5">
                                                    Qtd: <strong>{{ $row->quantity }}</strong>
                                                </div>
                                            </td>
                                            <td>
                                                @if($row->bypass_detected)
                                                    <span class="sa-badge sa-badge-danger inline-flex items-center gap-1">
                                                        <span class="w-1.5 h-1.5 rounded-full bg-red-500"></span>
                                                        BYPASS
                                                    </span>
                                                @else
                                                    <span class="sa-badge sa-badge-success inline-flex items-center gap-1">
                                                        <span class="w-1.5 h-1.5 rounded-full bg-green-500"></span>
                                                        REGULADO
                                                    </span>
                                                @endif
                                            </td>
                                        @endif
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="{{ ($view_type ?? 'item') === 'citizen' ? 5 : 4 }}" class="text-center text-gray-500 py-12">
                                            Nenhum registro de dispensação externa auditado para os filtros selecionados.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    @if($rows->hasPages())
                        <div class="mt-5 p-4">
                            {{ $rows->links() }}
                        </div>
                    @endif
                </div>

                {{-- Aba 2: Relatório por Medicamento --}}
                <div id="tab-content-meds" class="sa-card hidden">
                    <div class="sa-card-header">
                        <h3 class="sa-card-title flex items-center gap-2">
                            <svg class="w-5 h-5 text-emerald-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z" />
                            </svg>
                            Relatório por Medicamento (Período)
                        </h3>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="sa-table">
                            <thead>
                                <tr>
                                    <th>Medicamento</th>
                                    <th>Total Dispensações</th>
                                    <th>Qtd Unidades</th>
                                    <th>Desvios (Bypass)</th>
                                    <th>Conformidade</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($medicationsReport as $med)
                                    <tr>
                                        <td>
                                            <div class="text-sm font-semibold text-slate-800 leading-tight">
                                                {{ $med['name'] }}
                                            </div>
                                        </td>
                                        <td>
                                            <span class="text-sm font-semibold text-slate-800">
                                                {{ $med['total'] }}
                                            </span>
                                        </td>
                                        <td>
                                            <span class="text-sm font-semibold text-slate-800">
                                                {{ $med['quantity'] }}
                                            </span>
                                        </td>
                                        <td>
                                            <span class="text-sm font-semibold text-red-600">
                                                {{ $med['bypass'] }}
                                            </span>
                                        </td>
                                        <td>
                                            <span class="sa-badge @if($med['rate'] >= 90) sa-badge-success @elseif($med['rate'] >= 75) sa-badge-warning @else sa-badge-danger @endif">
                                                {{ $med['rate'] }}%
                                            </span>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="text-center text-gray-500 py-12">
                                            Nenhum registro para gerar o relatório de medicamentos.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Estilos Customizados para o Modal de Detalhes -->
    <style>
        .sa-modal-backdrop {
            position: fixed;
            inset: 0;
            background-color: rgba(7, 27, 42, 0.5);
            backdrop-filter: blur(4px);
            -webkit-backdrop-filter: blur(4px);
            z-index: 50;
            opacity: 0;
            transition: opacity 0.2s ease-out;
        }
        
        .sa-modal-backdrop.is-active {
            opacity: 1;
        }
        
        .sa-modal-wrapper {
            position: fixed;
            inset: 0;
            z-index: 55;
            display: flex;
            align-items: flex-start;
            justify-content: center;
            padding: 2.5rem 1rem;
            pointer-events: none;
        }
        
        .sa-modal-box {
            pointer-events: auto;
            width: 100%;
            max-width: 64rem;
            background-color: #ffffff;
            border-radius: 1.25rem;
            box-shadow: 0 25px 50px -12px rgba(7, 27, 42, 0.25);
            border: 1px solid rgba(7, 27, 42, 0.08);
            overflow: hidden;
            display: flex;
            flex-direction: column;
            max-height: 80vh;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            transform: translateY(-20px) scale(0.97);
            opacity: 0;
        }
        
        .sa-modal-box.is-active {
            transform: translateY(0) scale(1);
            opacity: 1;
        }
        
        .sa-modal-header {
            background: linear-gradient(135deg, #0a8f7b 0%, #056e60 100%);
            padding: 1.25rem 1.5rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
            color: #ffffff;
        }
        
        .sa-modal-title {
            font-size: 1.125rem;
            font-weight: 800;
            color: #ffffff;
            margin: 0;
            line-height: 1.2;
        }
        
        .sa-modal-subtitle {
            font-size: 0.75rem;
            color: #e8f5f1;
            margin: 0.25rem 0 0 0;
            font-weight: 500;
        }
        
        .sa-modal-close {
            color: rgba(255, 255, 255, 0.85);
            background: transparent;
            border: none;
            font-size: 2rem;
            font-weight: 300;
            cursor: pointer;
            line-height: 1;
            padding: 0;
            margin: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            width: 2rem;
            height: 2rem;
            transition: color 0.15s ease;
        }
        
        .sa-modal-close:hover {
            color: #ffffff;
        }
        
        .sa-modal-toolbar {
            background-color: #f8fafc;
            border-bottom: 1px solid #e2e8f0;
            padding: 1rem 1.5rem;
            display: flex;
            flex-direction: row;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
        }
        
        .sa-modal-search-wrapper {
            position: relative;
            flex: 1;
            max-width: 24rem;
        }
        
        .sa-modal-search-icon {
            position: absolute;
            inset-y: 0;
            left: 0;
            padding-left: 0.75rem;
            display: flex;
            align-items: center;
            pointer-events: none;
            color: #94a3b8;
        }
        
        .sa-modal-search-input {
            width: 100%;
            padding: 0.625rem 1rem 0.625rem 2.25rem;
            font-size: 0.875rem;
            color: #1e293b;
            background-color: #ffffff;
            border: 1px solid #cbd5e1;
            border-radius: 0.75rem;
            transition: all 0.2s ease;
        }
        
        .sa-modal-search-input:focus {
            outline: none;
            border-color: #0a8f7b;
            box-shadow: 0 0 0 3px rgba(10, 143, 123, 0.15);
        }
        
        .sa-modal-count {
            font-size: 0.75rem;
            color: #64748b;
            font-weight: 600;
        }
        
        .sa-modal-body {
            padding: 1.5rem;
            overflow-y: auto;
            flex: 1;
        }
        
        .sa-modal-footer {
            background-color: #f8fafc;
            border-top: 1px solid #e2e8f0;
            padding: 1rem 1.5rem;
            display: flex;
            justify-content: flex-end;
        }
        
        .sa-modal-btn-close {
            padding: 0.5rem 1.25rem;
            font-size: 0.875rem;
            font-weight: 700;
            color: #475569;
            background-color: #ffffff;
            border: 1px solid #cbd5e1;
            border-radius: 0.75rem;
            cursor: pointer;
            transition: all 0.2s ease;
        }
        
        .sa-modal-btn-close:hover {
            background-color: #f1f5f9;
            color: #1e293b;
            border-color: #94a3b8;
        }

        /* Semantics / Badges */
        .sa-modal-btn-lock {
            padding: 0.375rem 0.75rem;
            font-size: 0.75rem;
            font-weight: 600;
            border-radius: 0.5rem;
            border: 1px solid #fecaca;
            background-color: #fef2f2;
            color: #dc2626;
            cursor: pointer;
            transition: all 0.2s ease;
            display: inline-flex;
            align-items: center;
            gap: 0.25rem;
            line-height: 1;
        }
        .sa-modal-btn-lock:hover {
            background-color: #fee2e2;
            border-color: #fca5a5;
        }
        
        .sa-modal-btn-unlock {
            padding: 0.375rem 0.75rem;
            font-size: 0.75rem;
            font-weight: 600;
            border-radius: 0.5rem;
            border: 1px solid #a7f3d0;
            background-color: #ecfdf5;
            color: #059669;
            cursor: pointer;
            transition: all 0.2s ease;
            display: inline-flex;
            align-items: center;
            gap: 0.25rem;
            line-height: 1;
        }
        .sa-modal-btn-unlock:hover {
            background-color: #d1fae5;
            border-color: #6ee7b7;
        }
        
        .sa-modal-badge-bypass {
            display: inline-flex;
            align-items: center;
            gap: 0.25rem;
            padding: 0.25rem 0.625rem;
            font-size: 0.75rem;
            font-weight: 700;
            border-radius: 9999px;
            background-color: #fee2e2;
            color: #991b1b;
            border: 1px solid #fca5a5;
        }
        
        .sa-modal-badge-regulado {
            display: inline-flex;
            align-items: center;
            gap: 0.25rem;
            padding: 0.25rem 0.625rem;
            font-size: 0.75rem;
            font-weight: 700;
            border-radius: 9999px;
            background-color: #dcfce7;
            color: #166534;
            border: 1px solid #86efac;
        }
        
        .sa-modal-badge-gov {
            display: inline-flex;
            align-items: center;
            padding: 0.125rem 0.5rem;
            font-size: 0.75rem;
            font-weight: 600;
            border-radius: 0.375rem;
            background-color: #e8f5f1;
            color: #0a8f7b;
            border: 1px solid #a7f3d0;
        }
        
        .sa-modal-badge-residence-assai {
            display: inline-flex;
            align-items: center;
            padding: 0.125rem 0.5rem;
            font-size: 0.75rem;
            font-weight: 600;
            border-radius: 0.375rem;
            background-color: #dbeafe;
            color: #1e40af;
            border: 1px solid #bfdbfe;
        }
        .sa-modal-badge-residence-pending {
            display: inline-flex;
            align-items: center;
            padding: 0.125rem 0.5rem;
            font-size: 0.75rem;
            font-weight: 600;
            border-radius: 0.375rem;
            background-color: #f1f5f9;
            color: #475569;
            border: 1px solid #cbd5e1;
        }
    </style>

    <!-- ══════════ Modal de Detalhes da Auditoria ══════════ -->
    <div id="details-modal" class="fixed inset-0 z-50 hidden">
        <!-- Backdrop -->
        <div class="sa-modal-backdrop" id="details-modal-backdrop" onclick="closeDetailsModal()"></div>
        
        <div class="sa-modal-wrapper">
            <!-- Modal Content Container -->
            <div class="sa-modal-box" id="details-modal-box">
                <!-- Modal Header -->
                <div class="sa-modal-header">
                    <div>
                        <h3 class="sa-modal-title" id="details-modal-title">Detalhes da Auditoria</h3>
                        <p class="sa-modal-subtitle" id="details-modal-subtitle">Visualização de registros filtrados</p>
                    </div>
                    <button onclick="closeDetailsModal()" class="sa-modal-close">&times;</button>
                </div>

                <!-- Modal Search & Toolbar -->
                <div class="sa-modal-toolbar">
                    <div class="sa-modal-search-wrapper">
                        <span class="sa-modal-search-icon">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                            </svg>
                        </span>
                        <input type="text" id="modal-search-input" placeholder="Buscar por cidadão ou CPF..." class="sa-modal-search-input" oninput="filterModalData()">
                    </div>
                    <div class="sa-modal-count" id="modal-count-display">
                        Mostrando 0 registros
                    </div>
                </div>

                <!-- Modal Body (Table) -->
                <div class="sa-modal-body">
                    <div class="overflow-x-auto">
                        <table class="sa-table w-full">
                            <thead>
                                <tr id="modal-table-headers">
                                    <th>Cidadão / Status</th>
                                    <th>Data/Hora</th>
                                    <th>Medicamento</th>
                                    <th>Situação</th>
                                </tr>
                            </thead>
                            <tbody id="details-modal-tbody">
                                <!-- Populated dynamically via JS -->
                            </tbody>
                        </table>
                    </div>
                </div>
                
                <!-- Modal Footer -->
                <div class="sa-modal-footer">
                    <button onclick="closeDetailsModal()" class="sa-modal-btn-close">
                        Fechar
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script>
        function switchTab(tab) {
            const auditBtn = document.getElementById('tab-btn-audit');
            const medsBtn = document.getElementById('tab-btn-meds');
            const auditContent = document.getElementById('tab-content-audit');
            const medsContent = document.getElementById('tab-content-meds');

            if (tab === 'audit') {
                auditBtn.className = "flex-1 py-3 text-sm font-bold rounded-xl transition duration-200 bg-emerald-50 text-emerald-700";
                medsBtn.className = "flex-1 py-3 text-sm font-bold rounded-xl transition duration-200 text-gray-500 hover:bg-gray-50";
                auditContent.classList.remove('hidden');
                medsContent.classList.add('hidden');
            } else {
                medsBtn.className = "flex-1 py-3 text-sm font-bold rounded-xl transition duration-200 bg-emerald-50 text-emerald-700";
                auditBtn.className = "flex-1 py-3 text-sm font-bold rounded-xl transition duration-200 text-gray-500 hover:bg-gray-50";
                medsContent.classList.remove('hidden');
                auditContent.classList.add('hidden');
            }
        }

        // Details Modal Variables & Logic
        const allPeriodRows = @json($allPeriodRows);
        let currentModalFilterType = ''; 
        let currentModalFilterParam = '';
        let modalFilteredData = [];

        function openDetailsModal(type, title, param = '') {
            currentModalFilterType = type;
            currentModalFilterParam = param;
            
            document.getElementById('details-modal-title').textContent = title;
            document.getElementById('modal-search-input').value = '';
            
            // Build the subtitle dynamically
            let subtitle = 'Visualização de registros filtrados';
            if (type === 'day') {
                subtitle = `Registros de dispensação externa importados para a data de ${param}`;
            } else if (type === 'bypass') {
                subtitle = 'Lista de dispensações onde o fluxo de verificação foi burlado (Bypass)';
            } else if (type === 'regular') {
                subtitle = 'Lista de dispensações que seguiram as regras obrigatórias de validação';
            }
            document.getElementById('details-modal-subtitle').textContent = subtitle;

            // Apply base filter
            applyModalBaseFilter();
            
            // Render Table
            renderModalTable();

            // Display Modal
            const modal = document.getElementById('details-modal');
            const modalBox = document.getElementById('details-modal-box');
            const backdrop = document.getElementById('details-modal-backdrop');
            
            modal.classList.remove('hidden');
            setTimeout(() => {
                backdrop.classList.add('is-active');
                modalBox.classList.add('is-active');
            }, 10);
            
            // Focus Search Input
            setTimeout(() => document.getElementById('modal-search-input').focus(), 150);
        }

        function closeDetailsModal() {
            const modal = document.getElementById('details-modal');
            const modalBox = document.getElementById('details-modal-box');
            const backdrop = document.getElementById('details-modal-backdrop');
            
            backdrop.classList.remove('is-active');
            modalBox.classList.remove('is-active');
            setTimeout(() => {
                modal.classList.add('hidden');
            }, 300);
        }

        const viewType = '{{ $view_type ?? "item" }}';

        function applyModalBaseFilter() {
            let filtered = [];
            
            if (currentModalFilterType === 'day') {
                filtered = allPeriodRows.filter(row => row.date_only === currentModalFilterParam);
            } else if (currentModalFilterType === 'bypass') {
                filtered = allPeriodRows.filter(row => row.bypass_detected);
            } else if (currentModalFilterType === 'regular') {
                filtered = allPeriodRows.filter(row => !row.bypass_detected);
            } else {
                filtered = [...allPeriodRows];
            }

            if (viewType === 'citizen') {
                const seenCitizens = new Set();
                modalFilteredData = filtered.filter(row => {
                    if (row.citizen_id) {
                        if (!seenCitizens.has(row.citizen_id)) {
                            seenCitizens.add(row.citizen_id);
                            return true;
                        }
                        return false;
                    }
                    return true;
                });
            } else {
                modalFilteredData = filtered;
            }
        }

        function filterModalData() {
            const searchQuery = document.getElementById('modal-search-input').value.toLowerCase().trim();
            applyModalBaseFilter();

            if (searchQuery !== '') {
                modalFilteredData = modalFilteredData.filter(row => {
                    const nameMatch = row.customer_name && row.customer_name.toLowerCase().includes(searchQuery);
                    const cpfMatch = row.cpf && row.cpf.replace(/\D/g, '').includes(searchQuery.replace(/\D/g, ''));
                    return nameMatch || cpfMatch;
                });
            }

            renderModalTable();
        }

        function renderModalTable() {
            const tbody = document.getElementById('details-modal-tbody');
            tbody.innerHTML = '';

            document.getElementById('modal-count-display').textContent = `Mostrando ${modalFilteredData.length} registros`;

            // Adjust headers based on type
            const headersTr = document.getElementById('modal-table-headers');
            headersTr.innerHTML = `
                <th>Cidadão / Status</th>
                <th>Data/Hora</th>
                <th>Medicamento</th>
                <th>Situação</th>
            `;

            if (modalFilteredData.length === 0) {
                const colCount = 4;
                tbody.innerHTML = `
                    <tr>
                        <td colspan="${colCount}" class="text-center text-gray-500 py-12">
                            Nenhum registro correspondente encontrado.
                        </td>
                    </tr>
                `;
                return;
            }

            modalFilteredData.forEach(row => {
                const tr = document.createElement('tr');
                
                const badgeClass = row.bypass_detected 
                    ? 'sa-modal-badge-bypass' 
                    : 'sa-modal-badge-regulado';
                
                const situationText = row.bypass_detected 
                    ? '<span style="display:inline-block;width:6px;height:6px;border-radius:9999px;background-color:#dc2626;margin-right:4px;"></span>BYPASS' 
                    : '<span style="display:inline-block;width:6px;height:6px;border-radius:9999px;background-color:#16a34a;margin-right:4px;"></span>REGULADO';

                tr.innerHTML = `
                    <td>
                        <div class="font-bold text-gray-900 leading-tight">${row.customer_name}</div>
                        <div class="flex items-center gap-2 mt-1">
                            <span class="text-xs text-gray-500 font-mono">${row.cpf}</span>
                            <span class="sa-modal-badge-gov">Gov N${row.gov_level}</span>
                        </div>
                    </td>
                    <td>
                        <div class="text-xs text-gray-500 font-semibold">${row.dispensed_at_formatted}</div>
                        <div class="text-xs text-slate-700 font-mono mt-0.5">#${row.external_dispense_number}</div>
                    </td>
                    <td>
                        <div class="text-sm font-semibold text-slate-800 leading-tight">${row.medication_name}</div>
                        <div class="text-xs text-gray-500 mt-0.5">Qtd: <strong>${row.quantity}</strong></div>
                    </td>
                    <td>
                        <span class="${badgeClass}">
                            ${situationText}
                        </span>
                    </td>
                `;
                tbody.appendChild(tr);
            });
        }


        // Close on escape key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                closeDetailsModal();
                closeManualSyncModal();
            }
        });

        // --- Manual Sync Modal Logic ---
        let currentSyntheticCitizenId = null;

        function openManualSyncModal(citizenId, citizenName) {
            currentSyntheticCitizenId = citizenId;
            document.getElementById('sync-citizen-name').textContent = citizenName;
            document.getElementById('sync-cpf').value = '';
            
            // Set default date to today
            const today = new Date().toISOString().split('T')[0];
            document.getElementById('sync-date').value = today;
            
            document.getElementById('manual-sync-modal').classList.remove('hidden');
            document.getElementById('manual-sync-backdrop').classList.add('is-active');
            
            setTimeout(() => {
                document.getElementById('manual-sync-box').classList.add('is-active');
                document.getElementById('sync-cpf').focus();
            }, 50);
        }

        function closeManualSyncModal() {
            document.getElementById('manual-sync-box').classList.remove('is-active');
            document.getElementById('manual-sync-backdrop').classList.remove('is-active');
            setTimeout(() => {
                document.getElementById('manual-sync-modal').classList.add('hidden');
                currentSyntheticCitizenId = null;
            }, 300);
        }

        function submitManualSync() {
            if (!currentSyntheticCitizenId) return;
            
            const cpf = document.getElementById('sync-cpf').value.replace(/\D/g, '');
            const validationDate = document.getElementById('sync-date').value;
            
            if (cpf.length !== 11) {
                alert("Por favor, digite um CPF válido com 11 dígitos.");
                return;
            }
            if (!validationDate) {
                alert("Por favor, informe a data de validação.");
                return;
            }

            const btn = document.getElementById('btn-submit-sync');
            btn.disabled = true;
            btn.innerHTML = 'Vinculando... <svg class="animate-spin ml-2 h-4 w-4 text-white inline" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>';

            fetch(`/admin/border-control/fix-synthetic/${currentSyntheticCitizenId}`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify({
                    cpf: cpf,
                    validation_date: validationDate
                })
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    alert('Cidadão vinculado e histórico recalculado com sucesso!');
                    window.location.reload();
                } else {
                    alert('Erro: ' + (data.message || 'Falha ao processar vinculação.'));
                    btn.disabled = false;
                    btn.innerHTML = 'Confirmar Vinculação';
                }
            })
            .catch(err => {
                console.error(err);
                alert('Erro de comunicação com o servidor.');
                btn.disabled = false;
                btn.innerHTML = 'Confirmar Vinculação';
            });
        }
    </script>

    <!-- Modal HTML for Manual Sync -->
    <div id="manual-sync-modal" class="fixed inset-0 z-50 hidden">
        <div class="sa-modal-backdrop" id="manual-sync-backdrop" onclick="closeManualSyncModal()"></div>
        <div class="sa-modal-wrapper">
            <div class="sa-modal-box" id="manual-sync-box" style="max-width: 28rem;">
                <div class="sa-modal-header" style="background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%);">
                    <div>
                        <h3 class="sa-modal-title">Vincular CPF</h3>
                        <p class="sa-modal-subtitle">Corrigir bypass sintético</p>
                    </div>
                    <button onclick="closeManualSyncModal()" class="sa-modal-close">&times;</button>
                </div>
                
                <div class="sa-modal-body space-y-4">
                    <p class="text-sm text-gray-600">
                        Você está vinculando as dispensações de:<br>
                        <strong id="sync-citizen-name" class="text-gray-900 text-lg"></strong>
                    </p>
                    
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1">CPF Oficial do Cidadão</label>
                        <input type="text" id="sync-cpf" class="sa-input w-full" placeholder="000.000.000-00">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1">Data de Validação Oficial (ACS/Nível 2)</label>
                        <input type="date" id="sync-date" class="sa-input w-full">
                        <p class="text-[10px] text-gray-500 mt-1">Dispensações após essa data terão o bypass perdoado.</p>
                    </div>
                </div>
                
                <div class="sa-modal-footer flex justify-between items-center">
                    <button onclick="closeManualSyncModal()" class="text-gray-500 hover:text-gray-700 text-sm font-semibold">Cancelar</button>
                    <button id="btn-submit-sync" onclick="submitManualSync()" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg text-sm font-bold shadow-sm transition">
                        Confirmar Vinculação
                    </button>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
