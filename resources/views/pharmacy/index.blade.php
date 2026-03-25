<x-app-layout>
    <x-slot name="header">
        <div class="sa-page-header">
            <h2 class="sa-page-title">Farmácia</h2>
            <p class="sa-page-subtitle">Dispensação de medicamentos prescritos</p>
        </div>
    </x-slot>

    <div class="space-y-6">
        @if (session('status'))
            <div class="sa-alert-success sa-fade-in">
                <svg class="w-5 h-5 text-green-600 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                <span class="text-sm font-medium">{{ session('status') }}</span>
            </div>
        @endif

        {{-- Pending Count --}}
        <div class="flex items-center gap-3">
            <span class="sa-badge sa-badge-warning text-sm px-3 py-1">
                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                {{ $prescriptions->count() }} pendentes
            </span>
        </div>

        {{-- Prescription Cards --}}
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            @forelse ($prescriptions as $p)
                <div class="sa-card sa-fade-in">
                    <div class="flex items-start justify-between mb-3">
                        <div>
                            <h4 class="font-bold text-gray-900">{{ $p->citizen->full_name ?? '—' }}</h4>
                            <p class="text-xs text-gray-500">Senha: {{ $p->attendance->queue_password ?? '—' }}</p>
                        </div>
                        <span class="sa-badge sa-badge-warning">Pendente</span>
                    </div>

                    <div class="space-y-2 mb-4">
                        @foreach ($p->items as $item)
                            <div class="flex items-start gap-2 text-sm">
                                <svg class="w-4 h-4 text-sa-primary flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9.75 3.104v5.714a2.25 2.25 0 01-.659 1.591L5 14.5M9.75 3.104c-.251.023-.501.05-.75.082m.75-.082a24.301 24.301 0 014.5 0m0 0v5.714c0 .597.237 1.17.659 1.591L19.8 15.3"/></svg>
                                <div>
                                    <p class="font-medium text-gray-800">{{ $item->medication->name ?? 'Medicamento' }}</p>
                                    <p class="text-xs text-gray-500">
                                        {{ $item->dosage ?? 'Dose n/i' }} · {{ $item->frequency ?? 'Frequencia n/i' }} · Qtd: {{ $item->quantity }}
                                    </p>
                                </div>
                            </div>
                        @endforeach
                        @if ($p->notes)
                            <p class="text-xs text-gray-400 italic">{{ $p->notes }}</p>
                        @endif
                    </div>

                    <form method="POST" action="{{ route('pharmacy.dispense', $p) }}">
                        @csrf
                        <button type="submit" class="sa-btn-success w-full">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5"/></svg>
                            Dispensar
                        </button>
                    </form>
                </div>
            @empty
                <div class="sa-card col-span-full text-center py-12">
                    <svg class="w-12 h-12 mx-auto text-gray-300 mb-3" fill="none" stroke="currentColor" stroke-width="1" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    <p class="text-gray-500 font-medium">Todas as prescrições foram dispensadas.</p>
                    <p class="text-gray-400 text-sm mt-1">Novas prescrições aparecerão aqui automaticamente.</p>
                </div>
            @endforelse
        </div>
    </div>
</x-app-layout>
