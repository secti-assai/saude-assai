<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl">M7 - Prontuário Hospitalar Digital</h2>
        <p class="text-sm text-gray-600 mt-1">Registro clínico estruturado com SOAP, diagnósticos e desfecho</p>
    </x-slot>
    <div class="py-6 max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
        @if (session('status'))
            <div class="p-4 bg-green-50 border border-green-200 rounded-lg text-green-800">
                <strong>✓ Sucesso:</strong> {{ session('status') }}
            </div>
        @endif

        @forelse ($attendances as $attendance)
            <form method="POST" action="{{ route('hospital.store', $attendance) }}" class="bg-white rounded-lg shadow-md border border-gray-100">
                @csrf

                <!-- Cabeçalho -->
                <div class="bg-gradient-to-r from-blue-50 to-blue-100 px-6 py-5 border-b border-gray-200">
                    <h3 class="text-lg font-semibold text-gray-900">{{ $attendance->citizen->full_name }}</h3>
                    <div class="text-sm text-gray-600 mt-1">Atendimento #{{ $attendance->id }} | Data: {{ $attendance->created_at->format('d/m/Y H:i') }}</div>
                </div>

                <div class="p-6 space-y-6">
                    <!-- Painel Esquerdo: Triagem (Resumo) -->
                    <div class="grid md:grid-cols-3 gap-4">
                        <div class="md:col-span-1 bg-blue-50 p-4 rounded-lg border border-blue-200">
                            <h4 class="font-semibold text-blue-900 mb-3">📋 Triagem</h4>
                            @if ($attendance->triage)
                                <div class="space-y-2 text-sm">
                                    <div><strong>Consciência:</strong> <span class="text-blue-700">{{ $attendance->triage->consciousness_level ?? 'N/A' }}</span></div>
                                    <div><strong>PA:</strong> <span class="text-blue-700">{{ $attendance->triage->systolic_pressure ?? '--' }}/{{ $attendance->triage->diastolic_pressure ?? '--' }}</span></div>
                                    <div><strong>FC:</strong> <span class="text-blue-700">{{ $attendance->triage->heart_rate ?? '--' }} bpm</span></div>
                                    <div><strong>SpO₂:</strong> <span class="text-blue-700">{{ $attendance->triage->spo2 ?? '--' }}%</span></div>
                                    <div><strong>T°:</strong> <span class="text-blue-700">{{ $attendance->triage->temperature ?? '--' }}°C</span></div>
                                    <div><strong>Glicemia:</strong> <span class="text-blue-700">{{ $attendance->triage->hgt ?? '--' }} mg/dL</span></div>
                                </div>
                            @else
                                <p class="text-sm text-gray-600">Triagem não realizada</p>
                            @endif
                        </div>

                        <!-- Painel Direito: Avaliação e Diagnósticos -->
                        <div class="md:col-span-2 space-y-4">
                            <!-- SOAP -->
                            <div class="bg-gray-50 p-4 rounded-lg border border-gray-200">
                                <h4 class="font-semibold text-gray-800 mb-3">📝 Avaliação Clínica (SOAP)</h4>
                                <div class="grid md:grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-2">Subjetivo (S)</label>
                                        <textarea name="soap_objective" placeholder="Queixa principal e história do paciente" class="w-full px-3 py-2 rounded border border-gray-300 focus:ring-2 focus:ring-blue-500" rows="3" required></textarea>
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-2">Objetivo (O)</label>
                                        <textarea name="soap_assessment" placeholder="Achados do exame físico e complementares" class="w-full px-3 py-2 rounded border border-gray-300 focus:ring-2 focus:ring-blue-500" rows="3" required></textarea>
                                    </div>
                                </div>
                            </div>

                            <!-- Diagnósticos -->
                            <div class="bg-gray-50 p-4 rounded-lg border border-gray-200">
                                <h4 class="font-semibold text-gray-800 mb-3">🏷️ Diagnósticos</h4>
                                <div class="grid md:grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-2">Diagnóstico Principal</label>
                                        <textarea name="diagnosis" placeholder="Descrição da condição clínica" class="w-full px-3 py-2 rounded border border-gray-300 focus:ring-2 focus:ring-blue-500" rows="2" required></textarea>
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-2">CID-10 Principal</label>
                                        <input type="text" name="cid_10" placeholder="Código CID-10" class="w-full px-3 py-2 rounded border border-gray-300 focus:ring-2 focus:ring-blue-500" required>
                                        <small class="text-xs text-gray-500 mt-1">Ex: A00.0, E10.1</small>
                                    </div>
                                </div>
                            </div>

                            <!-- Orientações e Desfecho -->
                            <div class="bg-gray-50 p-4 rounded-lg border border-gray-200">
                                <h4 class="font-semibold text-gray-800 mb-3">📋 Plano (P) e Desfecho</h4>
                                <div class="grid md:grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-2">Orientações e Medicações</label>
                                        <textarea name="guidance" placeholder="Prescrições, medicamentos, retornos agendados, etc" class="w-full px-3 py-2 rounded border border-gray-300 focus:ring-2 focus:ring-blue-500" rows="3"></textarea>
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-2">Desfecho do Atendimento</label>
                                        <select name="outcome" class="w-full px-3 py-2 rounded border border-gray-300 focus:ring-2 focus:ring-blue-500" required>
                                            <option value="">-- Selecione --</option>
                                            <option value="ALTA">✓ Alta Ambulatorial</option>
                                            <option value="INTERNACAO">🏥 Internação</option>
                                            <option value="TRANSFERENCIA">↪️ Transferência</option>
                                            <option value="OBITO">✗ Óbito</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Botão de Submissão -->
                    <div class="flex gap-3 pt-4 border-t border-gray-200">
                        <button type="submit" class="flex-1 bg-green-600 hover:bg-green-700 text-white font-semibold rounded px-4 py-2 transition">
                            💾 Salvar Prontuário
                        </button>
                    </div>
                </div>
            </form>
        @empty
            <div class="bg-white p-8 rounded-lg shadow-md text-center text-gray-500">
                <p>Nenhum paciente pendente de atendimento hospitalar</p>
            </div>
        @endforelse
    </div>
</x-app-layout>
