<x-app-layout>
    <x-slot name="header">
        <div class="sa-page-header">
            <h2 class="sa-page-title">Prescrições</h2>
            <p class="sa-page-subtitle">Prescrição de medicamentos para pacientes atendidos</p>
        </div>
    </x-slot>

    <div class="space-y-6">
        {{-- Alertas de Erro/Sucesso --}}
        @if ($errors->any())
            <div class="bg-red-50 border-l-4 border-red-400 p-4 mb-4">
                <ul class="list-disc pl-5 text-sm text-red-700">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        @if (session('status'))
            <div class="bg-green-50 border-l-4 border-green-400 p-4 mb-4 flex items-center">
                <svg class="w-5 h-5 text-green-600 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                <span class="text-sm font-medium text-green-800">{{ session('status') }}</span>
            </div>
        @endif

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            {{-- Formulário de Prescrição --}}
            <div class="sa-card lg:col-span-1">
                <div class="sa-card-header">
                    <h3 class="sa-card-title">Nova Prescrição</h3>
                </div>
                <form method="POST" action="{{ route('prescriptions.store') }}" class="p-4 space-y-4" id="prescription-form">
                    @csrf
                    <div>
                        <label class="sa-label">Paciente *</label>
                        <input
                            type="text"
                            id="attendance_search"
                            class="sa-input"
                            placeholder="Digite nome, CPF ou senha"
                            autocomplete="off"
                            data-search-url="{{ route('prescriptions.attendances.search') }}"
                        >
                        <input type="hidden" name="attendance_id" id="attendance_id" required>
                        <div id="attendance_results" class="hidden mt-2 rounded-lg border border-slate-200 bg-white shadow-lg max-h-64 overflow-y-auto z-50"></div>
                    </div>

                    <div>
                        <label class="sa-label">Tipo de Entrega *</label>
                        <select name="delivery_type" class="sa-select" required>
                            <option value="RETIRADA">Retirada na Farmácia</option>
                            <option value="ENTREGA">Entrega Domiciliar (Remédio em Casa)</option>
                        </select>
                    </div>

                    {{-- Itens da Prescrição --}}
                    <div>
                        <div class="flex items-center justify-between mb-2">
                            <label class="sa-label mb-0">Medicamentos *</label>
                            <button type="button" onclick="addPrescriptionItem()" class="text-xs text-blue-600 font-semibold hover:underline">+ Adicionar Item</button>
                        </div>
                        <div id="prescription-items" class="space-y-3">
                            {{-- Item 0 (Template Inicial) --}}
                            <div class="p-3 bg-gray-50 rounded-lg space-y-2 prescription-item" data-index="0">
                                <div class="flex items-center justify-between">
                                    <span class="text-xs font-bold text-gray-400">Item 1</span>
                                    <button type="button" onclick="removePrescriptionItem(this)" class="text-xs text-red-500 hover:underline hidden remove-btn">Remover</button>
                                </div>
                                <div class="relative">
                                    <div class="flex gap-2 items-center">
                                        <input type="text" name="items[0][medication_name]" class="sa-input text-sm medication-autocomplete" placeholder="Buscar medicamento..." autocomplete="off" data-idx="0" required>
                                        <input type="hidden" name="items[0][medication_id]" class="medication-id-hidden" data-idx="0">
                                        <button type="button" class="text-xs text-blue-600 font-semibold whitespace-nowrap" onclick="openMedicationModal(0)">Novo</button>
                                    </div>
                                    <div class="autocomplete-dropdown absolute left-0 right-0 bg-white border rounded shadow-xl z-50 hidden" id="med-dropdown-0"></div>
                                </div>
                                <div class="grid grid-cols-2 gap-2">
                                    <input name="items[0][dosage]" class="sa-input text-sm" placeholder="Dosagem">
                                    <input name="items[0][frequency]" class="sa-input text-sm" placeholder="Frequência">
                                </div>
                                <div class="grid grid-cols-3 gap-2">
                                    <select name="items[0][administration_route]" class="sa-select text-sm">
                                        <option value="VO">VO</option><option value="IV">IV</option><option value="IM">IM</option>
                                        <option value="SC">SC</option><option value="TOPICA">Tópica</option>
                                    </select>
                                    <input name="items[0][duration_days]" type="number" class="sa-input text-sm" placeholder="Dias" required>
                                    <input name="items[0][quantity]" type="number" class="sa-input text-sm" placeholder="Qtd" required>
                                </div>
                            </div>
                        </div>
                    </div>

                    <button type="submit" class="w-full bg-blue-600 text-black py-2 rounded-lg font-bold hover:bg-blue-700 transition">
                        Finalizar Prescrição
                    </button>
                </form>
            </div>

            {{-- Tabela de Prescrições Recentes --}}
            <div class="sa-card lg:col-span-2 overflow-hidden">
                <div class="sa-card-header flex justify-between items-center">
                    <h3 class="sa-card-title">Prescrições Recentes</h3>
                    <span class="bg-blue-100 text-blue-800 text-xs font-medium px-2.5 py-0.5 rounded">{{ $prescriptions->count() }}</span>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead class="bg-gray-50 text-gray-600 uppercase text-xs">
                            <tr>
                                <th class="p-3">Paciente</th>
                                <th class="p-3">Medicamentos</th>
                                <th class="p-3">Status</th>
                                <th class="p-3">Hora</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @forelse($prescriptions as $p)
                                <tr class="hover:bg-gray-50">
                                    <td class="p-3 font-medium">{{ $p->citizen->full_name ?? '—' }}</td>
                                    <td class="p-3">
                                        @foreach($p->items as $item)
                                            <div class="text-xs">{{ $item->medication->name ?? '—' }} ({{ $item->dosage }})</div>
                                        @endforeach
                                    </td>
                                    <td class="p-3"><span class="px-2 py-1 rounded-full text-xs font-bold bg-yellow-100 text-yellow-700">{{ $p->status }}</span></td>
                                    <td class="p-3 text-xs text-gray-500">{{ $p->created_at->format('H:i') }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="4" class="p-8 text-center text-gray-400">Nenhuma prescrição hoje.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    {{-- MODAL PARA NOVO MEDICAMENTO --}}
    <div id="medication-modal" class="fixed inset-0 bg-black bg-opacity-50 z-[100] hidden flex items-center justify-center p-4">
        <div class="bg-white rounded-xl shadow-2xl max-w-md w-full overflow-hidden">
            <div class="p-4 border-b flex justify-between items-center">
                <h3 class="font-bold text-gray-800">Cadastrar Novo Medicamento</h3>
                <button onclick="closeMedicationModal()" class="text-gray-400 hover:text-gray-600">&times;</button>
            </div>
            <form id="medication-modal-form" class="p-4 space-y-4">
                <input type="hidden" name="_token" value="{{ csrf_token() }}">
                <div id="medication-modal-error" class="hidden p-2 text-xs bg-red-100 text-red-700 rounded"></div>
                <div>
                    <label class="block text-xs font-bold mb-1 text-gray-600">Nome do Medicamento</label>
                    <input type="text" name="name" class="sa-input" required>
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-bold mb-1 text-gray-600">Apresentação</label>
                        <input type="text" name="presentation" class="sa-input" placeholder="Ex: Comprimido">
                    </div>
                    <div>
                        <label class="block text-xs font-bold mb-1 text-gray-600">Concentração</label>
                        <input type="text" name="concentration" class="sa-input" placeholder="Ex: 500mg">
                    </div>
                </div>
                <div class="flex gap-2 justify-end mt-4">
                    <button type="button" onclick="closeMedicationModal()" class="px-4 py-2 text-sm text-gray-600 hover:bg-gray-100 rounded">Cancelar</button>
                    <button type="submit" class="px-4 py-2 text-sm bg-blue-600 text-white rounded font-bold hover:bg-blue-700">Salvar</button>
                </div>
            </form>
        </div>
    </div>

    <script>
    // Lista de medicamentos disponível no servidor (usada para autocomplete) - torna global
    window.MEDICATIONS = @json($medications->map(fn($m) => ['id' => $m->id, 'name' => $m->name]));
    var MEDICATIONS = window.MEDICATIONS;

    document.addEventListener('DOMContentLoaded', function () {
        const form = document.getElementById('prescription-form');
        const searchInput = document.getElementById('attendance_search');
        const hiddenAttendanceId = document.getElementById('attendance_id');
        const results = document.getElementById('attendance_results');

        form.addEventListener('submit', function (e) {
    if (!hiddenAttendanceId.value) {
        e.preventDefault();
        alert('Selecione um paciente antes de salvar.');
        return;
    }

    const items = document.querySelectorAll('.prescription-item');
    let valid = true;

    items.forEach((item, index) => {
        const nameInput = item.querySelector('.medication-autocomplete');
        const idInput = item.querySelector('.medication-id-hidden');

        if (nameInput.value.trim() !== '' && !idInput.value) {
            valid = false;
            nameInput.classList.add('border-red-500'); // Destaca o erro
        } else {
            nameInput.classList.remove('border-red-500');
        }
    });

    if (!valid) {
        e.preventDefault();
        alert('Por favor, selecione o medicamento da lista ou cadastre um novo para gerar um ID válido.');
    }
    });

        // BUSCA DE PACIENTE (AJAX)
        let debounceTimer;
        searchInput.addEventListener('input', () => {
            clearTimeout(debounceTimer);
            debounceTimer = setTimeout(async () => {
                const term = searchInput.value.trim();
                if (term.length < 2) { results.classList.add('hidden'); return; }

                const res = await fetch(`${searchInput.dataset.searchUrl}?q=${encodeURIComponent(term)}`);
                const data = await res.json();
                
                results.innerHTML = (data.data || []).map(item => `
                    <div class="p-3 border-b hover:bg-blue-50 cursor-pointer" onclick="selectPatient('${item.id}', '${item.citizen_name}')">
                        <div class="text-sm font-bold text-gray-800">${item.citizen_name}</div>
                        <div class="text-xs text-gray-500">Senha: ${item.queue_password} | CPF: ${item.citizen_cpf}</div>
                    </div>
                `).join('');
                results.classList.remove('hidden');
            }, 300);
        });

        window.selectPatient = (id, name) => {
            hiddenAttendanceId.value = id;
            searchInput.value = name;
            results.classList.add('hidden');
        };

        // FECHAR RESULTADOS AO CLICAR FORA
        document.addEventListener('click', (e) => {
            if (!results.contains(e.target) && e.target !== searchInput) results.classList.add('hidden');
        });
    });

    // FUNÇÕES DE ITENS DINÂMICOS
    function addPrescriptionItem() {
        const container = document.getElementById('prescription-items');
        const idx = container.querySelectorAll('.prescription-item').length;
        
        const div = document.createElement('div');
        div.className = 'p-3 bg-gray-50 rounded-lg space-y-2 prescription-item';
        div.innerHTML = `
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold text-gray-400">Item ${idx + 1}</span>
                <button type="button" onclick="removePrescriptionItem(this)" class="text-xs text-red-500 hover:underline">Remover</button>
            </div>
            <div class="relative">
                <div class="flex gap-2 items-center">
                    <input type="text" name="items[${idx}][medication_name]" class="sa-input text-sm medication-autocomplete" placeholder="Buscar medicamento..." autocomplete="off" data-idx="${idx}" required>
                    <input type="hidden" name="items[${idx}][medication_id]" class="medication-id-hidden" data-idx="${idx}">
                    <button type="button" class="text-xs text-blue-600 font-semibold whitespace-nowrap" onclick="openMedicationModal(${idx})">Novo</button>
                </div>
                <div class="autocomplete-dropdown absolute left-0 right-0 bg-white border rounded shadow-xl z-50 hidden" id="med-dropdown-${idx}"></div>
            </div>
            <div class="grid grid-cols-2 gap-2">
                <input name="items[${idx}][dosage]" class="sa-input text-sm" placeholder="Dosagem">
                <input name="items[${idx}][frequency]" class="sa-input text-sm" placeholder="Frequência">
            </div>
            <div class="grid grid-cols-3 gap-2">
                <select name="items[${idx}][administration_route]" class="sa-select text-sm">
                    <option value="VO">VO</option><option value="IV">IV</option><option value="TOPICA">Tópica</option>
                </select>
                <input name="items[${idx}][duration_days]" type="number" class="sa-input text-sm" placeholder="Dias" required>
                <input name="items[${idx}][quantity]" type="number" class="sa-input text-sm" placeholder="Qtd" required>
            </div>
        `;
        container.appendChild(div);
    }

    function removePrescriptionItem(btn) {
        btn.closest('.prescription-item').remove();
    }

    // AUTOCOMPLETE DE MEDICAMENTOS (DELEGAÇÃO DE EVENTO)
    let medDebounce;
    document.addEventListener('input', function(e) {
        if (e.target.classList.contains('medication-autocomplete')) {
            const input = e.target;
            const idx = input.dataset.idx;
            const val = input.value.trim();
            const dropdown = document.getElementById('med-dropdown-' + idx);

            clearTimeout(medDebounce);
            if (val.length < 2) { dropdown.classList.add('hidden'); return; }

            medDebounce = setTimeout(() => {
                // Busca local em MEDICATIONS (precarregada pelo servidor) para evitar rota inexistente
                const list = MEDICATIONS.filter(m => m.name.toLowerCase().includes(val.toLowerCase())).slice(0, 20);

                dropdown.innerHTML = list.map(m => {
                    const safeName = (m.name || '').replace(/'/g, "\\'");
                    return `
                        <div class="p-2 cursor-pointer hover:bg-blue-50 text-sm border-b" onclick="selectMedication(${idx}, '${m.id}', '${safeName}')">
                            ${m.name}
                        </div>
                    `;
                }).join('');
                dropdown.classList.remove('hidden');
            }, 300);
        }
    });

    window.selectMedication = (idx, id, name) => {
        document.querySelector(`input[name="items[${idx}][medication_name]"]`).value = name;
        document.querySelector(`input[name="items[${idx}][medication_id]"]`).value = id;
        document.getElementById('med-dropdown-' + idx).classList.add('hidden');
    };

    // FUNÇÕES DO MODAL
    window.openMedicationModal = function(idx) {
        const modal = document.getElementById('medication-modal');
        modal.classList.remove('hidden');
        modal.dataset.activeIdx = idx;
    };

    window.closeMedicationModal = function() {
        document.getElementById('medication-modal').classList.add('hidden');
        document.getElementById('medication-modal-form').reset();
    };

            document.getElementById('medication-modal-form').addEventListener('submit', async function(e) {
        e.preventDefault();
        const idx = document.getElementById('medication-modal').dataset.activeIdx;
        const formData = new FormData(this);
        const data = Object.fromEntries(formData.entries());

        try {
            const res = await fetch("{{ route('prescriptions.medications.store') }}", {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('input[name="_token"]').value,
                    'Accept': 'application/json'
                },
                body: JSON.stringify(data)
            });
                    if (!res.ok) {
                        const text = await res.text();
                        console.error('Erro ao salvar medicamento', res.status, text);
                        alert('Erro ao salvar medicamento: ' + (text || res.status));
                        return;
                    }

                    const json = await res.json();

                    if (json.success || json.id) {
                        const medObj = { id: json.id, name: json.name };
                        // adiciona no início da lista local de medicamentos, evitando duplicatas
                        if (!MEDICATIONS.some(m => String(m.id) === String(medObj.id))) {
                            MEDICATIONS.unshift(medObj);
                        }
                        selectMedication(idx, json.id, json.name);
                        closeMedicationModal();
                    } else {
                        console.error('Resposta inesperada ao salvar medicamento', json);
                        alert('Erro ao salvar medicamento. Verifique o console para detalhes.');
                    }
        } catch (err) {
            console.error(err);
            alert('Falha na comunicação com o servidor.');
        }
    });
    </script>
</x-app-layout>