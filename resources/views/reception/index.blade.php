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
                <svg class="w-5 h-5 text-amber-700 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0z" />
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
            <p class="text-xs text-amber-700 mb-3">
                Regras de recepcao: so permite prosseguir quando o CPF for validado como residente no Gov.Assai.
            </p>
            <form method="POST" action="{{ route('reception.store') }}" class="space-y-4">
                @csrf
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    <div class="lg:col-span-2">
                        <label class="sa-label">CPF *</label>
                        <div class="flex flex-col gap-2 sm:flex-row sm:items-center">
                            <input id="cpf" name="cpf" value="{{ old('cpf') }}" class="sa-input" required placeholder="000.000.000-00">
                            <button
                                type="button"
                                id="btn_lookup_cpf"
                                class="sa-btn-secondary text-sm"
                                data-url-template="{{ route('reception.citizens.lookup', ['cpf' => '__CPF__']) }}"
                            >
                                Consultar CPF
                            </button>
                        </div>
                        <p class="text-xs text-gray-500 mt-1">A recepcao busca automaticamente os dados do cidadao no Gov.Assai.</p>
                        <p id="gov_lookup_status" class="text-xs text-gray-500 mt-1"></p>
                    </div>
                    <div>
                        <label class="sa-label">Cartão SUS</label>
                        <input name="cns" value="{{ old('cns') }}" class="sa-input" placeholder="Somente se Gov.Assai nao retornar">
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
                        <p class="text-xs text-gray-600 mb-3">Preencha abaixo apenas se o Gov.Assai nao retornar nome ou data de nascimento:</p>
                    </div>
                    <div id="fallback-name" class="hidden">
                        <label class="sa-label">Nome Completo (fallback)</label>
                        <input id="full_name" name="full_name" value="{{ old('full_name') }}" class="sa-input" placeholder="Somente se solicitado pelo sistema">
                    </div>
                    <div id="fallback-birth" class="hidden">
                        <label class="sa-label">Data de Nascimento (fallback)</label>
                        <input id="birth_date" name="birth_date" value="{{ old('birth_date') }}" type="date" class="sa-input">
                    </div>
                    <div>
                        <label class="sa-label">Cartão SUS</label>
                        <label class="sa-label">Acidente de Trabalho</label>
                        <select name="work_accident" class="sa-select">
                            <option value="0" @selected(old('work_accident') === '0')>Nao</option>
                            <option value="1" @selected(old('work_accident') === '1')>Sim</option>
                        </select>
                    </div>
                    <div class="lg:col-span-2">
                        <label class="sa-label">Resumo do Motivo</label>
                        <input name="summary_reason" value="{{ old('summary_reason') }}" class="sa-input" placeholder="Queixa principal resumida">
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
                                    <span class="sa-badge {{ $resColors[$a->residence_status] ?? 'sa-badge-gray' }}">{{ $a->residence_status ?? '—' }}</span>
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

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const cpfInput = document.getElementById('cpf');
            const lookupButton = document.getElementById('btn_lookup_cpf');
            const lookupStatus = document.getElementById('gov_lookup_status');
            const fallbackSection = document.getElementById('fallback-section');
            const fallbackName = document.getElementById('fallback-name');
            const fallbackBirth = document.getElementById('fallback-birth');
            const fullNameInput = document.getElementById('full_name');
            const birthDateInput = document.getElementById('birth_date');

            const onlyDigits = (value) => (value || '').replace(/\D+/g, '');

            const setFallbackVisibility = (showFallback) => {
                fallbackSection.classList.toggle('hidden', !showFallback);
                fallbackName.classList.toggle('hidden', !showFallback);
                fallbackBirth.classList.toggle('hidden', !showFallback);
            };

            const hasOldFallbackData = (fullNameInput.value || '').trim() !== '' || birthDateInput.value !== '';
            if (hasOldFallbackData) {
                setFallbackVisibility(true);
            }

            lookupButton.addEventListener('click', async function () {
                const cpf = onlyDigits(cpfInput.value);

                if (cpf.length !== 11) {
                    lookupStatus.textContent = 'Informe um CPF valido com 11 digitos.';
                    lookupStatus.className = 'text-xs text-red-600 mt-1';
                    return;
                }

                lookupStatus.textContent = 'Consultando Gov.Assai...';
                lookupStatus.className = 'text-xs text-blue-600 mt-1';

                try {
                    const url = lookupButton.dataset.urlTemplate.replace('__CPF__', cpf);
                    const response = await fetch(url, {
                        method: 'GET',
                        headers: {
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                        },
                    });

                    const payload = await response.json();

                    if (!response.ok || !payload.success) {
                        setFallbackVisibility(false);
                        lookupStatus.textContent = payload.message || 'Nao foi possivel validar CPF no Gov.Assai.';
                        lookupStatus.className = 'text-xs text-red-600 mt-1';
                        return;
                    }

                    const cidadao = payload.data?.cidadao ?? {};
                    if (cidadao.nome) {
                        fullNameInput.value = cidadao.nome;
                    }
                    if (cidadao.data_nascimento) {
                        birthDateInput.value = cidadao.data_nascimento;
                    }

                    const showFallback = !!payload.requires_manual_fields;
                    setFallbackVisibility(showFallback);

                    lookupStatus.textContent = showFallback
                        ? 'CPF validado, mas faltam dados obrigatorios. Preencha os campos de fallback.'
                        : 'CPF validado com dados obrigatorios completos no Gov.Assai.';
                    lookupStatus.className = showFallback
                        ? 'text-xs text-amber-700 mt-1'
                        : 'text-xs text-green-700 mt-1';
                } catch (error) {
                    setFallbackVisibility(false);
                    lookupStatus.textContent = 'Erro de comunicacao com Gov.Assai. Tente novamente.';
                    lookupStatus.className = 'text-xs text-red-600 mt-1';
                }
            });
        });
    </script>
</x-app-layout>
