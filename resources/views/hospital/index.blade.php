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
                    <div class="space-y-4">
                        <div class="rounded-xl border border-slate-200 bg-slate-50 p-5">
                            <label for="patient_search" class="block text-base font-semibold text-slate-800 mb-2">Digite CPF ou Nome para iniciar</label>
                            <input
                                type="text"
                                id="patient_search"
                                class="sa-input text-lg"
                                placeholder="Ex: 000.000.000-00 ou nome do paciente"
                                autocomplete="off"
                                data-search-url="{{ route('hospital.citizens.search') }}"
                                data-gov-url-template="{{ route('hospital.citizens.lookup', ['cpf' => '__CPF__']) }}"
                            >

                            <input type="hidden" name="citizen_id" id="citizen_id" value="{{ old('citizen_id') }}">
                            <input type="hidden" name="new_citizen_cpf" id="new_citizen_cpf" value="{{ old('new_citizen_cpf') }}">

                            <div class="mt-2 flex flex-wrap items-center gap-2 text-xs text-slate-500">
                                <span class="font-medium">Atalhos:</span>
                                <span class="rounded bg-white border border-slate-200 px-2 py-1">F2 nova busca</span>
                                <span class="rounded bg-white border border-slate-200 px-2 py-1">Enter confirmar paciente</span>
                            </div>

                            <div id="search_feedback" class="mt-3 text-sm text-slate-600"></div>
                            <div id="search_results" class="mt-3 hidden rounded-lg border border-slate-200 bg-white shadow-sm max-h-72 overflow-y-auto"></div>
                        </div>

                        <div id="patient_state_bar" class="hidden sticky top-4 z-20 rounded-xl border border-sky-200 bg-sky-50 p-3 shadow-sm">
                            <div class="flex flex-wrap items-center gap-2">
                                <span class="text-xs font-semibold uppercase tracking-wide text-sky-700">Paciente ativo</span>
                                <span id="patient_state_name" class="text-sm font-bold text-slate-900"></span>
                                <span id="patient_state_source" class="inline-flex items-center rounded-full px-2 py-0.5 text-[11px] font-semibold"></span>
                            </div>
                        </div>

                        <div id="patient_summary" class="hidden rounded-xl border border-emerald-200 bg-emerald-50 p-4">
                            <div class="flex flex-wrap items-center gap-2 mb-2">
                                <span id="patient_summary_name" class="text-base font-bold text-slate-900"></span>
                                <span id="patient_source_badge" class="inline-flex items-center rounded-full px-2.5 py-1 text-xs font-semibold"></span>
                            </div>
                            <div id="patient_summary_meta" class="text-sm text-slate-700"></div>
                        </div>

                        <div id="quick_registration_fields" class="hidden rounded-xl border border-slate-200 bg-white p-4">
                            <h5 class="text-sm font-semibold text-slate-800 mb-3">Cadastro Rápido</h5>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                                <div>
                                    <label for="new_citizen_name" class="sa-label">Nome Completo</label>
                                    <input type="text" name="new_citizen_name" id="new_citizen_name" class="sa-input text-sm" value="{{ old('new_citizen_name') }}">
                                </div>
                                <div>
                                    <label for="new_citizen_birth" class="sa-label">Data de Nascimento</label>
                                    <input type="date" name="new_citizen_birth" id="new_citizen_birth" class="sa-input text-sm" value="{{ old('new_citizen_birth') }}">
                                </div>
                                <div>
                                    <label for="new_citizen_phone" class="sa-label">Telefone para Contato</label>
                                    <input type="text" name="new_citizen_phone" id="new_citizen_phone" class="sa-input text-sm" value="{{ old('new_citizen_phone') }}" placeholder="(43) 99999-9999">
                                </div>
                                <div>
                                    <label for="new_citizen_address" class="sa-label">Endereco</label>
                                    <input type="text" name="new_citizen_address" id="new_citizen_address" class="sa-input text-sm" value="{{ old('new_citizen_address') }}" placeholder="Rua, numero, bairro">
                                </div>
                            </div>
                            <p id="quick_registration_hint" class="mt-2 text-xs text-slate-500">Se nao houver retorno no Gov.Assai, informe nome e data de nascimento para continuar.</p>
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
                            <input type="number" name="systolic_pressure" id="pa_sistolica" class="sa-input text-center" placeholder="Ex: 120">
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
        document.addEventListener('DOMContentLoaded', function () {
            const searchInput = document.getElementById('patient_search');
            const hiddenCitizenId = document.getElementById('citizen_id');
            const hiddenCpf = document.getElementById('new_citizen_cpf');
            const newName = document.getElementById('new_citizen_name');
            const newBirth = document.getElementById('new_citizen_birth');
            const newPhone = document.getElementById('new_citizen_phone');
            const newAddress = document.getElementById('new_citizen_address');
            const feedback = document.getElementById('search_feedback');
            const results = document.getElementById('search_results');
            const summary = document.getElementById('patient_summary');
            const summaryName = document.getElementById('patient_summary_name');
            const summaryMeta = document.getElementById('patient_summary_meta');
            const sourceBadge = document.getElementById('patient_source_badge');
            const stateBar = document.getElementById('patient_state_bar');
            const stateName = document.getElementById('patient_state_name');
            const stateSource = document.getElementById('patient_state_source');
            const quickFields = document.getElementById('quick_registration_fields');
            const quickHint = document.getElementById('quick_registration_hint');

            if (!searchInput) {
                return;
            }

            const onlyDigits = (value) => (value || '').replace(/\D+/g, '');
            const initials = (name) => (name || '')
                .split(' ')
                .filter(Boolean)
                .slice(0, 2)
                .map((part) => part[0]?.toUpperCase())
                .join('') || 'SN';

            let debounceTimer = null;
            let activeResults = [];
            let highlightedIndex = 0;
            let lastResolvedCpf = '';

            const setFeedback = (message, tone = 'slate') => {
                const palette = {
                    slate: 'text-sm text-slate-600',
                    blue: 'text-sm text-blue-700',
                    green: 'text-sm text-green-700',
                    amber: 'text-sm text-amber-700',
                    red: 'text-sm text-red-700',
                };
                feedback.className = palette[tone] || palette.slate;
                feedback.textContent = message;
            };

            const showSummary = (payload) => {
                summary.classList.remove('hidden');
                summaryName.textContent = payload.name || 'Paciente sem nome';
                summaryMeta.textContent = [payload.cpf, payload.birthDate].filter(Boolean).join(' | ');
                stateBar.classList.remove('hidden');
                stateName.textContent = payload.name || 'Paciente sem nome';

                if (payload.source === 'LOCAL') {
                    sourceBadge.className = 'inline-flex items-center rounded-full px-2.5 py-1 text-xs font-semibold bg-blue-100 text-blue-800';
                    sourceBadge.textContent = 'Cadastro Local';
                    stateSource.className = 'inline-flex items-center rounded-full px-2 py-0.5 text-[11px] font-semibold bg-blue-100 text-blue-800';
                    stateSource.textContent = 'Cadastro Local';
                    return;
                }

                if (payload.source === 'GOV') {
                    sourceBadge.className = 'inline-flex items-center rounded-full px-2.5 py-1 text-xs font-semibold bg-emerald-100 text-emerald-800';
                    sourceBadge.textContent = 'Verificado pelo Municipio';
                    stateSource.className = 'inline-flex items-center rounded-full px-2 py-0.5 text-[11px] font-semibold bg-emerald-100 text-emerald-800';
                    stateSource.textContent = 'Verificado pelo Municipio';
                    return;
                }

                sourceBadge.className = 'inline-flex items-center rounded-full px-2.5 py-1 text-xs font-semibold bg-amber-100 text-amber-800';
                sourceBadge.textContent = 'Cadastro Manual';
                stateSource.className = 'inline-flex items-center rounded-full px-2 py-0.5 text-[11px] font-semibold bg-amber-100 text-amber-800';
                stateSource.textContent = 'Cadastro Manual';
            };

            const clearSummary = () => {
                summary.classList.add('hidden');
                summaryName.textContent = '';
                summaryMeta.textContent = '';
                sourceBadge.textContent = '';
                stateBar.classList.add('hidden');
                stateName.textContent = '';
                stateSource.textContent = '';
            };

            const showQuickFields = (show, readOnly = false) => {
                quickFields.classList.toggle('hidden', !show);
                [newName, newBirth].forEach((field) => {
                    field.readOnly = readOnly;
                    field.classList.toggle('bg-slate-100', readOnly);
                });
            };

            const resetSelection = () => {
                hiddenCitizenId.value = '';
                hiddenCpf.value = '';
                newName.value = '';
                newBirth.value = '';
                newPhone.value = '';
                newAddress.value = '';
                clearSummary();
                showQuickFields(false, false);
                quickHint.textContent = 'Se nao houver retorno no Gov.Assai, informe nome e data de nascimento para continuar.';
            };

            const hideResults = () => {
                results.innerHTML = '';
                results.classList.add('hidden');
                activeResults = [];
                highlightedIndex = 0;
            };

            const selectLocalCitizen = (citizen) => {
                hiddenCitizenId.value = citizen.id;
                hiddenCpf.value = '';
                newName.value = '';
                newBirth.value = '';
                newPhone.value = '';
                newAddress.value = '';
                showQuickFields(false, false);
                showSummary({
                    name: citizen.full_name,
                    cpf: citizen.cpf,
                    birthDate: citizen.birth_date,
                    source: 'LOCAL',
                });
                searchInput.value = citizen.full_name;
                setFeedback('Paciente encontrado na base local. Pressione Enter para confirmar e continuar.', 'green');
                hideResults();

                const systolic = document.getElementById('pa_sistolica');
                if (systolic) {
                    systolic.focus();
                }
            };

            const activateManualScenario = (cpfDigits) => {
                hiddenCitizenId.value = '';
                hiddenCpf.value = cpfDigits;
                newName.value = '';
                newBirth.value = '';
                showQuickFields(true, false);
                quickHint.textContent = 'Paciente nao localizado na base local nem no Gov.Assai. Complete os campos para cadastro manual.';
                showSummary({
                    name: 'Novo paciente',
                    cpf: cpfDigits,
                    birthDate: '',
                    source: 'MANUAL',
                });
                setFeedback('CPF nao localizado. Cadastro manual liberado.', 'amber');
                newName.focus();
            };

            const activateGovScenario = (cpfDigits, payload) => {
                const citizen = payload?.data?.cidadao ?? {};
                const address = payload?.data?.endereco ?? {};

                hiddenCitizenId.value = '';
                hiddenCpf.value = cpfDigits;
                newName.value = citizen.nome || '';
                newBirth.value = citizen.data_nascimento || '';
                newPhone.value = citizen.telefone || '';
                newAddress.value = [address.logradouro, address.numero, address.bairro].filter(Boolean).join(', ');

                showQuickFields(true, true);
                quickHint.textContent = 'Dados validados no Gov.Assai. Confirme telefone/endereco e prossiga.';
                showSummary({
                    name: citizen.nome || 'Paciente validado no Gov.Assai',
                    cpf: cpfDigits,
                    birthDate: citizen.data_nascimento || '',
                    source: 'GOV',
                });
                setFeedback('Paciente validado no Gov.Assai. Cadastro rapido preenchido automaticamente.', 'green');
                newPhone.focus();
            };

            const renderResults = (items) => {
                activeResults = items;
                highlightedIndex = 0;

                if (!items.length) {
                    hideResults();
                    return;
                }

                const html = items.map((item, index) => {
                    const selectedClass = index === highlightedIndex ? 'bg-slate-100' : '';
                    return `
                        <button type="button" class="w-full text-left px-3 py-2 border-b border-slate-100 hover:bg-slate-50 ${selectedClass}" data-result-index="${index}">
                            <div class="flex items-center gap-3">
                                <span class="inline-flex h-8 w-8 items-center justify-center rounded-full bg-slate-200 text-xs font-bold text-slate-700">${initials(item.full_name)}</span>
                                <span>
                                    <span class="block text-sm font-semibold text-slate-900">${item.full_name}</span>
                                    <span class="block text-xs text-slate-500">${item.cpf || 'CPF nao informado'} ${item.birth_date ? `| Nasc: ${item.birth_date}` : ''}</span>
                                </span>
                            </div>
                        </button>
                    `;
                }).join('');

                results.innerHTML = html;
                results.classList.remove('hidden');

                results.querySelectorAll('[data-result-index]').forEach((button) => {
                    button.addEventListener('click', () => {
                        const idx = Number(button.dataset.resultIndex || 0);
                        const selected = activeResults[idx];
                        if (selected) {
                            selectLocalCitizen(selected);
                        }
                    });
                });
            };

            const fetchLocalCandidates = async (term) => {
                const url = new URL(searchInput.dataset.searchUrl, window.location.origin);
                url.searchParams.set('q', term);

                const response = await fetch(url.toString(), {
                    headers: {
                        Accept: 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                });

                const payload = await response.json();
                return Array.isArray(payload?.data) ? payload.data : [];
            };

            const fetchGovByCpf = async (cpfDigits) => {
                const url = searchInput.dataset.govUrlTemplate.replace('__CPF__', cpfDigits);
                const response = await fetch(url, {
                    headers: {
                        Accept: 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                });

                const payload = await response.json();
                return { ok: response.ok, payload };
            };

            const resolveCpfFlow = async (cpfDigits) => {
                if (cpfDigits.length !== 11 || cpfDigits === lastResolvedCpf) {
                    return;
                }

                lastResolvedCpf = cpfDigits;
                setFeedback('Consultando base local e Gov.Assai...', 'blue');

                try {
                    const [localList, govResult] = await Promise.all([
                        fetchLocalCandidates(cpfDigits),
                        fetchGovByCpf(cpfDigits),
                    ]);

                    const exactLocal = localList.length > 0 ? localList[0] : null;
                    if (exactLocal) {
                        selectLocalCitizen(exactLocal);
                        return;
                    }

                    if (govResult.ok && govResult.payload?.success) {
                        activateGovScenario(cpfDigits, govResult.payload);
                        return;
                    }

                    activateManualScenario(cpfDigits);
                } catch (error) {
                    setFeedback('Falha na consulta automatica. Continue com cadastro manual.', 'amber');
                    activateManualScenario(cpfDigits);
                }
            };

            const handleSearchInput = async () => {
                const term = searchInput.value.trim();
                const digits = onlyDigits(term);

                if (!term) {
                    resetSelection();
                    hideResults();
                    setFeedback('Digite CPF ou Nome para iniciar a busca.', 'slate');
                    lastResolvedCpf = '';
                    return;
                }

                if (digits.length === 11) {
                    hideResults();
                    resolveCpfFlow(digits);
                    return;
                }

                hiddenCitizenId.value = '';
                hiddenCpf.value = '';
                clearSummary();
                showQuickFields(false, false);
                setFeedback('Buscando pacientes por nome...', 'blue');

                try {
                    const list = await fetchLocalCandidates(term);
                    if (list.length) {
                        setFeedback('Selecione um paciente da lista ou continue digitando o CPF completo.', 'slate');
                    } else {
                        setFeedback('Nenhum paciente encontrado para este nome.', 'amber');
                    }
                    renderResults(list);
                } catch (error) {
                    setFeedback('Falha ao consultar base local. Tente novamente.', 'red');
                    hideResults();
                }
            };

            searchInput.addEventListener('input', () => {
                if (debounceTimer) {
                    clearTimeout(debounceTimer);
                }

                debounceTimer = setTimeout(handleSearchInput, 500);
            });

            searchInput.addEventListener('keydown', (event) => {
                if (event.key === 'Enter') {
                    if (!results.classList.contains('hidden') && activeResults.length > 0) {
                        event.preventDefault();
                        const selected = activeResults[highlightedIndex] || activeResults[0];
                        if (selected) {
                            selectLocalCitizen(selected);
                        }
                    }
                }

                if (event.key === 'ArrowDown' && activeResults.length > 0) {
                    event.preventDefault();
                    highlightedIndex = Math.min(highlightedIndex + 1, activeResults.length - 1);
                    renderResults(activeResults);
                }

                if (event.key === 'ArrowUp' && activeResults.length > 0) {
                    event.preventDefault();
                    highlightedIndex = Math.max(highlightedIndex - 1, 0);
                    renderResults(activeResults);
                }
            });

            document.addEventListener('keydown', (event) => {
                if (event.key === 'F2') {
                    event.preventDefault();
                    searchInput.value = '';
                    resetSelection();
                    hideResults();
                    setFeedback('Nova busca iniciada. Digite CPF ou nome.', 'slate');
                    searchInput.focus();
                }
            });

            document.addEventListener('click', (event) => {
                if (!results.contains(event.target) && event.target !== searchInput) {
                    hideResults();
                }
            });

            setFeedback('Digite CPF ou Nome para iniciar a busca. O cursor ja esta pronto para atendimento.', 'slate');
            searchInput.focus();
        });
    </script>
</x-app-layout>
