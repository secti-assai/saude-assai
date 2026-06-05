<x-app-layout>
    <x-slot name="header">
        <div class="sa-page-header flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            <div>
                <h2 class="sa-page-title">Administração - Controle de Borda</h2>
                <p class="sa-page-subtitle">Auditoria e cruzamento de dispensações da Betha vs. validações internas (Gov.Assaí Nível 2 / ACS)</p>
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
                <div class="text-[10px] text-gray-400 mt-1">Dispensações importadas no período</div>
            </div>

            <div class="sa-kpi">
                <p class="sa-kpi-label text-emerald-600">Fluxo Regular</p>
                <p class="sa-kpi-value text-emerald-700">{{ $stats['regular'] }}</p>
                <div class="text-[10px] text-emerald-500 mt-1">
                    Gov.Assaí: <strong>{{ $stats['dispensations_gov_assai'] }}</strong> · ACS: <strong>{{ $stats['dispensations_acs'] }}</strong>
                </div>
            </div>

            <div class="sa-kpi">
                <p class="sa-kpi-label text-red-600">Dispensações Irregulares</p>
                <p class="sa-kpi-value text-red-700">{{ $stats['bypass'] }}</p>
                <div class="text-[10px] text-red-500 mt-1">Retiradas sem cadastro regular (Bypass)</div>
            </div>

            <div class="sa-kpi">
                <p class="sa-kpi-label text-blue-600">Cidadãos Bloqueados</p>
                <p class="sa-kpi-value text-blue-700">{{ $stats['unique_locked'] }}</p>
                <div class="text-[10px] text-blue-500 mt-1">Bloqueios de cidadãos ativados no período</div>
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
                        <div class="p-3.5 rounded-xl border border-gray-100 bg-slate-50/70 hover:bg-slate-50 transition flex items-center justify-between gap-4">
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
                                        <th>Bloqueio</th>
                                        <th>Dispensações (Itens)</th>
                                        <th>Total Unidades</th>
                                        <th>Última Retirada</th>
                                        <th>Situação</th>
                                    @else
                                        <th>Data / Guia</th>
                                        <th>Cidadão / Status</th>
                                        <th>Bloqueio</th>
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
                                                    </div>
                                                @else
                                                    <div class="font-bold text-gray-900 leading-tight">
                                                        Cidadão Não Identificado
                                                    </div>
                                                @endif
                                            </td>
                                            <td>
                                                @if($row->citizen)
                                                    <form method="POST" action="{{ route('admin.border-control.toggle-lock', $row->citizen->id) }}">
                                                        @csrf
                                                        <button type="submit" class="sa-btn px-3 py-1 text-xs font-semibold rounded-lg shadow-sm border transition-all duration-200
                                                            @if($row->citizen->pharmacy_lock_flag)
                                                                bg-rose-50 border-rose-200 text-rose-700 hover:bg-rose-100
                                                            @else
                                                                bg-emerald-50 border-emerald-200 text-emerald-700 hover:bg-emerald-100
                                                            @endif">
                                                            @if($row->citizen->pharmacy_lock_flag)
                                                                Bloqueado 🔒
                                                            @else
                                                                Desbloqueado 🔓
                                                            @endif
                                                        </button>
                                                    </form>
                                                @else
                                                    <span class="text-xs text-gray-400 font-medium">Não disponível</span>
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
                                                    </div>
                                                @else
                                                    <div class="font-bold text-gray-900 leading-tight">
                                                        {{ $row->customer_name_raw }}
                                                    </div>
                                                    <span class="sa-badge sa-badge-gray mt-1">Cidadão Não Sincronizado</span>
                                                @endif
                                            </td>
                                            <td>
                                                @if($row->citizen)
                                                    <form method="POST" action="{{ route('admin.border-control.toggle-lock', $row->citizen->id) }}">
                                                        @csrf
                                                        <button type="submit" class="sa-btn px-3 py-1 text-xs font-semibold rounded-lg shadow-sm border transition-all duration-200
                                                            @if($row->citizen->pharmacy_lock_flag)
                                                                bg-rose-50 border-rose-200 text-rose-700 hover:bg-rose-100
                                                            @else
                                                                bg-emerald-50 border-emerald-200 text-emerald-700 hover:bg-emerald-100
                                                            @endif">
                                                            @if($row->citizen->pharmacy_lock_flag)
                                                                Bloqueado 🔒
                                                            @else
                                                                Desbloqueado 🔓
                                                            @endif
                                                        </button>
                                                    </form>
                                                @else
                                                    <span class="text-xs text-gray-400 font-medium">Não disponível</span>
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
                                        <td colspan="{{ ($view_type ?? 'item') === 'citizen' ? 6 : 5 }}" class="text-center text-gray-500 py-12">
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
                                        <td colspan="5" class="text-center text-gray-500 py-12">
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
    </script>
</x-app-layout>
