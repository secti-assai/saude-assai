<x-app-layout>
    <x-slot name="header">
        <div class="sa-page-header">
            <h2 class="sa-page-title">Agendador de Clínicas de Especialidades</h2>
            <p class="sa-page-subtitle">Agenda central para Clínica da Mulher e Policlínica</p>
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
            <div class="sa-card-header"><h3 class="sa-card-title">Novo Agendamento</h3></div>
            @if (!is_array($flow) || !isset($flow['cpf']))
                <form method="POST" action="{{ route('clinic-scheduler.schedule.start') }}" class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    @csrf
                    <div class="md:col-span-2">
                        <label class="sa-label">Passo 1 de 3 - CPF do Cidadão *</label>
                        <input name="cpf" class="sa-input" value="{{ old('cpf') }}" placeholder="000.000.000-00" maxlength="14" required oninput="let v = this.value.replace(/\D/g, ''); if(v.length > 11) v = v.slice(0, 11); v = v.replace(/(\d{3})(\d)/, '$1.$2'); v = v.replace(/(\d{3})(\d)/, '$1.$2'); v = v.replace(/(\d{3})(\d{1,2})$/, '$1-$2'); this.value = v;">
                    </div>
                    <div class="md:col-span-3 flex justify-end"><button type="submit" class="sa-btn-primary">Validar CPF</button></div>
                </form>
            @elseif (empty($flow['identity_verified']))
                <form method="POST" action="{{ route('clinic-scheduler.schedule.verify-identity') }}" class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    @csrf
                    <div class="md:col-span-3">
                        <label class="sa-label">Passo 2 de 3 - Confirmacao de Identidade</label>
                        <p class="text-sm text-gray-700">Cidadão: <strong>{{ $flow['citizen_name'] ?? '—' }}</strong> | CPF: <strong>{{ $flow['cpf'] }}</strong></p>
                        <p class="text-sm text-gray-700 mt-1">{{ $flow['challenge']['prompt'] ?? '' }}</p>
                        <p class="text-xs text-gray-500">Voce pode responder com o dado solicitado ou com a data completa (ex: 12/03/2006).</p>
                    </div>
                    <div>
                        <label class="sa-label">Resposta *</label>
                        <input name="answer" class="sa-input" required>
                    </div>
                    <div class="md:col-span-3 flex justify-end space-x-2">
                        <button type="submit" class="sa-btn-primary">Confirmar Identidade</button>
                        <button type="submit" formnovalidate formaction="{{ route('clinic-scheduler.schedule.cancel') }}" class="bg-gray-300 hover:bg-gray-400 text-gray-800 font-bold py-2 px-4 rounded">Cancelar / Voltar</button>
                    </div>
                </form>
            @else
                <form method="POST" action="{{ route('clinic-scheduler.schedule') }}" class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    @csrf
                    @php
                        $selectedClinicForForm = old('clinic_type', \App\Models\WomenClinicAppointment::CLINIC_WOMEN);
                    @endphp
                    <div class="md:col-span-3">
                        <label class="sa-label">Passo 3 de 3 - Dados do Agendamento</label>
                        <p class="text-sm text-gray-700">Identidade confirmada para <strong>{{ $flow['citizen_name'] ?? '—' }}</strong> (CPF {{ $flow['cpf'] }})</p>
                    </div>
                    <div>
                        <label class="sa-label">Data e Hora *</label>
                        <input name="scheduled_for" type="datetime-local" class="sa-input" value="{{ old('scheduled_for') }}" required>
                    </div>
                    <div>
                        <label class="sa-label">Clínica *</label>
                        <select name="clinic_type" id="scheduler-clinic-type" class="sa-select" required>
                            @foreach(($clinicOptions ?? []) as $clinicValue => $clinicLabel)
                                <option value="{{ $clinicValue }}" @selected($selectedClinicForForm === $clinicValue)>{{ $clinicLabel }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="sa-label">Especialidade *</label>
                        <select name="specialty" id="scheduler-specialty" class="sa-select" required>
                            <option value="">Selecione</option>
                            @foreach(($clinicSpecialtyOptions ?? []) as $specialtyValue => $specialtyLabel)
                                <option value="{{ $specialtyValue }}" @selected(old('specialty') === $specialtyValue)>{{ $specialtyLabel }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="sa-label">Observações</label>
                        <input name="notes" class="sa-input" value="{{ old('notes') }}">
                    </div>
                    <div class="md:col-span-3 flex justify-end space-x-2">
                        <button type="submit" class="sa-btn-primary">Agendar</button>
                        <button type="submit" formnovalidate formaction="{{ route('clinic-scheduler.schedule.cancel') }}" class="bg-gray-300 hover:bg-gray-400 text-gray-800 font-bold py-2 px-4 rounded">Cancelar / Voltar</button>
                    </div>
                </form>
            @endif
        </div>

        <div class="sa-card">
            <div class="sa-card-header"><h3 class="sa-card-title">Agendamentos</h3></div>
            <div class="rounded-lg border border-emerald-100 bg-emerald-50/60 p-4">
                <div class="mb-3">
                    <p class="text-sm font-semibold text-emerald-900">Filtros de visualização</p>
                    <p class="text-xs text-emerald-800">Padrão desta tela: data de hoje e status Agendado. Você pode filtrar por clínica, especialidade, período e status.</p>
                </div>
                <form method="GET" action="{{ route('clinic-scheduler.index') }}" class="grid grid-cols-1 md:grid-cols-6 gap-3 items-end">
                    <div>
                        <label for="date_start" class="sa-label">Data inicial</label>
                        <input id="date_start" name="date_start" type="date" class="sa-input" value="{{ $filters['date_start'] ?? now()->toDateString() }}">
                    </div>
                    <div>
                        <label for="date_end" class="sa-label">Data final</label>
                        <input id="date_end" name="date_end" type="date" class="sa-input" value="{{ $filters['date_end'] ?? now()->toDateString() }}">
                    </div>
                    <div>
                        <label for="status" class="sa-label">Status</label>
                        <select id="status" name="status" class="sa-input">
                            @foreach(($statusOptions ?? []) as $value => $label)
                                <option value="{{ $value }}" @selected(($filters['status'] ?? 'AGENDADO') === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label for="clinic_type" class="sa-label">Clínica</label>
                        <select id="clinic_type" name="clinic_type" class="sa-input">
                            @foreach(($clinicFilterOptions ?? []) as $value => $label)
                                <option value="{{ $value }}" @selected(($filters['clinic_type'] ?? 'TODOS') === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label for="specialty" class="sa-label">Especialidade</label>
                        <select id="specialty" name="specialty" class="sa-input">
                            @foreach(($specialtyFilterOptions ?? []) as $value => $label)
                                <option value="{{ $value }}" @selected(($filters['specialty'] ?? 'TODOS') === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="flex items-center justify-end gap-2">
                        <button type="submit" class="sa-btn-primary">Aplicar</button>
                        <a href="{{ route('clinic-scheduler.index') }}" class="bg-gray-300 hover:bg-gray-400 text-gray-800 font-bold py-2 px-4 rounded">Voltar ao padrão</a>
                    </div>
                </form>
            </div>
            <div class="overflow-x-auto">
                <table class="sa-table">
                    <thead><tr><th>Data</th><th>Clínica</th><th>Especialidade</th><th>Cidadão</th><th>Status</th><th>Nível Gov.Assaí</th></tr></thead>
                    <tbody>
                        @forelse($appointments as $appointment)
                            <tr>
                                <td>{{ $appointment->scheduled_for?->format('d/m/Y H:i') }}</td>
                                <td>{{ \App\Models\WomenClinicAppointment::clinicLabel($appointment->clinic_type) }}</td>
                                <td>{{ \App\Models\WomenClinicAppointment::specialtyLabel($appointment->specialty) }}</td>
                                <td>{{ $appointment->citizen->full_name ?? '—' }}</td>
                                <td>
                                    @php
                                        $statusClass = match ($appointment->status) {
                                            'AGENDADO' => 'bg-blue-100 text-blue-700',
                                            'CHECKIN' => 'bg-amber-100 text-amber-700',
                                            'FINALIZADO' => 'bg-emerald-100 text-emerald-700',
                                            'CANCELADO' => 'bg-red-100 text-red-700',
                                            default => 'bg-gray-100 text-gray-700',
                                        };
                                    @endphp
                                    <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-semibold {{ $statusClass }}">{{ $appointment->status }}</span>
                                </td>
                                <td>{{ $appointment->gov_assai_level ?? '—' }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="text-center text-gray-500 py-6">Nenhum agendamento.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script>
        (() => {
            const clinicSelect = document.getElementById('scheduler-clinic-type');
            const specialtySelect = document.getElementById('scheduler-specialty');
            if (!clinicSelect || !specialtySelect) {
                return;
            }

            const specialtiesByClinic = @json($specialtiesByClinic ?? []);
            const oldSpecialty = @json(old('specialty'));

            const populateSpecialtyOptions = (clinicType) => {
                const specialties = specialtiesByClinic[clinicType] || {};
                const availableEntries = Object.entries(specialties);

                specialtySelect.innerHTML = '';

                const emptyOption = document.createElement('option');
                emptyOption.value = '';
                emptyOption.textContent = 'Selecione';
                specialtySelect.appendChild(emptyOption);

                availableEntries.forEach(([value, label]) => {
                    const option = document.createElement('option');
                    option.value = value;
                    option.textContent = label;

                    if (oldSpecialty && oldSpecialty === value) {
                        option.selected = true;
                    }

                    specialtySelect.appendChild(option);
                });

                const hasSelectedSpecialty = Array.from(specialtySelect.options).some((option) => option.selected && option.value !== '');
                if (!hasSelectedSpecialty) {
                    specialtySelect.value = '';
                }
            };

            populateSpecialtyOptions(clinicSelect.value);
            clinicSelect.addEventListener('change', () => {
                specialtySelect.dataset.userChanged = '1';
                populateSpecialtyOptions(clinicSelect.value);
            });
        })();
    </script>
</x-app-layout>
