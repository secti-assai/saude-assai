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

        {{-- Delivery Cards --}}
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            @forelse ($deliveries as $d)
                <div class="sa-card sa-fade-in">
                    <div class="flex items-start justify-between mb-4">
                        <div>
                            <h4 class="font-bold text-gray-900">{{ $d->prescription->attendance->patient_name ?? '—' }}</h4>
                            <p class="text-xs text-gray-500 mt-0.5">
                                {{ $d->prescription->medication ?? '—' }} · {{ $d->prescription->dosage ?? '' }}
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
                        <div class="flex items-start gap-2">
                            <svg class="w-4 h-4 text-gray-400 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1115 0z"/></svg>
                            <span>{{ $d->prescription->attendance->address ?? 'Endereço não informado' }}</span>
                        </div>
                    </div>

                    {{-- Status Update Form --}}
                    <form method="POST" action="{{ route('deliveries.update', $d) }}" class="space-y-3">
                        @csrf
                        @method('PUT')

                        <div class="flex gap-2">
                            <div class="flex-1">
                                <select name="status" class="sa-select text-sm">
                                    <option value="PENDENTE" {{ $d->status === 'PENDENTE' ? 'selected' : '' }}>Pendente</option>
                                    <option value="EM_ROTA" {{ $d->status === 'EM_ROTA' ? 'selected' : '' }}>Em Rota</option>
                                    <option value="ENTREGUE" {{ $d->status === 'ENTREGUE' ? 'selected' : '' }}>Entregue</option>
                                    <option value="FALHA" {{ $d->status === 'FALHA' ? 'selected' : '' }}>Falha</option>
                                </select>
                            </div>
                            <button type="submit" class="sa-btn-primary text-sm">
                                Atualizar
                            </button>
                        </div>

                        {{-- GPS fields (collapsible) --}}
                        <details class="text-sm">
                            <summary class="text-gray-500 cursor-pointer hover:text-gray-700 transition flex items-center gap-1">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                                Coordenadas GPS
                            </summary>
                            <div class="grid grid-cols-2 gap-2 mt-2">
                                <input name="latitude" class="sa-input text-xs" placeholder="Latitude" value="{{ $d->latitude ?? '' }}">
                                <input name="longitude" class="sa-input text-xs" placeholder="Longitude" value="{{ $d->longitude ?? '' }}">
                            </div>
                        </details>
                    </form>
                </div>
            @empty
                <div class="sa-card col-span-full text-center py-12">
                    <svg class="w-12 h-12 mx-auto text-gray-300 mb-3" fill="none" stroke="currentColor" stroke-width="1" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 18.75a1.5 1.5 0 01-3 0m3 0a1.5 1.5 0 00-3 0m3 0h6m-9 0H3.375a1.125 1.125 0 01-1.125-1.125V14.25"/></svg>
                    <p class="text-gray-500 font-medium">Nenhuma entrega pendente.</p>
                </div>
            @endforelse
        </div>
    </div>
</x-app-layout>
