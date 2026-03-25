<x-app-layout>
    <x-slot name="header">
        <div class="sa-page-header">
            <h2 class="sa-page-title">Módulo Hospitalar (M7)</h2>
            <p class="sa-page-subtitle">Atendimento Centralizado, Triagem e Prontuário</p>
        </div>
    </x-slot>

    <div class="space-y-8">
        @if (session('status'))
            <div class="sa-alert-success sa-fade-in">
                <svg class="w-5 h-5 text-green-600 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                <span class="text-sm font-medium">{{ session('status') }}</span>
            </div>
        @endif

        @if ($errors->any())
            <div class="bg-red-50 border-l-4 border-red-500 p-4 sa-fade-in">
                <div class="flex">
                    <div class="ml-3">
                        <h3 class="text-sm font-medium text-red-800">Foram encontrados erros de validação:</h3>
                        <ul class="mt-2 text-sm text-red-700 list-disc list-inside">
                            @foreach($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            </div>
        @endif

        <div class="sa-card sa-fade-in">
            <div class="sa-card-header">
                <h3 class="sa-card-title">Novo Atendimento</h3>
            </div>
            
            <form method="POST" action="{{ route('hospital.store') }}" class="space-y-8">
                @csrf
                
                {{-- 1. IDENTIFICAÇÃO DO PACIENTE --}}
                <div>
                    <h4 class="text-lg font-bold text-gray-800 border-b border-gray-200 pb-2 mb-4">1. Identificação do Paciente</h4>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="bg-blue-50 p-4 rounded-lg border border-blue-100">
                            <label class="sa-label text-blue-800">Paciente Existente</label>
                            <select name="citizen_id" id="citizen_id" class="sa-select">
                                <option value="">Selecione na base de dados...</option>
                                @foreach($citizens as $c)
                                    <option value="{{ $c->id }}">{{ $c->full_name }} (CPF: {{ $c->cpf ?? 'S/N' }})</option>
                                @endforeach
                            </select>
                            <p class="text-xs text-blue-600 mt-2">Escolha esta opção se o paciente já tem cadastro municipal.</p>
                        </div>
                        
                        <div class="bg-gray-50 p-4 rounded-lg border border-gray-200">
                            <label class="sa-label text-gray-700">Ou Cadastre um Novo Paciente Rápido</label>
                            <div class="space-y-3">
                                <input type="text" name="new_citizen_name" class="sa-input text-sm" placeholder="Nome Completo" id="new_citizen_name">
                                <div class="grid grid-cols-2 gap-2">
                                    <input type="text" name="new_citizen_cpf" id="new_citizen_cpf" class="sa-input text-sm" placeholder="CPF (000.000.000-00)" maxlength="14">
                                    <input type="date" name="new_citizen_birth" id="new_citizen_birth" class="sa-input text-sm">
                                </div>
                                <div class="flex items-center gap-2">
                                    <button
                                        type="button"
                                        id="btn_lookup_cpf"
                                        class="sa-btn-secondary text-sm"
                                        data-url-template="{{ route('hospital.citizens.lookup', ['cpf' => '__CPF__']) }}"
                                    >
                                        Consultar CPF no Gov.Assai
                                    </button>
                                    <span id="gov_lookup_status" class="text-xs text-gray-500"></span>
                                </div>
                            </div>
                            <p class="text-xs text-gray-500 mt-2">Se o CPF nao for de Assai, o atendimento hospitalar continua normalmente com cadastro manual.</p>
                        </div>
                    </div>
                </div>

                {{-- 2. SINAIS VITAIS (TRIAGEM RÁPIDA) --}}
                <div>
                    <h4 class="text-lg font-bold text-gray-800 border-b border-gray-200 pb-2 mb-4 flex items-center">
                        2. Sinais Vitais (Triagem)
                        <span class="ml-2 text-xs font-normal text-gray-400">Opcional, porém recomendado.</span>
                    </h4>
                    <div class="grid grid-cols-2 md:grid-cols-6 gap-4">
                        <div>
                            <label class="sa-label text-xs">PA Sistólica</label>
                            <input type="number" name="systolic_pressure" class="sa-input text-center" placeholder="Ex: 120">
                        </div>
                        <div>
                            <label class="sa-label text-xs">PA Diastólica</label>
                            <input type="number" name="diastolic_pressure" class="sa-input text-center" placeholder="Ex: 80">
                        </div>
                        <div>
                            <label class="sa-label text-xs">FC (bpm)</label>
                            <input type="number" name="heart_rate" class="sa-input text-center" placeholder="Ex: 85">
                        </div>
                        <div>
                            <label class="sa-label text-xs">Temp (°C)</label>
                            <input type="number" step="0.1" name="temperature" class="sa-input text-center" placeholder="Ex: 36.5">
                        </div>
                        <div>
                            <label class="sa-label text-xs">SpO₂ (%)</label>
                            <input type="number" name="spo2" class="sa-input text-center" placeholder="Ex: 98">
                        </div>
                        <div>
                            <label class="sa-label text-xs">HGT (mg/dL)</label>
                            <input type="number" name="hgt" class="sa-input text-center" placeholder="Ex: 99">
                        </div>
                    </div>
                </div>

                {{-- 3. PRONTUÁRIO CLÍNICO (SOAP) --}}
                <div>
                    <h4 class="text-lg font-bold text-gray-800 border-b border-gray-200 pb-2 mb-4">3. Evolução Médica (SOAP) e Conduta</h4>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                        <div>
                            <label class="sa-label text-blue-600">O — Objetivo (Exame Clínico) *</label>
                            <textarea name="soap_objective" rows="4" class="sa-input" placeholder="Exame físico e dados objetivos do plantão..." required></textarea>
                        </div>
                        <div>
                            <label class="sa-label text-purple-600">A — Avaliação Clínica *</label>
                            <textarea name="soap_assessment" rows="4" class="sa-input" placeholder="Hipótese diagnóstica, raciocínio clínico..." required></textarea>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
                        <div>
                            <label class="sa-label">Diagnóstico Texto *</label>
                            <input type="text" name="diagnosis" class="sa-input" placeholder="Descrição do diagnóstico principal" required>
                        </div>
                        <div>
                            <label class="sa-label">CID-10 Principal *</label>
                            <input type="text" name="cid_10" class="sa-input uppercase" placeholder="Ex: J06.9" required maxlength="10">
                        </div>
                        <div>
                            <label class="sa-label">Desfecho do Plantão *</label>
                            <select name="outcome" class="sa-select" required>
                                <option value="">Selecione...</option>
                                <option value="ALTA">🏠 Alta Médica</option>
                                <option value="INTERNACAO">🏥 Internação</option>
                                <option value="TRANSFERENCIA">➡️ Transferência</option>
                                <option value="OBITO">⚫ Óbito</option>
                            </select>
                        </div>
                    </div>

                    <div class="mb-4">
                        <label class="sa-label">CIDs Secundários (Apenas preencha se houver)</label>
                        <div class="flex flex-wrap gap-2">
                            @for ($i = 0; $i < 5; $i++)
                                <input type="text" name="secondary_cids[]" class="sa-input flex-1 text-center uppercase text-sm min-w-[120px]" placeholder="CID {{ $i + 1 }}" maxlength="10">
                            @endfor
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div>
                            <label class="sa-label">Procedimentos (SIGTAP)</label>
                            <textarea name="procedures" rows="2" class="sa-input" placeholder="Sutura, curativo..."></textarea>
                        </div>
                        <div>
                            <label class="sa-label">Exames</label>
                            <textarea name="exams" rows="2" class="sa-input" placeholder="Raio-X, Hemograma..."></textarea>
                        </div>
                        <div>
                            <label class="sa-label">Orientações</label>
                            <textarea name="guidance" rows="2" class="sa-input" placeholder="Receitas ext., atestado..."></textarea>
                        </div>
                    </div>
                </div>

                <div class="pt-4 border-t border-gray-200 flex justify-end">
                    <button type="submit" class="sa-btn-primary px-8 text-lg">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
                        Registrar Atendimento Hospitalar Completo
                    </button>
                </div>
            </form>
        </div>

        {{-- PLANTÃO RECENTE --}}
        <div class="sa-card sa-fade-in mb-12">
            <div class="sa-card-header">
                <h3 class="sa-card-title">Prontuários Registrados no Plantão (Recentes)</h3>
            </div>
            <div class="overflow-x-auto">
                <table class="sa-table">
                    <thead>
                        <tr>
                            <th>Data/Hora</th>
                            <th>Paciente</th>
                            <th>CID-10</th>
                            <th>Desfecho</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($recentRecords as $record)
                            <tr>
                                <td class="text-sm text-gray-500 whitespace-nowrap">{{ $record->signed_at?->format('d/m/Y H:i') }}</td>
                                <td class="font-medium text-gray-900">{{ $record->attendance->citizen->full_name ?? 'Paciente Excluído' }}</td>
                                <td>
                                    <span class="bg-gray-100 text-gray-800 text-xs font-bold px-2 py-1 rounded">{{ $record->cid_10 }}</span>
                                </td>
                                <td>
                                    @php
                                        $badges = [
                                            'ALTA' => 'bg-green-100 text-green-800',
                                            'INTERNACAO' => 'bg-blue-100 text-blue-800',
                                            'TRANSFERENCIA' => 'bg-yellow-100 text-yellow-800',
                                            'OBITO' => 'bg-red-100 text-red-800',
                                        ];
                                    @endphp
                                    <span class="text-xs font-semibold px-2 py-1 rounded-full {{ $badges[$record->outcome] ?? 'bg-gray-100 text-gray-600' }}">
                                        {{ $record->outcome }}
                                    </span>
                                </td>
                                <td class="text-sm">
                                    <span class="text-gray-400">Ver ficha (MVP)</span>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center py-6 text-gray-400 text-sm">Nenhum prontuário registrado recentemente.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script>
        // UX interaction: disable "existing citizen" se "new citizen" is being typed
        document.addEventListener('DOMContentLoaded', function () {
            const selectCitizen = document.getElementById('citizen_id');
            const newName = document.getElementById('new_citizen_name');
            const cpfInput = document.getElementById('new_citizen_cpf');
            const birthInput = document.getElementById('new_citizen_birth');
            const lookupButton = document.getElementById('btn_lookup_cpf');
            const lookupStatus = document.getElementById('gov_lookup_status');

            const onlyDigits = (value) => (value || '').replace(/\D+/g, '');
            
            newName.addEventListener('input', function() {
                if(this.value.length > 0) {
                    selectCitizen.value = "";
                }
            });

            selectCitizen.addEventListener('change', function() {
                if(this.value !== "") {
                    newName.value = "";
                    cpfInput.value = "";
                    birthInput.value = "";
                    lookupStatus.textContent = '';
                }
            });

            lookupButton.addEventListener('click', async function () {
                const cpf = onlyDigits(cpfInput.value);

                if (cpf.length !== 11) {
                    lookupStatus.textContent = 'Informe um CPF valido com 11 digitos.';
                    lookupStatus.className = 'text-xs text-red-600';
                    return;
                }

                lookupStatus.textContent = 'Consultando Gov.Assai...';
                lookupStatus.className = 'text-xs text-blue-600';

                const url = lookupButton.dataset.urlTemplate.replace('__CPF__', cpf);

                try {
                    const response = await fetch(url, {
                        method: 'GET',
                        headers: {
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                        },
                    });

                    const payload = await response.json();

                    if (response.ok && payload.success) {
                        const cidadao = payload.data?.cidadao ?? {};

                        if (!newName.value && cidadao.nome) {
                            newName.value = cidadao.nome;
                        }

                        if (!birthInput.value && cidadao.data_nascimento) {
                            birthInput.value = cidadao.data_nascimento;
                        }

                        selectCitizen.value = '';
                        lookupStatus.textContent = 'CPF localizado. Dados preenchidos automaticamente.';
                        lookupStatus.className = 'text-xs text-green-600';
                        return;
                    }

                    lookupStatus.textContent = payload.message || 'CPF nao encontrado no Gov.Assai. Continue com cadastro manual.';
                    lookupStatus.className = 'text-xs text-amber-700';
                } catch (error) {
                    lookupStatus.textContent = 'Falha ao consultar Gov.Assai. Continue com cadastro manual.';
                    lookupStatus.className = 'text-xs text-amber-700';
                }
            });
        });
    </script>
</x-app-layout>
