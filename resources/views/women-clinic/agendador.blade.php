<x-app-layout>
    <x-slot name="header">
        <div class="sa-page-header">
            <h2 class="sa-page-title">Agendador de Clínicas de Especialidades</h2>
            <p class="sa-page-subtitle">Organize e consulte a agenda de atendimentos</p>
        </div>
    </x-slot>

    <!-- Alertas -->
    @if ($errors->any())
        <div class="bg-red-50 border-l-4 border-red-500 p-4 mb-6">
            <ul class="text-sm text-red-700 list-disc list-inside">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    @if (session('status'))
        <div class="sa-alert-success mb-6"><span class="text-sm font-medium">{{ session('status') }}</span></div>
    @endif

    <div class="grid grid-cols-1 xl:grid-cols-3 gap-6">
        
        <!-- Coluna Esquerda: Agenda (2/3) -->
        <div class="xl:col-span-2 flex flex-col space-y-6">
            <div class="sa-card h-full flex flex-col">
                <div class="sa-card-header bg-gray-50 border-b border-gray-100 flex justify-between items-center">
                    <h3 class="sa-card-title text-lg"><i class="far fa-calendar-alt mr-2 text-blue-600"></i>Grade de Horários</h3>
                </div>
                
                <!-- Filtros da Agenda -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 p-5 border-b border-gray-100 bg-white">
                    <div>
                        <label class="sa-label text-xs">Data da Agenda</label>
                        <input type="date" id="view-date" class="sa-input font-medium" value="{{ $filters['date_start'] ?? date('Y-m-d') }}">
                    </div>
                    <div>
                        <label class="sa-label text-xs">Clínica</label>
                        <select id="view-clinic" class="sa-select font-medium text-gray-700">
                            @foreach(($clinicOptions ?? []) as $clinicValue => $clinicLabel)
                                <option value="{{ $clinicValue }}" @selected(($filters['clinic_type'] ?? '') === $clinicValue)>{{ $clinicLabel }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="sa-label text-xs">Especialidade</label>
                        <select id="view-specialty" class="sa-select font-medium text-gray-700">
                            <option value="">Selecione a clínica...</option>
                        </select>
                    </div>
                </div>

                <!-- Grid PEC -->
                <div class="p-0 bg-gray-50 flex-1 flex flex-col relative" id="agenda-slots-container">
                    <!-- Overlays de aviso -->
                    <div id="agenda-overlay-empty" class="absolute inset-0 bg-white bg-opacity-90 z-10 flex flex-col items-center justify-center text-gray-500">
                        <div class="w-16 h-16 bg-blue-50 text-blue-500 rounded-full flex items-center justify-center text-2xl mb-4 shadow-sm">
                            <i class="fas fa-search"></i>
                        </div>
                        <h4 class="text-lg font-medium text-gray-700 mb-1">Encontre os Horários</h4>
                        <p class="text-sm">Selecione a especialidade acima para ver a agenda.</p>
                    </div>
                    
                    <div class="bg-gray-100 border-b border-gray-200 px-5 py-3 font-semibold text-gray-700 flex justify-between items-center text-sm">
                        <span id="agenda-subtitle">Horários do Dia</span>
                        <span class="text-xs font-normal text-gray-500 bg-white px-2 py-1 rounded border border-gray-200"><i class="fas fa-clock mr-1"></i> <span id="slot-duration-text">--</span></span>
                    </div>
                    <div id="agenda-slots-list" class="divide-y divide-gray-100 overflow-y-auto w-full bg-white flex-1" style="max-height: 600px;">
                        <!-- JS injeta os agendamentos aqui -->
                    </div>
                </div>
            </div>
        </div>

        <!-- Coluna Direita: Fluxo de Atendimento (1/3) -->
        <div class="xl:col-span-1 flex flex-col space-y-6" id="painel-fluxo">
            <div class="sa-card border-t-4 border-blue-500 shadow-md">
                <div class="sa-card-header bg-white border-b border-gray-100">
                    <h3 class="sa-card-title text-lg text-blue-800"><i class="fas fa-user-plus mr-2"></i>Novo Atendimento</h3>
                </div>
                
                <div class="p-5">
                    @if (!is_array($flow) || !isset($flow['cpf']))
                        <!-- Passo1 -->
                        <div class="mb-4">
                            <div class="flex items-center text-sm font-bold text-gray-500 mb-2">
                                <span class="w-6 h-6 rounded-full bg-blue-100 text-blue-600 flex items-center justify-center mr-2">1</span>
                                Identificação do Cidadão
                            </div>
                            <p class="text-xs text-gray-400 mb-4 ml-8">Para reservar um horário na agenda ao lado, primeiro informe quem será atendido.</p>
                        </div>
                        
                        <form method="POST" action="{{ route('clinic-scheduler.schedule.start') }}">
                            @csrf
                            <div class="mb-4">
                                <label class="sa-label font-bold text-gray-700">CPF do Cidadão <span class="text-red-500">*</span></label>
                                <div class="relative">
                                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                        <i class="fas fa-id-card text-gray-400"></i>
                                    </div>
                                    <input name="cpf" id="input-cpf-start" class="sa-input pl-10 text-lg transition-colors border-2 focus:border-blue-500" value="{{ old('cpf') }}" placeholder="000.000.000-00" maxlength="14" required oninput="let v = this.value.replace(/\D/g, ''); if(v.length > 11) v = v.slice(0, 11); v = v.replace(/(\d{3})(\d)/, '$1.$2'); v = v.replace(/(\d{3})(\d)/, '$1.$2'); v = v.replace(/(\d{3})(\d{1,2})$/, '$1-$2'); this.value = v;">
                                </div>
                            </div>
                            <button type="submit" class="sa-btn-primary w-full py-3 text-base shadow-sm border border-blue-600"><i class="fas fa-arrow-right mr-2"></i>Iniciar Agendamento</button>
                        </form>
                    @elseif (empty($flow['identity_verified']))
                        <!-- Passo2 -->
                        <div class="mb-4">
                            <div class="flex items-center text-sm font-bold text-gray-500 mb-2">
                                <span class="w-6 h-6 rounded-full bg-green-100 text-green-600 flex items-center justify-center mr-2"><i class="fas fa-check"></i></span>
                                <span class="w-6 h-6 rounded-full bg-blue-100 text-blue-600 flex items-center justify-center mx-2">2</span>
                                Desafio de Segurança
                            </div>
                        </div>

                        <form method="POST" action="{{ route('clinic-scheduler.schedule.verify-identity') }}">
                            @csrf
                            <div class="bg-gray-50 p-3 rounded-lg border border-gray-200 mb-4">
                                <p class="text-xs text-gray-500 uppercase tracking-wide font-bold mb-1">Paciente Encontrado</p>
                                <p class="text-sm text-gray-800 font-bold truncate"><i class="fas fa-user mr-1 text-gray-400"></i>{{ $flow['citizen_name'] ?? '—' }}</p>
                                <p class="text-xs text-gray-500 mt-1">CPF: {{ $flow['cpf'] }}</p>
                            </div>

                            <div class="mb-4">
                                <label class="sa-label text-blue-800 font-bold text-sm"><i class="fas fa-lock mr-1"></i> {{ $flow['challenge']['prompt'] ?? 'Confirme os dados solicitados:' }}</label>
                                <p class="text-xs text-gray-500 mb-2 leading-tight">Você pode responder com o nome exatamente igual ou tentar colocar a data de nascimento (ex: 12/03/1990).</p>
                                <input name="answer" class="sa-input focus:ring-blue-500 focus:border-blue-500 border-2" autocomplete="off" autofocus required>
                            </div>
                            
                            <div class="flex flex-col space-y-2">
                                <button type="submit" class="sa-btn-primary w-full py-2"><i class="fas fa-shield-alt mr-2"></i>Validar Identidade</button>
                                <button type="submit" formnovalidate formaction="{{ route('clinic-scheduler.schedule.cancel') }}" class="w-full text-center text-sm text-gray-500 hover:text-gray-800 py-2">Cancelar Atendimento</button>
                            </div>
                        </form>
                    @else
                        <!-- Passo3 Final -->
                        <div class="mb-4">
                            <div class="flex items-center text-sm font-bold text-gray-500 mb-2">
                                <span class="w-6 h-6 rounded-full bg-green-100 text-green-600 flex items-center justify-center mr-2"><i class="fas fa-check"></i></span>
                                <span class="w-6 h-6 rounded-full bg-green-100 text-green-600 flex items-center justify-center mr-2"><i class="fas fa-check"></i></span>
                                <span class="w-6 h-6 rounded-full bg-blue-100 text-blue-600 flex items-center justify-center mr-2 shadow-sm">3</span>
                                Concluir Marcação
                            </div>
                        </div>

                        <form method="POST" action="{{ route('clinic-scheduler.schedule') }}">
                            @csrf
                            <div class="bg-blue-50 p-4 rounded-lg border border-blue-100 mb-4 shadow-inner">
                                <p class="text-xs text-blue-500 uppercase tracking-wide font-bold mb-1">Paciente Autenticado</p>
                                <p class="text-base text-blue-900 font-bold leading-tight">{{ $flow['citizen_name'] ?? '—' }}</p>
                                <p class="text-xs text-blue-600 mt-1 mb-3">CPF: {{ $flow['cpf'] }}</p>
                                
                                <label class="sa-label text-xs !mb-0 text-blue-800">Horário Escolhido:</label>
                                <div class="bg-white px-3 py-2 rounded border border-blue-200 flex items-center justify-between">
                                    <div class="font-bold text-blue-700 text-sm" id="final-dateLabel">
                                        <i class="fas fa-hand-pointer mr-2 animate-bounce"></i>Clique em um horário livre na grade
                                    </div>
                                </div>
                                <input type="hidden" name="clinic_type" id="final-clinic">
                                <input type="hidden" name="specialty" id="final-specialty">
                                <input type="hidden" name="scheduled_for" id="scheduled-for-hidden" required>
                                <input type="hidden" id="final-clinicLabel">
                                <input type="hidden" id="final-specialtyLabel">
                            </div>
                            
                            <div class="mb-4">
                                <label class="sa-label text-gray-700 text-xs">Observações do Agendamento (Opcional)</label>
                                <textarea name="notes" class="sa-input resize-none" rows="2" placeholder="Queixa principal, prioridade...">{{ old('notes') }}</textarea>
                            </div>
                            
                            <div class="flex flex-col space-y-2 mt-4">
                                <button type="submit" class="w-full py-3 bg-gray-300 text-gray-500 font-bold rounded cursor-not-allowed transition-colors" id="btn-submit-final" disabled>
                                    <i class="fas fa-calendar-check mr-2"></i>Confirmar Agendamento
                                </button>
                                <button type="submit" formnovalidate formaction="{{ route('clinic-scheduler.schedule.cancel') }}" class="w-full text-center text-sm text-red-500 hover:text-red-700 hover:bg-red-50 py-2 rounded transition-colors">
                                    <i class="fas fa-times mr-1"></i> Descartar e Sair
                                </button>
                            </div>
                        </form>
                    @endif
                </div>
            </div>
            
            <!-- Ajuda de UX rápida -->
            <div class="bg-blue-50 border border-blue-100 rounded-lg p-4 text-sm text-blue-800">
                <p class="font-bold mb-1"><i class="fas fa-info-circle mr-1"></i> Como usar a nova tela:</p>
                <ul class="list-disc list-outside pl-4 space-y-1 text-xs text-blue-700">
                    <li>Use o painel grande para <strong>visualizar todas as vagas</strong> do dia livremente.</li>
                    <li>Sempre inicie o atendimento pelo painel da direita (CPF).</li>
                    <li>No Passo 3, basta <strong>clicar no botão "+" dentro da grade</strong> de horários para finalizar!</li>
                </ul>
            </div>
        </div>
    </div>

    <!-- Lista de todas as Consultas na parte Inferior -->
    <div class="sa-card mt-6">
        <div class="sa-card-header bg-gray-50 border-b border-gray-100 cursor-pointer" onclick="document.getElementById('filtros-consultas').classList.toggle('hidden')">
            <h3 class="sa-card-title text-md text-gray-700 flex justify-between items-center w-full">
                <span><i class="fas fa-list-ul mr-2 text-gray-400"></i>Gestão de Consultas Já Agendadas</span>
                <i class="fas fa-chevron-down text-gray-400 text-sm"></i>
            </h3>
        </div>
        <div class="sa-card-body pb-0 border-b border-gray-100 bg-white" id="filtros-consultas">
            <form method="GET" action="{{ route('clinic-scheduler.index') }}" class="grid grid-cols-1 md:grid-cols-6 gap-3 items-end mb-4">
                <div>
                    <label class="sa-label text-xs">De</label>
                    <input name="date_start" type="date" class="sa-input" value="{{ $filters['date_start'] }}">
                </div>
                <div>
                    <label class="sa-label text-xs">Até</label>
                    <input name="date_end" type="date" class="sa-input" value="{{ $filters['date_end'] }}">
                </div>
                <div>
                    <label class="sa-label text-xs">Clínica</label>
                    <select name="clinic_type" class="sa-select">
                        <option value="">Todas</option>
                        @foreach($clinicFilterOptions ?? [] as $value => $label)
                            <option value="{{ $value }}" @selected($filters['clinic_type'] === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="sa-label text-xs">Especialidade</label>
                    <select name="specialty" class="sa-select">
                        <option value="">Todas</option>
                        @foreach($specialtyFilterOptions ?? [] as $value => $label)
                            <option value="{{ $value }}" @selected($filters['specialty'] === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="sa-label text-xs">Status</label>
                    <select name="status" class="sa-select">
                        <option value="">Todos</option>
                        @foreach($statusOptions ?? [] as $value => $label)
                            <option value="{{ $value }}" @selected($filters['status'] === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="flex items-center justify-end gap-2">
                    <button type="submit" class="sa-btn-primary w-full shadow-sm">Filtrar</button>
                    <a href="{{ route('clinic-scheduler.index') }}" class="bg-white border-2 border-gray-200 hover:bg-gray-100 hover:border-gray-300 text-gray-700 py-2 px-3 rounded text-center transition-colors"><i class="fas fa-eraser"></i></a>
                </div>
            </form>
        </div>
        <div class="overflow-x-auto bg-white">
            <table class="w-full text-left border-collapse text-sm">
                <thead><tr class="bg-gray-50 border-b-2 border-gray-200 text-gray-600 font-bold uppercase text-xs tracking-wider">
                    <th class="p-3">Data/Hora</th>
                    <th class="p-3">Paciente</th>
                    <th class="p-3">Serviço/Espec.</th>
                    <th class="p-3">Status</th>
                </tr></thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($appointments as $appointment)
                        <tr class="hover:bg-gray-50 transition-colors">
                            <td class="p-3 text-gray-800 font-medium whitespace-nowrap"><i class="far fa-calendar-alt text-gray-400 mr-1"></i> {{ $appointment->scheduled_for?->format('d/m/Y') }} <br><span class="text-blue-600 font-bold text-xs"><i class="far fa-clock mr-1"></i>{{ $appointment->scheduled_for?->format('H:i') }}</span></td>
                            <td class="p-3">
                                <div class="font-bold text-gray-700">{{ $appointment->citizen?->full_name ?? '—' }}</div>
                                <div class="text-xs text-gray-400 font-mono">{{ $appointment->citizen?->cpf ? substr($appointment->citizen->cpf,0,3).'.***.***-**' : 'S/ CPF' }}</div>
                            </td>
                            <td class="p-3">
                                <div class="text-gray-800">{{ \App\Models\WomenClinicAppointment::specialtyLabel($appointment->specialty) }}</div>
                                <div class="text-xs text-gray-500">{{ \App\Models\WomenClinicAppointment::clinicLabel($appointment->clinic_type) }}</div>
                            </td>
                            <td class="p-3">
                                @if($appointment->status === 'AGENDADO')
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800 border border-blue-200">Agendado</span>
                                @elseif($appointment->status === 'CANCELADO')
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800 border border-red-200">Cancelado</span>
                                @elseif($appointment->status === 'REALIZADO' || $appointment->status === 'FINALIZADO')
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800 border border-green-200">Realizado</span>
                                @else
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800 border border-gray-200">{{ $appointment->status }}</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="text-center text-gray-500 py-8"><i class="fas fa-inbox text-gray-300 text-3xl mb-2 block"></i>Nenhuma consulta encontrada com os filtros atuais.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Estilos exclusivos da Agenda UX -->
    <style>
        .pec-slot-row { display: flex; min-height: 52px; border-bottom: 1px solid #f3f4f6; }
        .pec-time-col { width: 68px; flex-shrink: 0; padding: 16px 8px; font-size: 13px; color: #4B5563; font-weight: bold; text-align: center; border-right: 1px solid #F3F4F6; background: #FAFAFA; }
        .pec-content-col { flex-grow: 1; padding: 0; margin: 0; }
        .pec-slot-box { 
            width: 100%; height: 100%; min-height: 52px; display: flex; align-items: center; px-4; transition: all 0.2s; 
            border: 1px solid transparent; border-bottom: 0; 
        }
        .pec-slot-free { 
            background-color: #FFFFFF; cursor: pointer; color: #2563EB; font-weight: 600; font-size: 14px; 
            justify-content: flex-start; padding-left: 20px;
        }
        .pec-slot-free:hover { background-color: #EFF6FF; box-shadow: inset 2px 0 0 0 #3B82F6; color: #1D4ED8;}
        .pec-slot-free-selected { background-color: #DBEAFE; box-shadow: inset 4px 0 0 0 #2563EB; color: #1E3A8A; font-weight: bold; }
        .pec-slot-busy { 
            background-color: #F8FAFC; color: #6B7280; font-size: 13px; padding-left: 1rem; justify-content: center;
        }
        .pec-slot-busy-internal {
            background-color: #FFFFFF; color: #374151; font-weight: 500; padding: 0.5rem 1rem;
            border: 1px solid #E5E7EB; border-radius: 6px; border-left: 4px solid #9CA3AF;
            display: flex; justify-content: space-between; align-items: center; width: 98%; margin: 4px auto; box-shadow: 0 1px 2px rgba(0,0,0,0.05);
        }
        .pec-slot-busy-internal:hover { border-left-color: #4B5563; background-color: #F9FAFB; }
        
        /* Animacao no botao de validacao */
        .btn-ready {
            background-color: #2563EB !important;
            color: white !important;
            cursor: pointer !important;
            box-shadow: 0 4px 6px -1px rgba(37, 99, 235, 0.4) !important;
        }
        .btn-ready:hover {
            background-color: #1D4ED8 !important;
        }
        .glow-input {
            animation: inputPulse 1.5s ease-in-out 2;
        }
        @keyframes inputPulse {
            0% { box-shadow: 0 0 0 0px rgba(59, 130, 246, 0.6); border-color: #3b82f6; }
            50% { box-shadow: 0 0 0 4px rgba(59, 130, 246, 0); border-color: #3b82f6; }
            100% { box-shadow: 0 0 0 0px rgba(59, 130, 246, 0); }
        }
    </style>

    <script>
        (function() {
            const clinicSelect = document.getElementById('view-clinic');
            const specialtySelect = document.getElementById('view-specialty');
            const dateInput = document.getElementById('view-date');
            
            const specialtiesByClinic = @json($specialtiesByClinic ?? []);
            const flowLoaded = @json(is_array($flow) && isset($flow['cpf']));
            const flowStep = @json($flow['identity_verified'] ?? null); // true se tiver no passo 3
            const isStep3 = flowLoaded && flowStep === true;
            
            // Prefill flow inputs if we are in Step 3!
            const finalHiddenInput = document.getElementById('scheduled-for-hidden');
            const hiddenClinic = document.getElementById('final-clinic');
            const hiddenSpecialty = document.getElementById('final-specialty');
            const finalDateLabel = document.getElementById('final-dateLabel');
            const submitBtn = document.getElementById('btn-submit-final');

            const initTargetSpecialty = @json($filters['specialty'] ?? '');

            const populateSpecialtyOptions = (clinicType, targetVal = null) => {
                const specialties = specialtiesByClinic[clinicType] || {};
                specialtySelect.innerHTML = '<option value="">Selecione a especialidade...</option>';

                Object.entries(specialties).forEach(([value, label]) => {
                    const option = document.createElement('option');
                    option.value = value;
                    option.textContent = label;
                    if (targetVal === value) option.selected = true;
                    specialtySelect.appendChild(option);
                });
            };

            // Disparadores dinâmicos
            clinicSelect.addEventListener('change', () => {
                populateSpecialtyOptions(clinicSelect.value);
                specialtySelect.focus();
            });
            
            specialtySelect.addEventListener('change', () => {
                if (specialtySelect.value) {
                    loadSlots();
                } else {
                    document.getElementById('agenda-overlay-empty').classList.remove('hidden');
                }
            });
            dateInput.addEventListener('change', () => {
                if (specialtySelect.value) loadSlots();
            });
            
            // Render on load if we have params
            populateSpecialtyOptions(clinicSelect.value, initTargetSpecialty);
            if (initTargetSpecialty && dateInput.value) {
                loadSlots();
            } else if (isStep3) {
                // If we are in step 3 but user hasn't selected a clinic/spec in top form, 
                // we should encourage them to pick one.
                document.getElementById('agenda-overlay-empty').innerHTML = `
                    <div class="w-16 h-16 bg-blue-100 text-blue-600 rounded-full flex items-center justify-center text-3xl mb-4 shadow-sm animate-bounce">
                        <i class="fas fa-hand-pointer"></i>
                    </div>
                    <h4 class="text-lg font-bold text-blue-800 mb-1">Passo Final!</h4>
                    <p class="text-sm font-medium text-gray-600 px-8 text-center">Agora, selecione a <strong>Clínica e a Especialidade</strong> nesta barra acima para ver os horários disponíveis ao cidadão.</p>
                `;
            }

            function loadSlots() {
                const date = dateInput.value;
                const specialty = specialtySelect.value;
                const clinicType = clinicSelect.value;
                const list = document.getElementById('agenda-slots-list');
                
                if (!specialty) return;
                
                document.getElementById('agenda-overlay-empty').classList.add('hidden');
                list.innerHTML = '<div class="p-12 text-center text-blue-500"><i class="fas fa-spinner fa-spin text-3xl mb-3 block"></i> Carregando a agenda do profissional...</div>';
                document.getElementById('slot-duration-text').innerHTML = "Buscando...";
                
                // Update final form hidden elements right away when they explore
                if(hiddenClinic && isStep3) {
                    hiddenClinic.value = clinicType;
                    hiddenSpecialty.value = specialty;
                    finalDateLabel.innerHTML = '<i class="fas fa-hand-pointer mr-2 text-blue-500"></i> Selecione um horário livre';
                    finalDateLabel.classList.remove('bg-green-100', 'text-green-800', 'border-green-200');
                    finalHiddenInput.value = '';
                    
                    if(submitBtn) {
                        submitBtn.disabled = true;
                        submitBtn.className = "w-full py-3 bg-gray-300 text-gray-500 font-bold rounded cursor-not-allowed transition-colors";
                    }
                }

                fetch(`/agendador/slots?date=${date}&specialty=${specialty}&clinic_type=${clinicType}`)
                    .then(r => r.json())
                    .then(slots => {
                        if (slots.error) {
                            list.innerHTML = `<div class="p-8 text-center text-red-600 font-bold"><i class="fas fa-exclamation-triangle block text-3xl mb-2"></i> ${slots.error}</div>`;
                            return;
                        }
                        if (slots.length === 0) {
                            list.innerHTML = '<div class="p-8 text-center text-gray-500"><i class="far fa-calendar-times block text-3xl mb-2 text-gray-300"></i> Nenhum horário configurado para este dia nesta especialidade.</div>';
                            return;
                        }

                        document.getElementById('slot-duration-text').innerHTML = `<strong>Intervalo fixo</strong>`;
                        const parts = date.split('-');
                        document.getElementById('agenda-subtitle').textContent = `Agendamentos para ${parts[2]}/${parts[1]}/${parts[0]} - ${specialtySelect.options[specialtySelect.selectedIndex].text}`;
                        
                        list.innerHTML = '';
                        
                        slots.forEach(slot => {
                            const row = document.createElement('div');
                            row.className = 'pec-slot-row';
                            
                            const timeCol = document.createElement('div');
                            timeCol.className = 'pec-time-col';
                            timeCol.textContent = slot.time;
                            row.appendChild(timeCol);

                            const contentCol = document.createElement('div');
                            contentCol.className = 'pec-content-col';

                            if (!slot.available) {
                                const isCitizenNamed = slot.patient_name && slot.patient_name !== 'Cidadão';
                                if (isCitizenNamed) {
                                    contentCol.innerHTML = `
                                        <div class="pec-slot-busy-internal">
                                            <div class="flex items-center gap-3">
                                                <div class="w-8 h-8 rounded-full bg-gray-100 flex items-center justify-center text-gray-400">
                                                    <i class="fas fa-user"></i>
                                                </div>
                                                <div>
                                                    <div class="font-bold text-gray-800 text-sm leading-none">${slot.patient_name}</div>
                                                    <div class="text-gray-500 text-xs font-medium mt-1"><i class="fas fa-phone mr-1"></i>${slot.patient_phone || 'Telefone não informado'}</div>
                                                </div>
                                            </div>
                                            <div class="flex gap-2">
                                                <span class="bg-gray-100 text-gray-600 text-xs px-2 py-1 rounded-full font-bold">Ocupado</span>
                                            </div>
                                        </div>
                                    `;
                                    const wrap = document.createElement('div');
                                    wrap.className = 'pec-slot-box bg-gray-50 p-0';
                                    wrap.appendChild(contentCol.children[0]);
                                    contentCol.innerHTML = '';
                                    contentCol.appendChild(wrap);
                                } else {
                                    contentCol.innerHTML = `
                                        <div class="pec-slot-box pec-slot-busy">
                                            <div class="flex flex-col justify-center items-center w-full">
                                                <span class="text-blue-500 font-medium text-xs"><i class="far fa-calendar-check mr-1"></i> Reservado a terceiros</span>
                                            </div>
                                        </div>
                                    `;
                                }
                            } else {
                                contentCol.innerHTML = `
                                    <div class="pec-slot-box pec-slot-free">
                                        <div class="flex items-center gap-3 w-full">
                                            <div class="w-6 h-6 rounded bg-green-100 text-green-600 flex items-center justify-center border border-green-200">
                                                <i class="fas fa-plus text-sm"></i>
                                            </div>
                                            <span class="tracking-wide">Livre para agendamento</span>
                                        </div>
                                    </div>
                                `;
                                const box = contentCol.querySelector('.pec-slot-free');
                                box.onclick = () => {
                                    if(isStep3 && hiddenClinic) {
                                        // Pick it
                                        document.querySelectorAll('.pec-slot-free-selected').forEach(e => e.className = 'pec-slot-box pec-slot-free');
                                        box.className = 'pec-slot-box pec-slot-free-selected';
                                        
                                        finalHiddenInput.value = `${date} ${slot.time}:00`;
                                        
                                        finalDateLabel.innerHTML = `
                                            <div class="flex flex-col">
                                                <span class="text-xs text-green-800 uppercase tracking-wide">Selecionado</span>
                                                <span class="text-lg text-green-900"><i class="far fa-calendar-check mr-1"></i> ${parts[2]}/${parts[1]} às ${slot.time}</span>
                                            </div>
                                        `;
                                        finalDateLabel.parentElement.classList.add('bg-green-50', 'border-green-500', 'shadow-sm');
                                        finalDateLabel.parentElement.classList.remove('bg-white', 'border-blue-200');
                                        
                                        submitBtn.disabled = false;
                                        submitBtn.className = "btn-ready w-full py-3 font-bold rounded transition-colors text-base";
                                        
                                        // Auto-scroll para concluir com pequeno atraso para sentirem a seleção
                                        setTimeout(() => {
                                            document.getElementById('painel-fluxo').scrollIntoView({ behavior: 'smooth', block: 'end' });
                                        }, 150);
                                    } else {
                                        // Tell user to start flow gracefully
                                        document.getElementById('painel-fluxo').scrollIntoView({ behavior: 'smooth', block: 'center' });
                                        const cpfInput = document.getElementById('input-cpf-start');
                                        if (cpfInput) {
                                            setTimeout(() => {
                                                cpfInput.focus();
                                                cpfInput.classList.add('glow-input');
                                                setTimeout(() => cpfInput.classList.remove('glow-input'), 3000);
                                            }, 500);
                                        }
                                    }
                                };
                            }
                            row.appendChild(contentCol);
                            list.appendChild(row);
                        });
                        
                        // Footer do grid
                        const footer = document.createElement('div');
                        footer.className = 'p-3 text-center text-xs text-gray-400 border-t border-gray-100 bg-gray-50';
                        footer.textContent = 'Fim dos horários deste dia.';
                        list.appendChild(footer);
                        
                    }).catch(e => {
                        list.innerHTML = '<div class="p-8 text-center text-red-500"><i class="fas fa-plug text-4xl mb-3 text-red-200 block"></i> Erro ao carregar tabela.</div>';
                    });
            }
        })();
    </script>
</x-app-layout>