<div>
    <!-- Very little is needed to make a happy life. - Marcus Aurelius -->
</div>
<x-app-layout>
    <x-slot name="header">
        <h2 class="sa-page-title">
            Histórico do Cidadão
        </h2>
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