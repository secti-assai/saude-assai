<x-app-layout>
    <x-slot name="header">
        <div class="sa-page-header">
            <h2 class="sa-page-title">Entregas</h2>
            <p class="sa-page-subtitle">Rastreamento de entregas de medicamentos</p>
        </div>
    </x-slot>

    <div class="space-y-6">
        @if (session('status'))
            <div class="sa-alert-success sa-fade-in">
                <svg class="w-5 h-5 text-green-600 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                <span class="text-sm font-medium">{{ session('status') }}</span>
            </div>
        @endif
        
        @if ($errors->any())
            <div class="bg-red-50 text-red-700 p-4 rounded-xl text-sm font-medium mb-4 sa-fade-in">
                <ul>
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        {{-- Barra de Filtros --}}
        <div class="bg-white p-4 rounded-xl shadow-sm mb-6 border border-gray-100">
            <form method="GET" action="{{ route('deliveries.index') }}" class="flex flex-col md:flex-row gap-4">
                <div class="flex-1">
                    <input type="text" name="search" placeholder="Buscar por paciente..." value="{{ request('search') }}" class="sa-input w-full">
                </div>
                <div>
                    <select name="status" class="sa-select">
                        <option value="ativos" {{ request('status', 'ativos') === 'ativos' ? 'selected' : '' }}>Entregas Ativas</option>
                        <option value="historico" {{ request('status') === 'historico' ? 'selected' : '' }}>Histórico (Concluídas/Falhas)</option>
                        <option value="todos" {{ request('status') === 'todos' ? 'selected' : '' }}>Todos</option>
                    </select>
                </div>
                <div>
                    <button type="submit" class="sa-btn-primary w-full md:w-auto">Filtrar</button>
                </div>
            </form>
        </div>

        {{-- Delivery Cards --}}
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            @forelse ($deliveries as $d)
                <div class="sa-card sa-fade-in">
                    <div class="flex items-start justify-between mb-4">
                        <div>
                            <h4 class="font-bold text-gray-900">{{ $d->prescription->citizen->full_name ?? '—' }}</h4>
                            <p class="text-xs text-gray-500 mt-0.5">
                                Senha: {{ $d->prescription->attendance->queue_password ?? '—' }}
                            </p>
                        </div>
                        @php
                            $deliveryStatusMap = [
                                'PENDENTE' => 'sa-badge-warning',
                                'EM_ROTA' => 'sa-badge-info',
                                'ENTREGUE' => 'sa-badge-success',
                                'FALHA' => 'sa-badge-danger',
                            ];
                        @endphp
                        <span class="sa-badge {{ $deliveryStatusMap[$d->status] ?? 'sa-badge-gray' }}">
                            {{ str_replace('_', ' ', $d->status) }}
                        </span>
                    </div>

                    {{-- Address --}}
                    <div class="bg-gray-50 rounded-xl p-3 mb-4 text-sm text-gray-600">
                        <div class="mb-2 space-y-1">
                            @foreach($d->prescription->items as $item)
                                <p>
                                    <span class="font-medium text-gray-700">{{ $item->medication->name ?? 'Medicamento' }}</span>
                                    <span class="text-gray-500">· {{ $item->dosage ?? 'Dose n/i' }} · {{ $item->frequency ?? 'Frequencia n/i' }} · Qtd: {{ $item->quantity }}</span>
                                </p>
                            @endforeach
                        </div>
                        <div class="flex items-start gap-2">
                            <svg class="w-4 h-4 text-gray-400 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1115 0z"/></svg>
                            <span>{{ $d->address ?? $d->prescription->citizen->address ?? 'Endereco nao informado' }}</span>
                        </div>
                    </div>

                    {{-- Status Update Form / Concurrency Lock --}}
                    @php
                        $isCentral = in_array(auth()->user()->role, ['admin_secti', 'gestor', 'auditor']);
                        $isLockedByOther = $d->delivery_user_id && $d->delivery_user_id !== auth()->id() && !$isCentral;
                    @endphp

                    @if($isLockedByOther)
                        <div class="bg-yellow-50 text-yellow-800 text-sm p-3 rounded-lg border border-yellow-200">
                            🔒 Esta entrega está em rota com outro entregador.
                        </div>
                    @else
                        <form method="POST" action="{{ route('deliveries.update', $d) }}" class="space-y-3">
                            @csrf
                            @method('PUT')

                            <div class="flex gap-2">
                                <div class="flex-1">
                                    <select name="status" class="sa-select text-sm" onchange="toggleFailureReason(this)">
                                        <option value="PENDENTE" {{ $d->status === 'PENDENTE' ? 'selected' : '' }}>Pendente</option>
                                        <option value="EM_ROTA" {{ $d->status === 'EM_ROTA' ? 'selected' : '' }}>Em Rota (Assumir)</option>
                                        <option value="ENTREGUE" {{ $d->status === 'ENTREGUE' ? 'selected' : '' }}>Entregue</option>
                                        <option value="FALHA" {{ $d->status === 'FALHA' ? 'selected' : '' }}>Falha na Entrega</option>
                                    </select>
                                </div>
                                <button type="submit" class="sa-btn-primary text-sm">
                                    Atualizar
                                </button>
                            </div>

                            {{-- Motivo da Falha (Oculto via JS até que FALHA seja selecionado) --}}
                            <div class="failure-reason-container mt-2 transition-all duration-300" style="display: {{ $d->status === 'FALHA' ? 'block' : 'none' }}">
                                <input type="text" name="failure_reason" class="sa-input text-xs w-full" placeholder="Ex: Endereço não localizado, Cliente ausente..." value="{{ old('failure_reason', $d->failure_reason) }}">
                            </div>

                            {{-- GPS fields (collapsible) --}}
                            <details class="text-sm">
                                <summary class="text-gray-500 cursor-pointer hover:text-gray-700 transition flex items-center gap-1">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                                    Coordenadas GPS
                                </summary>
                                <div class="grid grid-cols-2 gap-2 mt-2">
                                    <input name="gps_lat" class="sa-input text-xs" placeholder="Latitude" value="{{ old('gps_lat', $d->gps_lat) }}">
                                    <input name="gps_lng" class="sa-input text-xs" placeholder="Longitude" value="{{ old('gps_lng', $d->gps_lng) }}">
                                </div>
                            </details>
                        </form>
                    @endif
                </div>
            @empty
                <div class="sa-card col-span-full text-center py-12">
                    <svg class="w-12 h-12 mx-auto text-gray-300 mb-3" fill="none" stroke="currentColor" stroke-width="1" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 18.75a1.5 1.5 0 01-3 0m3 0a1.5 1.5 0 00-3 0m3 0h6m-9 0H3.375a1.125 1.125 0 01-1.125-1.125V14.25"/></svg>
                    <p class="text-gray-500 font-medium">Nenhuma entrega encontrada.</p>
                </div>
            @endforelse
        </div>
    </div>

    {{-- Script para exibir/ocultar o motivo da falha dinamicamente --}}
    <script>
        function toggleFailureReason(selectElement) {
            const form = selectElement.closest('form');
            const reasonContainer = form.querySelector('.failure-reason-container');
            const reasonInput = form.querySelector('input[name="failure_reason"]');
            
            if (selectElement.value === 'FALHA') {
                reasonContainer.style.display = 'block';
                reasonInput.setAttribute('required', 'required');
            } else {
                reasonContainer.style.display = 'none';
                reasonInput.removeAttribute('required');
                reasonInput.value = ''; // Limpa o valor se trocar pra outro status
            }
        }
    </script>
</x-app-layout>