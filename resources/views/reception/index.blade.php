<x-app-layout>
    <x-slot name="header">
        <div class="sa-page-header">
            <h2 class="sa-page-title">Recepção</h2>
            <p class="sa-page-subtitle">Cadastro de pacientes e fila de atendimento</p>
        </div>
    </x-slot>

    <div class="space-y-6">
        <div class="bg-amber-50 border-l-4 border-amber-500 rounded-md p-4 sa-fade-in">
            <div class="flex items-start gap-3">
                <svg class="w-5 h-5 text-amber-700 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor"
                    stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round"
                        d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <div>
                    <h3 class="text-sm font-semibold text-amber-900">Regra obrigatoria da Recepcao</h3>
                    <p class="mt-1 text-sm text-amber-800">
                        Sem validacao positiva de CPF no Gov.Assai, o atendimento nao pode ser aberto na recepcao.
                    </p>
                </div>
            </div>
        </div>

        @if ($errors->any())
            <div class="bg-red-50 border-l-4 border-red-500 p-4 sa-fade-in">
                <h3 class="text-sm font-medium text-red-800">Nao foi possivel concluir a recepcao:</h3>
                <ul class="mt-2 text-sm text-red-700 list-disc list-inside">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        {{-- Success Alert --}}
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

        {{-- Registration Form --}}
        <div class="sa-card sa-fade-in">
            <div class="sa-card-header">
                <h3 class="sa-card-title">
                    <svg class="w-5 h-5 inline-block mr-1 text-sa-primary" fill="none" stroke="currentColor"
                        stroke-width="1.5" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M18 7.5v3m0 0v3m0-3h3m-3 0h-3m-2.25-4.125a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zM3 19.235v-.11a6.375 6.375 0 0112.75 0v.109A12.318 12.318 0 019.374 21c-2.331 0-4.512-.645-6.374-1.766z" />
                    </svg>
                    Novo Atendimento
                </h3>
            </div>
            <p class="text-xs text-amber-700 mb-3">
                Regras de recepcao: so permite prosseguir quando o CPF for validado como residente no Gov.Assai.
            </p>
            <form method="POST" action="{{ route('reception.store') }}" class="space-y-4" id="reception-form">
                @csrf
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    <div class="lg:col-span-2">
                        <label class="sa-label">CPF *</label>
                        <input id="cpf" name="cpf" value="{{ old('cpf') }}" class="sa-input" required
                            placeholder="000.000.000-00"
                            data-url-template="{{ route('reception.citizens.lookup', ['cpf' => '__CPF__']) }}">
                        <p class="text-xs text-gray-500 mt-1">A recepcao consulta automaticamente o Gov.Assai ao
                            completar os 11 digitos do CPF.</p>
                        <p id="gov_lookup_status" class="text-xs text-gray-500 mt-1"></p>
                    </div>
                    <div>
                        <label class="sa-label">Cartão SUS</label>
                        <input name="cns" value="{{ old('cns') }}" class="sa-input"
                            placeholder="Somente se Gov.Assai nao retornar">
                    </div>
                    <div>
                        <label class="sa-label">Tipo de Atendimento *</label>
                        <select name="care_type" class="sa-select" required>
                            <option value="">Selecione...</option>
                            <option value="UBS" @selected(old('care_type') === 'UBS')>UBS</option>
                            <option value="HOSPITALAR" @selected(old('care_type') === 'HOSPITALAR')>Hospitalar</option>
                            <option value="URGENTE" @selected(old('care_type') === 'URGENTE')>Urgente</option>
                        </select>
                    </div>
                    <div>
                        <label class="sa-label">Unidade de Saúde *</label>
                        <select name="health_unit_id" class="sa-select" required>
                            <option value="">Selecione...</option>
                            @foreach($units as $unit)
                                <option value="{{ $unit->id }}" @selected((string) old('health_unit_id') === (string) $unit->id)>
                                    {{ $unit->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div id="fallback-section" class="lg:col-span-3 mt-2 pt-3 border-t border-gray-200 hidden">
                        <p class="text-xs text-gray-600 mb-3">Preencha abaixo apenas se o Gov.Assai nao retornar nome ou
                            data de nascimento:</p>
                    </div>
                    <div id="fallback-name" class="hidden">
                        <label class="sa-label">Nome Completo (fallback)</label>
                        <input id="full_name" name="full_name" value="{{ old('full_name') }}" class="sa-input"
                            placeholder="Somente se solicitado pelo sistema">
                    </div>
                    <div id="fallback-birth" class="hidden">
                        <label class="sa-label">Data de Nascimento (fallback)</label>
                        <input id="birth_date" name="birth_date" value="{{ old('birth_date') }}" type="date"
                            class="sa-input">
                    </div>
                    <div>
                        <label class="sa-label">Acidente de Trabalho</label>
                        <select name="work_accident" class="sa-select">
                            <option value="0" @selected(old('work_accident') === '0')>Nao</option>
                            <option value="1" @selected(old('work_accident') === '1')>Sim</option>
                        </select>
                    </div>
                    <div class="lg:col-span-2">
                        <label class="sa-label">Resumo do Motivo</label>
                        <input name="summary_reason" value="{{ old('summary_reason') }}" class="sa-input"
                            placeholder="Queixa principal resumida">
                    </div>
                </div>
                <div class="flex justify-end pt-2">
                    <button type="submit" class="sa-btn-primary" id="reception-submit" disabled>
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                        </svg>
                        Registrar Atendimento
                    </button>
                </div>
            </form>
        </div>

        {{-- Queue Table --}}
        <div class="sa-card-header flex flex-wrap items-center justify-between gap-4">
            <div>
                <h3 class="sa-card-title">Fila de Hoje</h3>
                <span class="sa-badge sa-badge-info">{{ $attendances->count() }} pacientes</span>
            </div>

            <div class="flex items-center gap-2">
                <div class="relative">
                    <input type="text" id="search-input" value="{{ request('search') }}"
                        placeholder="Buscar paciente por nome..." class="sa-input pr-10" style="min-width: 300px;"
                        autocomplete="off">
                </div>

                <select id="status-filter" class="sa-select">
                    <option value="">Todos os status</option>
                    <option value="RECEPCAO" @selected(request('status') === 'RECEPCAO')>Recepção</option>
                    <option value="TRIAGEM_CONCLUIDA" @selected(request('status') === 'TRIAGEM_CONCLUIDA')>Triagem
                    </option>
                    <option value="ENCERRADO" @selected(request('status') === 'ENCERRADO')>Encerrado</option>
                </select>

                <button type="submit" id="search-button" class="sa-btn-primary">
                    Buscar
                </button>

                @if(request()->filled('search'))
                    <a href="{{ route('reception.index') }}" class="text-sm text-red-500 hover:underline">
                        Limpar
                    </a>
                @endif
            </div>
        </div>
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
                    <th>Ação</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($attendances as $a)
                    <tr>
                        <td>
                            <span
                                class="inline-flex items-center justify-center w-12 h-8 rounded-lg bg-sa-primary/10 text-sa-primary font-bold text-sm">
                                {{ $a->queue_password }}
                            </span>
                        </td>
                        <td class="font-medium text-gray-900">{{ $a->citizen->full_name ?? '—' }}</td>
                        <td class="text-gray-500 text-xs font-mono">{{ $a->citizen->cpf ?? '—' }}</td>
                        <td>
                            @php
                                $resColors = [
                                    'RESIDENTE' => 'sa-badge-success',
                                    'NAO_RESIDENTE' => 'sa-badge-warning',
                                    'PENDENTE' => 'sa-badge-gray',
                                ];
                            @endphp
                            <span
                                class="sa-badge {{ $resColors[$a->residence_status] ?? 'sa-badge-gray' }}">{{ $a->residence_status ?? '—' }}</span>
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
                            <span
                                class="sa-badge {{ $statusColors[$a->status] ?? 'sa-badge-gray' }}">{{ $a->status }}</span>
                        </td>
                        <td class="text-gray-500 text-xs">{{ $a->created_at->format('H:i') }}</td>
                        <td>
                            @if($a->status !== 'ENCERRADO')
                                <button class="call-btn sa-btn-primary text-xs" data-id="{{ $a->id }}"
                                    data-name="{{ $a->citizen->full_name }}">
                                    Chamar
                                </button>
                            @else
                                <span class="text-gray-400 text-xs">Finalizado</span>
                            @endif
                        </td>
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

    <div id="call-modal" class="hidden fixed inset-0 bg-black/50 flex items-center justify-center z-50">
        <div class="bg-white rounded-lg p-6 w-full max-w-sm shadow-lg">

            <h3 class="text-lg font-semibold mb-4">
                Chamar paciente
            </h3>

            <p id="call-patient-name" class="text-sm text-gray-600 mb-4"></p>

            <div class="mb-3">
                <label class="text-xs text-gray-600">Sala / Guichê</label>
                <input id="call-room" class="sa-input mt-1" placeholder="Ex: Sala 2 ou Guichê 1">
            </div>

            <div class="space-y-3">
                <button data-type="TRIAGEM" class="call-type-btn w-full sa-btn-primary text-sm">
                    🩺 Triagem
                </button>

                <button data-type="ATENDIMENTO" class="call-type-btn w-full sa-btn-primary text-sm">
                    👨‍⚕️ Atendimento
                </button>
            </div>

            <div class="flex justify-end mt-4">
                <button id="cancel-call" class="text-sm text-gray-500 hover:underline">
                    Cancelar
                </button>
            </div>

        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {

            // ── CPF / recepção ──────────────────────────────────────────────
            const form = document.getElementById('reception-form');
            const cpfInput = document.getElementById('cpf');
            const submitButton = document.getElementById('reception-submit');
            const lookupStatus = document.getElementById('gov_lookup_status');
            const careTypeInput = document.querySelector('select[name="care_type"]');
            const fallbackSection = document.getElementById('fallback-section');
            const fallbackName = document.getElementById('fallback-name');
            const fallbackBirth = document.getElementById('fallback-birth');
            const fullNameInput = document.getElementById('full_name');
            const birthDateInput = document.getElementById('birth_date');

            const onlyDigits = (value) => (value || '').replace(/\D+/g, '');

            let debounceTimer = null;
            let lastLookupCpf = '';
            let lookupInFlight = false;
            let govValidated = false;

            const setFallbackVisibility = (show) => {
                fallbackSection?.classList.toggle('hidden', !show);
                fallbackName?.classList.toggle('hidden', !show);
                fallbackBirth?.classList.toggle('hidden', !show);
            };

            const setStatus = (message, tone) => {
                if (!lookupStatus) return;

                const palette = {
                    neutral: 'text-xs text-gray-500 mt-1',
                    info: 'text-xs text-blue-600 mt-1',
                    success: 'text-xs text-green-700 mt-1',
                    warning: 'text-xs text-amber-700 mt-1',
                    danger: 'text-xs text-red-600 mt-1',
                };

                lookupStatus.textContent = message;
                lookupStatus.className = palette[tone] || palette.neutral;
            };

            const clearAutofill = () => {
                if (fullNameInput) fullNameInput.value = '';
                if (birthDateInput) birthDateInput.value = '';
                setFallbackVisibility(false);
            };

            const setSubmitAvailability = () => {
                if (!submitButton || !cpfInput) return;

                const cpf = onlyDigits(cpfInput.value);

                submitButton.disabled =
                    !(govValidated && cpf.length === 11 && !lookupInFlight);

                submitButton.classList.toggle('opacity-70', submitButton.disabled);
                submitButton.classList.toggle('cursor-not-allowed', submitButton.disabled);
            };

            const invalidateValidationState = () => {
                govValidated = false;
                setSubmitAvailability();
            };

            const performLookup = async () => {
                const cpf = onlyDigits(cpfInput?.value);

                if (cpf.length !== 11) {
                    lastLookupCpf = '';
                    setStatus('Informe um CPF valido com 11 digitos.', 'danger');
                    clearAutofill();
                    invalidateValidationState();
                    return;
                }

                if (lookupInFlight || cpf === lastLookupCpf) return;

                lookupInFlight = true;
                lastLookupCpf = cpf;
                invalidateValidationState();
                setStatus('Consultando Gov.Assai...', 'info');
                setSubmitAvailability();

                try {
                    const url = cpfInput.dataset.urlTemplate.replace('__CPF__', cpf);

                    const response = await fetch(url, {
                        method: 'GET',
                        headers: {
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                        },
                    });

                    const payload = await response.json();

                    if (!response.ok || !payload.success) {
                        clearAutofill();
                        setStatus(payload.message || 'Erro ao validar CPF.', 'danger');
                        invalidateValidationState();
                        return;
                    }

                    const cidadao = payload.data?.cidadao ?? {};

                    if (fullNameInput) fullNameInput.value = cidadao.nome || '';
                    if (birthDateInput) birthDateInput.value = cidadao.data_nascimento || '';

                    const showFallback = !!payload.requires_manual_fields;
                    setFallbackVisibility(showFallback);

                    govValidated = true;
                    setSubmitAvailability();

                    if (showFallback) {
                        setStatus('CPF validado, mas faltam dados. Preencha os campos.', 'warning');
                        fullNameInput?.focus();
                        return;
                    }

                    setStatus('CPF validado com sucesso.', 'success');
                    careTypeInput?.focus();

                } catch (e) {
                    clearAutofill();
                    setStatus('Erro de comunicacao com Gov.Assai.', 'danger');
                    invalidateValidationState();
                } finally {
                    lookupInFlight = false;
                    setSubmitAvailability();
                }
            };

            if (cpfInput) {
                cpfInput.addEventListener('input', function () {
                    const cpf = onlyDigits(cpfInput.value);

                    if (cpf.length < 11) {
                        lastLookupCpf = '';
                        clearAutofill();
                        invalidateValidationState();
                        setStatus('Digite o CPF completo.', 'neutral');
                    }

                    clearTimeout(debounceTimer);

                    debounceTimer = setTimeout(() => {
                        if (onlyDigits(cpfInput.value).length === 11) {
                            performLookup();
                        }
                    }, 500);
                });

                cpfInput.addEventListener('keydown', function (e) {
                    if (e.key === 'Enter') {
                        e.preventDefault();
                        performLookup();
                    }
                });

                cpfInput.focus();
            }

            form?.addEventListener('submit', function (e) {
                if (!govValidated || lookupInFlight) {
                    e.preventDefault();
                    setStatus('Aguarde validacao do CPF.', 'warning');
                }
            });

            setSubmitAvailability();


            // ── BUSCA (CORRIGIDA) ────────────────────────────────────────────
            const searchInput = document.getElementById('search-input');
            const searchButton = document.getElementById('search-button');
            const statusFilter = document.getElementById('status-filter');

            const performSearch = () => {
                const url = new URL(window.location.href);

                const searchVal = searchInput?.value.trim();
                const statusVal = statusFilter?.value;

                // nome
                if (searchVal) {
                    url.searchParams.set('search', searchVal);
                } else {
                    url.searchParams.delete('search');
                }

                // status
                if (statusVal) {
                    url.searchParams.set('status', statusVal);
                } else {
                    url.searchParams.delete('status');
                }

                sessionStorage.setItem('scrollY', window.scrollY);
                window.location.href = url.toString();
            };

            // botão buscar
            searchButton?.addEventListener('click', performSearch);

            // ENTER no nome
            searchInput?.addEventListener('keydown', function (e) {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    performSearch();
                }
            });

            // mudança no select (opcional automático)
            statusFilter?.addEventListener('change', performSearch);


            // ── RESTORE SCROLL ───────────────────────────────────────────────
            const savedScroll = sessionStorage.getItem('scrollY');

            if (savedScroll !== null) {
                window.scrollTo(0, parseInt(savedScroll));
                sessionStorage.removeItem('scrollY');
            }
        });

        // ── CHAMADA DE PACIENTE ────────────────────────────────────────────
        let selectedAttendanceId = null;

        const modal = document.getElementById('call-modal');
        const patientNameEl = document.getElementById('call-patient-name');

        // abrir modal
        document.querySelectorAll('.call-btn').forEach(btn => {
            btn.addEventListener('click', () => {
                selectedAttendanceId = btn.dataset.id;

                if (patientNameEl) {
                    patientNameEl.textContent = btn.dataset.name;
                }

                modal.classList.remove('hidden');
            });
        });

        // cancelar
        document.getElementById('cancel-call')?.addEventListener('click', () => {
            modal.classList.add('hidden');
        });

        // clique fora fecha
        modal?.addEventListener('click', (e) => {
            if (e.target === modal) {
                modal.classList.add('hidden');
            }
        });

        // escolher tipo
        document.querySelectorAll('.call-type-btn').forEach(btn => {
            btn.addEventListener('click', async () => {

                const type = btn.dataset.type;

                const room = document.getElementById('call-room').value;

                try {
                    const response = await fetch(`/calls/${selectedAttendanceId}`, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document
                                .querySelector('meta[name="csrf-token"]')
                                .content
                        },
                        body: JSON.stringify({
                            type: type,
                            room: room 
                        })
                    });

                    const data = await response.json();

                    if (data.success) {
                        modal.classList.add('hidden');

                        // feedback simples
                        alert('Paciente chamado com sucesso!');

                        // opcional: atualizar tela
                        location.reload();
                    }

                } catch (error) {
                    alert('Erro ao chamar paciente');
                }

            });
        });
    </script>
</x-app-layout>