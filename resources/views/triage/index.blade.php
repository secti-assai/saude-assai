<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl">M4 - Triagem UBS</h2>
        <p class="text-sm text-gray-600 mt-1">Avaliação de sinais vitais, comorbidades e classificação de risco</p>
    </x-slot>
    <div class="py-6 max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
        @if (session('status'))
            <div class="p-4 bg-green-50 border border-green-200 rounded-lg text-green-800">
                <strong>✓ Sucesso:</strong> {{ session('status') }}
            </div>
        @endif

        @forelse ($attendances as $attendance)
            <div class="bg-white p-6 rounded-lg shadow-md border border-gray-100">
                <!-- Cabeçalho com Paciente e Senha -->
                <div class="mb-6 pb-4 border-b-2 border-gray-200">
                    <h3 class="text-lg font-semibold text-gray-900">{{ $attendance->citizen->full_name }}</h3>
                    <div class="flex items-center gap-2 mt-2">
                        <span class="text-sm text-gray-600">Senha da Fila:</span>
                        <span class="inline-block bg-blue-100 text-blue-800 font-bold text-2xl px-4 py-1 rounded-full">{{ $attendance->queue_password }}</span>
                    </div>
                </div>

                <form method="POST" action="{{ route('triage.store', $attendance) }}" class="space-y-6">
                    @csrf

                    <!-- Seção 1: Estado Geral -->
                    <div class="bg-gray-50 p-4 rounded-lg border border-gray-200">
                        <h4 class="font-semibold text-gray-800 mb-4">Estado Geral do Paciente</h4>
                        <div class="grid md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Nível de Consciência</label>
                                <div class="space-y-2">
                                    <label class="flex items-center cursor-pointer">
                                        <input type="radio" name="consciousness_level" value="LUCIDO" class="w-4 h-4 text-blue-600" required>
                                        <span class="ml-2 text-gray-700">Lúcido</span>
                                    </label>
                                    <label class="flex items-center cursor-pointer">
                                        <input type="radio" name="consciousness_level" value="CONFUSO" class="w-4 h-4 text-blue-600">
                                        <span class="ml-2 text-gray-700">Confuso</span>
                                    </label>
                                    <label class="flex items-center cursor-pointer">
                                        <input type="radio" name="consciousness_level" value="INCONSCIENTE" class="w-4 h-4 text-blue-600">
                                        <span class="ml-2 text-gray-700">Inconsciente</span>
                                    </label>
                                    <label class="flex items-center cursor-pointer">
                                        <input type="radio" name="consciousness_level" value="COMATOSO" class="w-4 h-4 text-blue-600">
                                        <span class="ml-2 text-gray-700">Comatoso</span>
                                    </label>
                                </div>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Comorbidades</label>
                                <div class="space-y-2 max-h-32 overflow-y-auto">
                                    <label class="flex items-center cursor-pointer">
                                        <input type="checkbox" name="comorbidities[]" value="DM" class="w-4 h-4 text-blue-600 rounded">
                                        <span class="ml-2 text-gray-700">Diabetes Mellitus (DM)</span>
                                    </label>
                                    <label class="flex items-center cursor-pointer">
                                        <input type="checkbox" name="comorbidities[]" value="HAS" class="w-4 h-4 text-blue-600 rounded">
                                        <span class="ml-2 text-gray-700">Hipertensão (HAS)</span>
                                    </label>
                                    <label class="flex items-center cursor-pointer">
                                        <input type="checkbox" name="comorbidities[]" value="CARDIO" class="w-4 h-4 text-blue-600 rounded">
                                        <span class="ml-2 text-gray-700">Cardiopatia</span>
                                    </label>
                                    <label class="flex items-center cursor-pointer">
                                        <input type="checkbox" name="comorbidities[]" value="DPOC" class="w-4 h-4 text-blue-600 rounded">
                                        <span class="ml-2 text-gray-700">DPOC</span>
                                    </label>
                                    <label class="flex items-center cursor-pointer">
                                        <input type="checkbox" name="comorbidities[]" value="ALERGIAS" class="w-4 h-4 text-blue-600 rounded">
                                        <span class="ml-2 text-gray-700">Alergias/Intolerancias</span>
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Seção 2: Sinais Vitais -->
                    <div class="bg-gray-50 p-4 rounded-lg border border-gray-200">
                        <h4 class="font-semibold text-gray-800 mb-4">Sinais Vitais</h4>
                        <div class="grid md:grid-cols-2 lg:grid-cols-4 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">PA Sistólica (mmHg)</label>
                                <input type="number" name="systolic_pressure" placeholder="120" class="w-full px-3 py-2 rounded border border-gray-300 focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                                <small class="text-xs text-gray-500 mt-1">Normal: 120 | Alerta: >180</small>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">PA Diastólica (mmHg)</label>
                                <input type="number" name="diastolic_pressure" placeholder="80" class="w-full px-3 py-2 rounded border border-gray-300 focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                                <small class="text-xs text-gray-500 mt-1">Normal: 80 | Alerta: >110</small>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Temperatura (°C)</label>
                                <input type="number" name="temperature" placeholder="36.5" step="0.1" class="w-full px-3 py-2 rounded border border-gray-300 focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                                <small class="text-xs text-gray-500 mt-1">Normal: 36.5-37.5 | Febre: >38</small>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Frequência Cardíaca (bpm)</label>
                                <input type="number" name="heart_rate" placeholder="70" class="w-full px-3 py-2 rounded border border-gray-300 focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                                <small class="text-xs text-gray-500 mt-1">Normal: 60-100 | Alerta: >120</small>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">SpO₂ (%)</label>
                                <input type="number" name="spo2" placeholder="98" class="w-full px-3 py-2 rounded border border-gray-300 focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                                <small class="text-xs text-gray-500 mt-1">Normal: >95% | Alerta: <90%</small>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Glicemia (mg/dL)</label>
                                <input type="number" name="hgt" placeholder="100" class="w-full px-3 py-2 rounded border border-gray-300 focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                                <small class="text-xs text-gray-500 mt-1">Normal: 70-100 | Alerta: <50</small>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Peso (kg)</label>
                                <input type="number" name="weight" placeholder="70" step="0.1" class="w-full px-3 py-2 rounded border border-gray-300 focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                                <small class="text-xs text-gray-500 mt-1">Para cálculo de IMC</small>
                            </div>
                        </div>
                    </div>

                    <!-- Seção 3: Histórico de Enfermagem -->
                    <div class="bg-gray-50 p-4 rounded-lg border border-gray-200">
                        <h4 class="font-semibold text-gray-800 mb-4">Histórico de Enfermagem</h4>
                        <textarea name="nursing_history" placeholder="Descreva queixa principal, história atual, medicamentos em uso, alergias, etc." class="w-full px-3 py-2 rounded border border-gray-300 focus:ring-2 focus:ring-blue-500 focus:border-transparent" rows="4"></textarea>
                    </div>

                    <!-- Botão de Submissão -->
                    <div class="flex gap-3 pt-4">
                        <button type="submit" class="flex-1 bg-green-600 hover:bg-green-700 text-white font-semibold rounded px-4 py-2 transition">✓ Concluir Triagem</button>
                    </div>
                </form>
            </div>
        @empty
            <div class="bg-white p-8 rounded-lg shadow-md text-center text-gray-500">
                <p>Nenhum paciente pendente de triagem</p>
            </div>
        @endforelse
    </div>
</x-app-layout>
