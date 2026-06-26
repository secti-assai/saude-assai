<x-app-layout>
    <x-slot name="header">
        <div class="sa-page-header flex items-center gap-3">
            <a href="{{ route('triagem.cidadao', ['busca' => $paciente->cpf ?? $paciente->full_name]) }}" class="text-gray-400 hover:text-gray-700 transition" title="Voltar à consulta">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18" />
                </svg>
            </a>
            <div>
                <h2 class="sa-page-title">Histórico de Atendimentos</h2>
                <p class="sa-page-subtitle">{{ $paciente->full_name }}</p>
            </div>
        </div>
    </x-slot>

    <div class="space-y-4">

        <div class="sa-card p-4">
            <p class="text-sm text-gray-600">
                Paciente:
                <span class="font-semibold">{{ $paciente->full_name }}</span>
            </p>

            @if($paciente->cpf)
                <p class="text-xs text-gray-500">CPF: {{ $paciente->cpf }}</p>
            @endif
        </div>

        <div class="sa-card p-4">
            <h3 class="font-bold mb-3">Atendimentos</h3>

            @forelse($atendimentos as $a)
                <div class="border-b py-2 text-sm">
                    <div class="flex justify-between">
                        <span>{{ $a->arrived_at?->format('d/m/Y H:i') }}</span>
                        <span>{{ $a->statusLabel() }}</span>
                    </div>

                    <div class="text-xs text-gray-500">
                        Prioridade: {{ $a->priorityColorLabel() ?? '—' }}
                    </div>
                </div>
            @empty
                <p class="text-sm text-gray-500">Nenhum atendimento encontrado.</p>
            @endforelse
        </div>

    </div>
</x-app-layout>