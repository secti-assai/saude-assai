<x-app-layout>
    <x-slot name="header">
        <div class="sa-page-header">
            <h2 class="sa-page-title">Triagem</h2>
            <p class="sa-page-subtitle">Classificação de risco e sinais vitais</p>
        </div>
    </x-slot>

    <div class="space-y-6">
        @if (session('status'))
            <div class="sa-alert-success sa-fade-in">
                <svg class="w-5 h-5 text-green-600 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <span class="text-sm font-medium">{{ session('status') }}</span>
            </div>
        @endif

        @forelse ($attendances as $a)
            <div class="sa-card sa-fade-in">
                {{-- Patient Header --}}
                <div class="flex flex-wrap items-center justify-between gap-3 mb-5 pb-4 border-b border-gray-100">
                    <div class="flex items-center gap-3">
                        <span class="inline-flex items-center justify-center w-12 h-10 rounded-xl bg-sa-primary/10 text-sa-primary font-bold text-sm">
                            {{ $a->queue_password }}
                        </span>
                        <div>
                            <h3 class="font-bold text-gray-900">{{ $a->citizen->full_name ?? '—' }}</h3>
                            <p class="text-xs text-gray-500">CPF: {{ $a->citizen->cpf ?? '—' }} · SUS: {{ $a->citizen->cns ?? '—' }}</p>
                        </div>
                    </div>
                    <span class="sa-badge sa-badge-warning">Aguardando Triagem</span>
                </div>

                <form method="POST" action="{{ route('triage.store', $a) }}" class="space-y-5">
                    @csrf

                    {{-- Vital Signs Grid --}}
                    <div>
                        <h4 class="text-sm font-bold text-gray-700 mb-3 flex items-center gap-2">
                            <svg class="w-4 h-4 text-sa-primary" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M21 8.25c0-2.485-2.099-4.5-4.688-4.5-1.935 0-3.597 1.126-4.312 2.733-.715-1.607-2.377-2.733-4.313-2.733C5.1 3.75 3 5.765 3 8.25c0 7.22 9 12 9 12s9-4.78 9-12z"/></svg>
                            Sinais Vitais
                        </h4>
                        <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-5 gap-3">
                            <div class="bg-gray-50 rounded-xl p-3 text-center">
                                <label class="block text-[11px] font-semibold text-gray-500 uppercase tracking-wide mb-1">PA (mmHg)</label>
                                <div class="grid grid-cols-2 gap-1">
                                    <input name="systolic_pressure" type="number" class="sa-input text-center" placeholder="120">
                                    <input name="diastolic_pressure" type="number" class="sa-input text-center" placeholder="80">
                                </div>
                            </div>
                            <div class="bg-gray-50 rounded-xl p-3 text-center">
                                <label class="block text-[11px] font-semibold text-gray-500 uppercase tracking-wide mb-1">FC (bpm)</label>
                                <input name="heart_rate" type="number" class="sa-input text-center" placeholder="80">
                            </div>
                            <div class="bg-gray-50 rounded-xl p-3 text-center">
                                <label class="block text-[11px] font-semibold text-gray-500 uppercase tracking-wide mb-1">Temp (°C)</label>
                                <input name="temperature" class="sa-input text-center" placeholder="36.5">
                            </div>
                            <div class="bg-gray-50 rounded-xl p-3 text-center">
                                <label class="block text-[11px] font-semibold text-gray-500 uppercase tracking-wide mb-1">SpO₂ (%)</label>
                                <input name="spo2" type="number" class="sa-input text-center" placeholder="98">
                            </div>
                            <div class="bg-gray-50 rounded-xl p-3 text-center">
                                <label class="block text-[11px] font-semibold text-gray-500 uppercase tracking-wide mb-1">Peso (kg)</label>
                                <input name="weight" class="sa-input text-center" placeholder="70">
                            </div>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                        <div>
                            <label class="sa-label">Nivel de Consciencia *</label>
                            <select name="consciousness_level" class="sa-select" required>
                                <option value="">Selecione...</option>
                                <option value="LUCIDO">Lucido</option>
                                <option value="CONFUSO">Confuso</option>
                                <option value="SONOLENTO">Sonolento</option>
                                <option value="INCONSCIENTE">Inconsciente</option>
                            </select>
                        </div>
                        <div>
                            <label class="sa-label">HGT (mg/dL)</label>
                            <input name="hgt" type="number" class="sa-input" placeholder="100">
                        </div>
                    </div>

                    {{-- Manchester Protocol --}}
                    <div>
                        <h4 class="text-sm font-bold text-gray-700 mb-3 flex items-center gap-2">
                            <svg class="w-4 h-4 text-sa-primary" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 013 19.875v-6.75z"/></svg>
                            Classificação de Risco (Manchester)
                        </h4>
                        <div class="flex flex-wrap gap-2">
                            @php
                                $manchesterColors = [
                                    'VERMELHO' => 'bg-red-600 hover:bg-red-700 text-white ring-red-300',
                                    'LARANJA'  => 'bg-orange-500 hover:bg-orange-600 text-white ring-orange-300',
                                    'AMARELO'  => 'bg-yellow-400 hover:bg-yellow-500 text-yellow-900 ring-yellow-300',
                                    'VERDE'    => 'bg-green-500 hover:bg-green-600 text-white ring-green-300',
                                    'AZUL'     => 'bg-blue-500 hover:bg-blue-600 text-white ring-blue-300',
                                ];
                            @endphp
                            @foreach ($manchesterColors as $level => $classes)
                                <label class="cursor-pointer">
                                    <input type="radio" name="manchester" value="{{ $level }}" class="sr-only peer" required>
                                    <span class="sa-btn {{ $classes }} peer-checked:ring-2 peer-checked:ring-offset-2 peer-checked:scale-105 transition-transform text-xs">
                                        {{ $level }}
                                    </span>
                                </label>
                            @endforeach
                        </div>
                    </div>

                    {{-- Complaint --}}
                    <div>
                        <label class="sa-label">Queixa Principal</label>
                        <textarea name="nursing_history" rows="2" class="sa-input" placeholder="Descreva a queixa principal do paciente..."></textarea>
                    </div>

                    <div class="flex justify-end pt-2">
                        <button type="submit" class="sa-btn-primary">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5"/></svg>
                            Salvar Triagem
                        </button>
                    </div>
                </form>
            </div>
        @empty
            <div class="sa-card text-center py-12">
                <svg class="w-12 h-12 mx-auto text-gray-300 mb-3" fill="none" stroke="currentColor" stroke-width="1" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                <p class="text-gray-500 font-medium">Nenhum paciente aguardando triagem.</p>
                <p class="text-gray-400 text-sm mt-1">Os pacientes aparecerão aqui assim que forem registrados na recepção.</p>
            </div>
        @endforelse
    </div>
</x-app-layout>
