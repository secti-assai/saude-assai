<x-app-layout>
    <x-slot name="header">
        <div class="sa-page-header">
            <h2 class="sa-page-title">Recepção</h2>
            <p class="sa-page-subtitle">Cadastro de pacientes e fila de atendimento</p>
        </div>
    </x-slot>

    <div class="space-y-6">
        {{-- Success Alert --}}
        @if (session('status'))
            <div class="sa-alert-success sa-fade-in">
                <svg class="w-5 h-5 text-green-600 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <span class="text-sm font-medium">{{ session('status') }}</span>
            </div>
        @endif

        {{-- Registration Form --}}
        <div class="sa-card sa-fade-in">
            <div class="sa-card-header">
                <h3 class="sa-card-title">
                    <svg class="w-5 h-5 inline-block mr-1 text-sa-primary" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M18 7.5v3m0 0v3m0-3h3m-3 0h-3m-2.25-4.125a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zM3 19.235v-.11a6.375 6.375 0 0112.75 0v.109A12.318 12.318 0 019.374 21c-2.331 0-4.512-.645-6.374-1.766z"/></svg>
                    Novo Atendimento
                </h3>
            </div>
            <form method="POST" action="{{ route('reception.store') }}" class="space-y-4">
                @csrf
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    <div>
                        <label class="sa-label">Nome Completo *</label>
                        <input name="patient_name" class="sa-input" required placeholder="Nome do paciente">
                    </div>
                    <div>
                        <label class="sa-label">CPF</label>
                        <input name="cpf" class="sa-input" placeholder="000.000.000-00">
                    </div>
                    <div>
                        <label class="sa-label">Cartão SUS</label>
                        <input name="sus_card" class="sa-input" placeholder="Nº do Cartão SUS">
                    </div>
                    <div>
                        <label class="sa-label">Data de Nascimento</label>
                        <input name="birth_date" type="date" class="sa-input">
                    </div>
                    <div>
                        <label class="sa-label">Telefone</label>
                        <input name="phone" class="sa-input" placeholder="(00) 00000-0000">
                    </div>
                    <div>
                        <label class="sa-label">Residência</label>
                        <select name="residence" class="sa-select">
                            <option value="URBANA">Urbana</option>
                            <option value="RURAL">Rural</option>
                            <option value="INDIGENA">Área Indígena</option>
                            <option value="QUILOMBOLA">Quilombola</option>
                            <option value="RIBEIRINHA">Ribeirinha</option>
                        </select>
                    </div>
                    <div class="lg:col-span-2">
                        <label class="sa-label">Endereço</label>
                        <input name="address" class="sa-input" placeholder="Rua, número, bairro">
                    </div>
                </div>
                <div class="flex justify-end pt-2">
                    <button type="submit" class="sa-btn-primary">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
                        Registrar Atendimento
                    </button>
                </div>
            </form>
        </div>

        {{-- Queue Table --}}
        <div class="sa-card sa-fade-in">
            <div class="sa-card-header">
                <h3 class="sa-card-title">Fila de Hoje</h3>
                <span class="sa-badge sa-badge-info">{{ $attendances->count() }} pacientes</span>
            </div>
            <div class="overflow-x-auto">
                <table class="sa-table">
                    <thead>
                        <tr>
                            <th>Senha</th>
                            <th>Paciente</th>
                            <th>CPF</th>
                            <th>Residência</th>
                            <th>Status</th>
                            <th>Hora</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($attendances as $a)
                            <tr>
                                <td>
                                    <span class="inline-flex items-center justify-center w-12 h-8 rounded-lg bg-sa-primary/10 text-sa-primary font-bold text-sm">
                                        {{ $a->queue_password }}
                                    </span>
                                </td>
                                <td class="font-medium text-gray-900">{{ $a->patient_name }}</td>
                                <td class="text-gray-500 text-xs font-mono">{{ $a->cpf ?? '—' }}</td>
                                <td>
                                    @php
                                        $resColors = [
                                            'URBANA' => 'sa-badge-gray',
                                            'RURAL' => 'sa-badge-success',
                                            'INDIGENA' => 'sa-badge-warning',
                                            'QUILOMBOLA' => 'sa-badge-purple',
                                            'RIBEIRINHA' => 'sa-badge-info',
                                        ];
                                    @endphp
                                    <span class="sa-badge {{ $resColors[$a->residence] ?? 'sa-badge-gray' }}">{{ $a->residence }}</span>
                                </td>
                                <td>
                                    @php
                                        $statusColors = [
                                            'AGUARDANDO' => 'sa-badge-warning',
                                            'TRIAGEM' => 'sa-badge-info',
                                            'CONSULTA' => 'sa-badge-primary',
                                            'FINALIZADO' => 'sa-badge-success',
                                        ];
                                    @endphp
                                    <span class="sa-badge {{ $statusColors[$a->status] ?? 'sa-badge-gray' }}">{{ $a->status }}</span>
                                </td>
                                <td class="text-gray-500 text-xs">{{ $a->created_at->format('H:i') }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center text-gray-400 py-8">Nenhum atendimento registrado hoje.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-app-layout>
