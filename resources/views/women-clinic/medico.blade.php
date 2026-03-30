<x-app-layout>
    <x-slot name="header">
        <div class="sa-page-header">
            <h2 class="sa-page-title">Clínica da Mulher - MEDICO_CLINICA</h2>
            <p class="sa-page-subtitle">Área exclusiva de check-out</p>
        </div>
    </x-slot>

    <div class="space-y-6">
        @if ($errors->any())
            <div class="bg-red-50 border-l-4 border-red-500 p-4">
                <ul class="text-sm text-red-700 list-disc list-inside">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        @if (session('status'))
            <div class="sa-alert-success"><span class="text-sm font-medium">{{ session('status') }}</span></div>
        @endif

        <div class="sa-card">
            <div class="sa-card-header"><h3 class="sa-card-title">Pacientes em Atendimento</h3></div>
            <div class="overflow-x-auto">
                <table class="sa-table">
                    <thead><tr><th>Cidadão</th><th>Check-in</th><th>Ação</th></tr></thead>
                    <tbody>
                        @forelse($appointments as $appointment)
                            <tr>
                                <td>{{ $appointment->citizen->full_name ?? '—' }}</td>
                                <td>{{ $appointment->checked_in_at?->format('d/m/Y H:i') ?? '—' }}</td>
                                <td>
                                    <form method="POST" action="{{ route('women-clinic.check-out', $appointment) }}">
                                        @csrf
                                        <button type="submit" class="sa-btn-success">Finalizar (Check-out)</button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="3" class="text-center text-gray-500 py-6">Nenhum paciente em consulta.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-app-layout>
