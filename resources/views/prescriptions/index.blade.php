<x-app-layout>
    <x-slot name="header">
        <div class="sa-page-header">
            <h2 class="sa-page-title">Prescrições</h2>
            <p class="sa-page-subtitle">Prescrição de medicamentos para pacientes atendidos</p>
        </div>
    </x-slot>

    <div class="space-y-6">
        @if (session('status'))
            <div class="sa-alert-success sa-fade-in">
                <svg class="w-5 h-5 text-green-600 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                <span class="text-sm font-medium">{{ session('status') }}</span>
            </div>
        @endif

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            {{-- Prescription Form --}}
            <div class="sa-card sa-fade-in lg:col-span-1">
                <div class="sa-card-header">
                    <h3 class="sa-card-title">Nova Prescrição</h3>
                </div>
                <form method="POST" action="{{ route('prescriptions.store') }}" class="space-y-4">
                    @csrf
                    <div>
                        <label class="sa-label">Paciente *</label>
                        <select name="attendance_id" class="sa-select" required>
                            <option value="">Selecione o paciente...</option>
                            @foreach ($attendances as $a)
                                <option value="{{ $a->id }}">{{ $a->queue_password }} — {{ $a->patient_name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="sa-label">Medicamento *</label>
                        <input name="medication" class="sa-input" required placeholder="Nome do medicamento">
                    </div>
                    <div>
                        <label class="sa-label">Dosagem *</label>
                        <input name="dosage" class="sa-input" required placeholder="Ex: 500mg, 2x ao dia">
                    </div>
                    <div>
                        <label class="sa-label">Observações</label>
                        <textarea name="notes" rows="3" class="sa-input" placeholder="Instruções adicionais..."></textarea>
                    </div>
                    <button type="submit" class="sa-btn-primary w-full">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
                        Prescrever
                    </button>
                </form>
            </div>

            {{-- Prescriptions Table --}}
            <div class="sa-card sa-fade-in lg:col-span-2">
                <div class="sa-card-header">
                    <h3 class="sa-card-title">Prescrições de Hoje</h3>
                    <span class="sa-badge sa-badge-info">{{ $prescriptions->count() }}</span>
                </div>
                <div class="overflow-x-auto">
                    <table class="sa-table">
                        <thead>
                            <tr>
                                <th>Paciente</th>
                                <th>Medicamento</th>
                                <th>Dosagem</th>
                                <th>Status</th>
                                <th>Hora</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($prescriptions as $p)
                                <tr>
                                    <td class="font-medium text-gray-900">{{ $p->attendance->patient_name ?? '—' }}</td>
                                    <td>{{ $p->medication }}</td>
                                    <td class="text-gray-500">{{ $p->dosage }}</td>
                                    <td>
                                        @php
                                            $statusMap = [
                                                'PENDENTE' => 'sa-badge-warning',
                                                'DISPENSADO' => 'sa-badge-success',
                                                'CANCELADO' => 'sa-badge-danger',
                                            ];
                                        @endphp
                                        <span class="sa-badge {{ $statusMap[$p->status] ?? 'sa-badge-gray' }}">{{ $p->status }}</span>
                                    </td>
                                    <td class="text-gray-500 text-xs">{{ $p->created_at->format('H:i') }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="text-center text-gray-400 py-8">Nenhuma prescrição registrada hoje.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
