<x-app-layout>
    <x-slot name="header">
        <div class="sa-page-header">
            <h2 class="sa-page-title">Triagem</h2>
            <p class="sa-page-subtitle">Classificação de risco e sinais vitais</p>
        </div>
    </x-slot>

    <div class="space-y-4">
        @if (session('status'))
            <div class="sa-alert-success sa-fade-in">
                <svg class="w-5 h-5 text-green-600 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" stroke-width="2"
                    viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round"
                        d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <span class="text-sm font-medium">{{ session('status') }}</span>
            </div>
        @endif

        @forelse ($attendances as $a)
            <div class="sa-card sa-fade-in transition-all duration-200">

                {{-- CABEÇALHO DO PACIENTE --}}
                <div onclick="toggleTriage('{{ $a->id }}')"
                    class="flex flex-wrap items-center justify-between gap-3 cursor-pointer group -m-2 p-2 rounded-xl hover:bg-gray-50 transition-colors">
                    <div class="flex items-center gap-3">
                        <span
                            class="inline-flex items-center justify-center w-12 h-10 rounded-xl bg-sa-primary/10 text-sa-primary font-bold text-sm">
                            {{ $a->queue_password }}
                        </span>
                        <div>
                            <h3 class="font-bold text-gray-900">{{ $a->citizen->full_name ?? '—' }}</h3>
                            <p class="text-xs text-gray-500">CPF: {{ $a->citizen->cpf ?? '—' }} · SUS:
                                {{ $a->citizen->cns ?? '—' }}
                            </p>
                        </div>
                    </div>

                    <div class="flex items-center gap-4">
                        <span class="sa-badge sa-badge-warning">Aguardando Triagem</span>
                        <div class="bg-gray-100 group-hover:bg-gray-200 rounded-full p-1.5 transition-colors">
                            <svg id="chevron-{{ $a->id }}"
                                class="w-5 h-5 text-gray-500 transition-transform duration-300 {{ $loop->first ? 'rotate-180' : '' }}"
                                fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                            </svg>
                        </div>
                    </div>
                </div>

                {{-- FORMULÁRIO RETRÁTIL --}}
                <div id="form-{{ $a->id }}"
                    class="{{ $loop->first ? 'block' : 'hidden' }} mt-4 pt-5 border-t border-gray-100">
                    <form method="POST" action="{{ route('triage.store', $a) }}" class="space-y-5">
                        @csrf

                        {{-- Vital Signs Grid com Alertas Visuais --}}
                        <div>
                            <h4 class="text-sm font-bold text-gray-700 mb-3 flex items-center gap-2">
                                <svg class="w-4 h-4 text-sa-primary" fill="none" stroke="currentColor" stroke-width="2"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M21 8.25c0-2.485-2.099-4.5-4.688-4.5-1.935 0-3.597 1.126-4.312 2.733-.715-1.607-2.377-2.733-4.313-2.733C5.1 3.75 3 5.765 3 8.25c0 7.22 9 12 9 12s9-4.78 9-12z" />
                                </svg>
                                Sinais Vitais
                            </h4>
                            <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-5 gap-3">

                                {{-- Campo PA (Máscara 120/80) --}}
                                <div class="bg-gray-50 rounded-xl p-3 text-center">
                                    <label
                                        class="block text-[11px] font-semibold text-gray-500 uppercase tracking-wide mb-1">PA
                                        (mmHg)</label>
                                    {{-- Campos invisíveis para o Controller --}}
                                    <input type="hidden" name="systolic_pressure" id="sys-{{ $a->id }}">
                                    <input type="hidden" name="diastolic_pressure" id="dia-{{ $a->id }}">
                                    {{-- Campo visível para o Enfermeiro --}}
                                    <input type="text" id="pa-{{ $a->id }}" oninput="formatPA(this, '{{ $a->id }}')"
                                        class="sa-input text-center font-bold text-lg transition-colors duration-200"
                                        placeholder="120/80" maxlength="7">
                                </div>

                                <div class="bg-gray-50 rounded-xl p-3 text-center">
                                    <label
                                        class="block text-[11px] font-semibold text-gray-500 uppercase tracking-wide mb-1">FC
                                        (bpm)</label>
                                    <input name="heart_rate" id="fc-{{ $a->id }}" oninput="checkVitals('{{ $a->id }}')"
                                        type="number"
                                        class="sa-input text-center font-bold text-lg transition-colors duration-200"
                                        placeholder="80">
                                </div>
                                <div class="bg-gray-50 rounded-xl p-3 text-center">
                                    <label
                                        class="block text-[11px] font-semibold text-gray-500 uppercase tracking-wide mb-1">Temp
                                        (°C)</label>
                                    <input name="temperature" id="temp-{{ $a->id }}" oninput="checkVitals('{{ $a->id }}')"
                                        type="number" step="0.1"
                                        class="sa-input text-center font-bold text-lg transition-colors duration-200"
                                        placeholder="36.5">
                                </div>
                                <div class="bg-gray-50 rounded-xl p-3 text-center">
                                    <label
                                        class="block text-[11px] font-semibold text-gray-500 uppercase tracking-wide mb-1">SpO₂
                                        (%)</label>
                                    <input name="spo2" id="spo2-{{ $a->id }}" oninput="checkVitals('{{ $a->id }}')"
                                        type="number"
                                        class="sa-input text-center font-bold text-lg transition-colors duration-200"
                                        placeholder="98">
                                </div>
                                <div class="bg-gray-50 rounded-xl p-3 text-center">
                                    <label
                                        class="block text-[11px] font-semibold text-gray-500 uppercase tracking-wide mb-1">Peso
                                        (kg)</label>
                                    <input name="weight" type="number" step="0.1"
                                        class="sa-input text-center font-bold text-lg" placeholder="70">
                                </div>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                            <div>
                                <label class="sa-label">Nível de Consciência *</label>
                                <select name="consciousness_level" id="consc-{{ $a->id }}"
                                    onchange="checkVitals('{{ $a->id }}')"
                                    class="sa-select font-medium transition-colors duration-200" required>
                                    <option value="">Selecione...</option>
                                    <option value="LUCIDO">Lúcido</option>
                                    <option value="CONFUSO">Confuso</option>
                                    <option value="SONOLENTO">Sonolento</option>
                                    <option value="INCONSCIENTE">Inconsciente</option>
                                </select>
                            </div>
                            <div>
                                <label class="sa-label">HGT (mg/dL)</label>
                                <input name="hgt" id="hgt-{{ $a->id }}" oninput="checkVitals('{{ $a->id }}')" type="number"
                                    class="sa-input transition-colors duration-200" placeholder="100">
                            </div>
                        </div>

                        {{-- Manchester Protocol Padronizado --}}
                        <div>
                            <h4 class="text-sm font-bold text-gray-700 mb-3 flex items-center gap-2">
                                <svg class="w-4 h-4 text-sa-primary" fill="none" stroke="currentColor" stroke-width="2"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 013 19.875v-6.75z" />
                                </svg>
                                Classificação de Risco (Manchester)
                            </h4>

                            <style id="fix-manchester">
                                input[type="radio"]:checked+span {
                                    transform: scale(1.05);
                                    box-shadow: 0 4px 14px rgba(0, 0, 0, 0.15);
                                }

                                /* CORES */
                                #vermelho:checked+span {
                                    background-color: #b91c1c;
                                    color: white;
                                    border-color: #b91c1c;
                                }

                                #laranja:checked+span {
                                    background-color: #ea580c;
                                    color: white;
                                    border-color: #ea580c;
                                }

                                #amarelo:checked+span {
                                    background-color: #ca8a04;
                                    color: white;
                                    border-color: #ca8a04;
                                }

                                #verde:checked+span {
                                    background-color: #15803d;
                                    color: white;
                                    border-color: #15803d;
                                }

                                #azul:checked+span {
                                    background-color: #1d4ed8;
                                    color: white;
                                    border-color: #1d4ed8;
                                }
                            </style>

                            <div class="flex flex-wrap gap-3">

                                <label class="cursor-pointer">
                                    <input id="vermelho" type="radio" name="manchester" class="hidden">
                                    <span class="px-6 py-3 rounded-full border bg-red-50 text-red-800 border-red-200">
                                        VERMELHO
                                    </span>
                                </label>

                                <label class="cursor-pointer">
                                    <input id="laranja" type="radio" name="manchester" class="hidden">
                                    <span
                                        class="px-6 py-3 rounded-full border bg-orange-50 text-orange-800 border-orange-200">
                                        LARANJA
                                    </span>
                                </label>

                                <label class="cursor-pointer">
                                    <input id="amarelo" type="radio" name="manchester" class="hidden">
                                    <span
                                        class="px-6 py-3 rounded-full border bg-yellow-50 text-yellow-900 border-yellow-200">
                                        AMARELO
                                    </span>
                                </label>

                                <label class="cursor-pointer">
                                    <input id="verde" type="radio" name="manchester" class="hidden">
                                    <span class="px-6 py-3 rounded-full border bg-green-50 text-green-800 border-green-200">
                                        VERDE
                                    </span>
                                </label>

                                <label class="cursor-pointer">
                                    <input id="azul" type="radio" name="manchester" class="hidden">
                                    <span class="px-6 py-3 rounded-full border bg-blue-50 text-blue-800 border-blue-200">
                                        AZUL
                                    </span>
                                </label>

                            </div>
                        </div>

                        {{-- Queixa --}}
                        <div>
                            <label class="sa-label">Queixa Principal</label>
                            <textarea name="nursing_history" rows="2" class="sa-input"
                                placeholder="Descreva a queixa principal do paciente..."></textarea>
                        </div>

                        <div class="flex justify-end pt-2">
                            <button type="submit" class="sa-btn-primary">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
                                </svg>
                                Salvar Triagem
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        @empty
            <div class="sa-card text-center py-12">
                <svg class="w-12 h-12 mx-auto text-gray-300 mb-3" fill="none" stroke="currentColor" stroke-width="1"
                    viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round"
                        d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <p class="text-gray-500 font-medium">Nenhum paciente aguardando triagem.</p>
                <p class="text-gray-400 text-sm mt-1">Os pacientes aparecerão aqui assim que forem registrados na recepção.
                </p>
            </div>
        @endforelse
    </div>

    {{-- SCRIPTS DE UX E AGILIDADE --}}
    <script>
        // 1. Função para abrir/fechar os cards
        function toggleTriage(id) {
            const form = document.getElementById('form-' + id);
            const chevron = document.getElementById('chevron-' + id);
            form.classList.toggle('hidden');
            chevron.classList.toggle('rotate-180');
        }

        // 2. Máscara da Pressão Arterial (Ex: 12080 vira 120/80)
        function formatPA(input, id) {
            // Remove tudo que não for número
            let value = input.value.replace(/\D/g, '');

            // Lógica para colocar a barra no lugar certo (ex: 120/80 ou 90/60)
            if (value.length > 2) {
                if (value.length === 4) {
                    value = value.substring(0, 2) + '/' + value.substring(2); // 90/60
                } else if (value.length === 5) {
                    value = value.substring(0, 3) + '/' + value.substring(3); // 120/80
                } else if (value.length >= 6) {
                    value = value.substring(0, 3) + '/' + value.substring(3, 6); // 120/100
                }
            }
            input.value = value;

            // Divide os valores e joga para os campos ocultos que o Controller vai ler
            const parts = value.split('/');
            document.getElementById('sys-' + id).value = parts[0] || '';
            document.getElementById('dia-' + id).value = parts[1] || '';

            // Dispara a checagem visual da PA
            checkVitals(id);
        }

        // 3. Alertas Visuais em Tempo Real
        function checkVitals(id) {
            // Pega os elementos e valores
            const paInput = document.getElementById('pa-' + id);
            const sys = parseInt(document.getElementById('sys-' + id).value) || 0;
            const dia = parseInt(document.getElementById('dia-' + id).value) || 0;

            const tempInput = document.getElementById('temp-' + id);
            const temp = parseFloat(tempInput.value) || 0;

            const spo2Input = document.getElementById('spo2-' + id);
            const spo2 = parseInt(spo2Input.value) || 0;

            const fcInput = document.getElementById('fc-' + id);
            const fc = parseInt(fcInput.value) || 0;

            const hgtInput = document.getElementById('hgt-' + id);
            const hgt = parseInt(hgtInput.value) || 0;

            const conscInput = document.getElementById('consc-' + id);

            // Função auxiliar para pintar o campo
            const toggleAlert = (input, condition) => {
                if (condition) {
                    input.classList.add('border-red-500', 'bg-red-50', 'text-red-700', 'ring-red-200');
                    input.classList.remove('border-gray-200', 'bg-white');
                } else {
                    input.classList.remove('border-red-500', 'bg-red-50', 'text-red-700', 'ring-red-200');
                    input.classList.add('border-gray-200', 'bg-white');
                }
            };

            // Regras Clínicas (As mesmas do seu Controller!)
            toggleAlert(paInput, (sys > 180 || dia > 120) || (sys > 0 && sys < 80));
            toggleAlert(tempInput, (temp > 37.8 || (temp > 0 && temp < 35.0)));
            toggleAlert(spo2Input, (spo2 > 0 && spo2 < 94));
            toggleAlert(fcInput, (fc > 120 || (fc > 0 && fc < 50)));
            toggleAlert(hgtInput, ((hgt > 0 && hgt < 70) || hgt > 300));
            toggleAlert(conscInput, (conscInput.value === 'INCONSCIENTE'));
        }
    </script>
</x-app-layout>