<x-app-layout>
    <x-slot name="header">
        <div class="sa-page-header">
            <h2 class="sa-page-title">Atendimento Hospitalar</h2>
            <p class="sa-page-subtitle">Prontuário SOAP e condutas clínicas</p>
        </div>
    </x-slot>

    <div class="space-y-6">
        @if (session('status'))
            <div class="sa-alert-success sa-fade-in">
                <svg class="w-5 h-5 text-green-600 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                <span class="text-sm font-medium">{{ session('status') }}</span>
            </div>
        @endif

        @forelse ($attendances as $a)
            <div class="sa-card sa-fade-in">
                <div class="grid grid-cols-1 lg:grid-cols-4 gap-6">
                    {{-- Triage Summary Sidebar --}}
                    <div class="lg:col-span-1">
                        <div class="bg-gray-50 rounded-xl p-4 space-y-3 sticky top-6">
                            <div class="flex items-center justify-between">
                                <span class="inline-flex items-center justify-center w-10 h-8 rounded-lg bg-sa-primary/10 text-sa-primary font-bold text-sm">
                                    {{ $a->queue_password }}
                                </span>
                                @if ($a->triage)
                                    @php
                                        $manMap = [
                                            'VERMELHO' => 'bg-red-500 text-white',
                                            'LARANJA'  => 'bg-orange-500 text-white',
                                            'AMARELO'  => 'bg-yellow-400 text-yellow-900',
                                            'VERDE'    => 'bg-green-500 text-white',
                                            'AZUL'     => 'bg-blue-500 text-white',
                                        ];
                                    @endphp
                                    <span class="sa-badge {{ $manMap[$a->triage->manchester] ?? 'sa-badge-gray' }}">
                                        {{ $a->triage->manchester }}
                                    </span>
                                @endif
                            </div>
                            <div>
                                <h4 class="font-bold text-gray-900 text-sm">{{ $a->patient_name }}</h4>
                                <p class="text-xs text-gray-500">{{ $a->cpf ?? 'CPF não informado' }}</p>
                            </div>
                            @if ($a->triage)
                                <div class="border-t border-gray-200 pt-3 space-y-2 text-xs text-gray-600">
                                    <div class="flex justify-between"><span class="text-gray-400">PA</span><span class="font-medium">{{ $a->triage->blood_pressure ?? '—' }}</span></div>
                                    <div class="flex justify-between"><span class="text-gray-400">FC</span><span class="font-medium">{{ $a->triage->heart_rate ?? '—' }} bpm</span></div>
                                    <div class="flex justify-between"><span class="text-gray-400">Temp</span><span class="font-medium">{{ $a->triage->temperature ?? '—' }} °C</span></div>
                                    <div class="flex justify-between"><span class="text-gray-400">SpO₂</span><span class="font-medium">{{ $a->triage->oxygen_saturation ?? '—' }}%</span></div>
                                </div>
                                @if ($a->triage->complaint)
                                    <div class="border-t border-gray-200 pt-3">
                                        <p class="text-[11px] font-semibold text-gray-400 uppercase tracking-wide mb-1">Queixa</p>
                                        <p class="text-xs text-gray-700">{{ $a->triage->complaint }}</p>
                                    </div>
                                @endif
                            @endif
                        </div>
                    </div>

                    {{-- SOAP Form --}}
                    <div class="lg:col-span-3">
                        <form method="POST" action="{{ route('hospital.store', $a) }}" class="space-y-5">
                            @csrf

                            {{-- SOAP Sections --}}
                            @php
                                $soapSections = [
                                    ['key' => 'subjective', 'label' => 'S — Subjetivo', 'desc' => 'Relato do paciente, queixas, história...', 'color' => 'text-blue-600'],
                                    ['key' => 'objective', 'label' => 'O — Objetivo', 'desc' => 'Exame físico, dados mensuráveis...', 'color' => 'text-emerald-600'],
                                    ['key' => 'assessment', 'label' => 'A — Avaliação', 'desc' => 'Hipótese diagnóstica, CID...', 'color' => 'text-purple-600'],
                                    ['key' => 'plan', 'label' => 'P — Plano', 'desc' => 'Condutas, exames, encaminhamentos...', 'color' => 'text-orange-600'],
                                ];
                            @endphp

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                @foreach ($soapSections as $s)
                                    <div>
                                        <label class="sa-label {{ $s['color'] }}">{{ $s['label'] }}</label>
                                        <textarea name="{{ $s['key'] }}" rows="4" class="sa-input" placeholder="{{ $s['desc'] }}"></textarea>
                                    </div>
                                @endforeach
                            </div>

                            {{-- Diagnosis + Outcome --}}
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label class="sa-label">Diagnóstico / CID-10</label>
                                    <input name="diagnosis" class="sa-input" placeholder="Ex: J06 – IVAS">
                                </div>
                                <div>
                                    <label class="sa-label">Desfecho</label>
                                    <select name="outcome" class="sa-select">
                                        <option value="">Selecione...</option>
                                        <option value="ALTA">🏠 Alta</option>
                                        <option value="INTERNACAO">🏥 Internação</option>
                                        <option value="ENCAMINHAMENTO">➡️ Encaminhamento</option>
                                        <option value="OBITO">⚫ Óbito</option>
                                    </select>
                                </div>
                            </div>

                            <div class="flex justify-end pt-2">
                                <button type="submit" class="sa-btn-primary">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5"/></svg>
                                    Salvar Prontuário
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        @empty
            <div class="sa-card text-center py-12">
                <svg class="w-12 h-12 mx-auto text-gray-300 mb-3" fill="none" stroke="currentColor" stroke-width="1" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 21h16.5M4.5 3h15M5.25 3v18m13.5-18v18"/></svg>
                <p class="text-gray-500 font-medium">Nenhum paciente aguardando atendimento hospitalar.</p>
                <p class="text-gray-400 text-sm mt-1">Pacientes com triagem concluída aparecerão aqui.</p>
            </div>
        @endforelse
    </div>
</x-app-layout>
