<x-app-layout>
    <x-slot name="header">
        <div class="sa-page-header">
            <h2 class="sa-page-title">Prescrições</h2>
            <p class="sa-page-subtitle">Prescrição de medicamentos para pacientes atendidos</p>
        </div>
    </x-slot>

    <div class="space-y-6">
        @if (session('status'))
            <div class="sa-alert-success sa-fade-in">
                <svg class="w-5 h-5 text-green-600 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                <span class="text-sm font-medium">{{ session('status') }}</span>
            </div>
        @endif

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            {{-- Prescription Form --}}
            <div class="sa-card sa-fade-in lg:col-span-1">
                <div class="sa-card-header">
                    <h3 class="sa-card-title">Nova Prescrição</h3>
                </div>
                <form method="POST" action="{{ route('prescriptions.store') }}" class="space-y-4" id="prescription-form">
                    @csrf
                    <div>
                        <label class="sa-label">Paciente *</label>
                        <input
                            type="text"
                            id="attendance_search"
                            class="sa-input"
                            placeholder="Digite nome, CPF ou senha para buscar"
                            autocomplete="off"
                            data-search-url="{{ route('prescriptions.attendances.search') }}"
                        >
                        <input type="hidden" name="attendance_id" id="attendance_id" required>
                        <div id="attendance_feedback" class="text-xs text-gray-500 mt-1">Comece digitando para localizar o paciente sem abrir lista longa.</div>
                        <div id="attendance_results" class="hidden mt-2 rounded-lg border border-slate-200 bg-white shadow-sm max-h-64 overflow-y-auto"></div>
                        <div id="attendance_selected" class="hidden mt-2 rounded-lg border border-emerald-200 bg-emerald-50 p-3">
                            <p id="attendance_selected_name" class="text-sm font-semibold text-slate-900"></p>
                            <p id="attendance_selected_meta" class="text-xs text-slate-600 mt-1"></p>
                        </div>
                    </div>

                    <div>
                        <label class="sa-label">Tipo de Entrega *</label>
                        <select name="delivery_type" class="sa-select" required>
                            <option value="RETIRADA">Retirada na Farmácia</option>
                            <option value="ENTREGA">Entrega Domiciliar (Remédio em Casa)</option>
                        </select>
                    </div>

                    {{-- Itens da Prescrição (múltiplos) --}}
                    <div>
                        <div class="flex items-center justify-between mb-2">
                            <label class="sa-label mb-0">Medicamentos *</label>
                            <button type="button" onclick="addPrescriptionItem()" class="text-xs text-sa-primary font-semibold hover:underline">+ Adicionar Item</button>
                        </div>
                        <div id="prescription-items" class="space-y-3">
                            {{-- Item 1 (template) --}}
                            <div class="p-3 bg-gray-50 rounded-lg space-y-2 prescription-item" data-index="0">
                                <div class="flex items-center justify-between">
                                    <span class="text-xs font-bold text-gray-400">Item 1</span>
                                    <button type="button" onclick="removePrescriptionItem(this)" class="text-xs text-red-500 hover:underline hidden remove-btn">Remover</button>
                                </div>
                                <div>
                                    <select name="items[0][medication_id]" class="sa-select text-sm">
                                        <option value="">Selecionar medicamento existente...</option>
                                        @foreach($medications as $med)
                                            <option value="{{ $med->id }}">{{ $med->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="rounded border border-dashed border-slate-300 bg-white p-2">
                                    <p class="text-[11px] font-semibold text-slate-500 mb-2">Ou cadastre um novo medicamento rapido</p>
                                    <div class="space-y-2">
                                        <input name="items[0][new_medication_name]" class="sa-input text-sm" placeholder="Nome do novo medicamento">
                                        <div class="grid grid-cols-2 gap-2">
                                            <input name="items[0][new_medication_presentation]" class="sa-input text-sm" placeholder="Apresentacao (ex: comprimido)">
                                            <input name="items[0][new_medication_concentration]" class="sa-input text-sm" placeholder="Concentracao (ex: 500mg)">
                                        </div>
                                    </div>
                                </div>
                                <div class="grid grid-cols-2 gap-2">
                                    <input name="items[0][dosage]" class="sa-input text-sm" placeholder="Dosagem (ex: 500mg)">
                                    <input name="items[0][frequency]" class="sa-input text-sm" placeholder="Frequência (ex: 8/8h)">
                                </div>
                                <div class="grid grid-cols-3 gap-2">
                                    <select name="items[0][administration_route]" class="sa-select text-sm">
                                        <option value="VO">VO</option>
                                        <option value="IV">IV</option>
                                        <option value="IM">IM</option>
                                        <option value="SC">SC</option>
                                        <option value="TOPICA">Tópica</option>
                                        <option value="INALATORIA">Inalatória</option>
                                    </select>
                                    <input name="items[0][duration_days]" type="number" min="1" class="sa-input text-sm" placeholder="Dias" required>
                                    <input name="items[0][quantity]" type="number" min="1" class="sa-input text-sm" placeholder="Qtd" required>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div>
                        <label class="sa-label">Observações</label>
                        <textarea name="notes" rows="3" class="sa-input" placeholder="Instruções adicionais..."></textarea>
                    </div>
                    <button type="submit" class="sa-btn-primary w-full">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
                        Prescrever
                    </button>
                </form>
            </div>

            {{-- Prescriptions Table --}}
            <div class="sa-card sa-fade-in lg:col-span-2">
                <div class="sa-card-header">
                    <h3 class="sa-card-title">Prescrições Recentes</h3>
                    <span class="sa-badge sa-badge-info">{{ $prescriptions->count() }}</span>
                </div>
                <div class="overflow-x-auto">
                    <table class="sa-table">
                        <thead>
                            <tr>
                                <th>Paciente</th>
                                <th>Medicamentos</th>
                                <th>Entrega</th>
                                <th>Status</th>
                                <th>Hora</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($prescriptions as $p)
                                <tr>
                                    <td class="font-medium text-gray-900">{{ $p->citizen->full_name ?? '—' }}</td>
                                    <td>
                                        <div class="space-y-0.5">
                                            @foreach($p->items as $item)
                                                <div class="text-xs">
                                                    <span class="font-medium">{{ $item->medication->name ?? '—' }}</span>
                                                    <span class="text-gray-400">{{ $item->dosage }} · {{ $item->frequency }}</span>
                                                </div>
                                            @endforeach
                                        </div>
                                    </td>
                                    <td>
                                        <span class="sa-badge {{ $p->delivery_type === 'ENTREGA' ? 'sa-badge-info' : 'sa-badge-gray' }}">
                                            {{ $p->delivery_type }}
                                        </span>
                                    </td>
                                    <td>
                                        @php
                                            $statusMap = [
                                                'PENDENTE' => 'sa-badge-warning',
                                                'ASSINADA' => 'sa-badge-primary',
                                                'DISPENSADA' => 'sa-badge-success',
                                                'CANCELADA' => 'sa-badge-danger',
                                            ];
                                        @endphp
                                        <span class="sa-badge {{ $statusMap[$p->status] ?? 'sa-badge-gray' }}">{{ $p->status }}</span>
                                    </td>
                                    <td class="text-gray-500 text-xs">{{ $p->created_at->format('H:i') }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="text-center text-gray-400 py-8">Nenhuma prescrição registrada.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    {{-- JavaScript para adicionar/remover itens --}}
    <script>
        let itemIndex = 1;
        const medicationOptions = `@foreach($medications as $med)<option value="{{ $med->id }}">{{ $med->name }}</option>@endforeach`;

        document.addEventListener('DOMContentLoaded', () => {
            const searchInput = document.getElementById('attendance_search');
            const hiddenAttendanceId = document.getElementById('attendance_id');
            const feedback = document.getElementById('attendance_feedback');
            const results = document.getElementById('attendance_results');
            const selectedCard = document.getElementById('attendance_selected');
            const selectedName = document.getElementById('attendance_selected_name');
            const selectedMeta = document.getElementById('attendance_selected_meta');

            let debounceTimer = null;
            let items = [];
            let activeIndex = 0;

            const setFeedback = (text, type = 'text-gray-500') => {
                feedback.className = `text-xs mt-1 ${type}`;
                feedback.textContent = text;
            };

            const hideResults = () => {
                results.classList.add('hidden');
                results.innerHTML = '';
                items = [];
                activeIndex = 0;
            };

            const selectAttendance = (attendance) => {
                hiddenAttendanceId.value = attendance.id;
                searchInput.value = attendance.citizen_name || '';
                selectedCard.classList.remove('hidden');
                selectedName.textContent = `${attendance.queue_password} — ${attendance.citizen_name || 'Paciente sem nome'}`;
                selectedMeta.textContent = `${attendance.citizen_cpf || 'CPF nao informado'} · ${attendance.care_type || '-'} · ${attendance.status || '-'} · ${attendance.arrived_at || '-'}`;
                setFeedback('Paciente selecionado. Continue com os itens da prescricao.', 'text-green-700');
                hideResults();
            };

            const renderResults = (list) => {
                items = list;
                activeIndex = 0;

                if (!list.length) {
                    hideResults();
                    setFeedback('Nenhum atendimento encontrado para o termo informado.', 'text-amber-700');
                    return;
                }

                results.innerHTML = list.map((item, idx) => `
                    <button type="button" class="w-full text-left px-3 py-2 border-b border-slate-100 hover:bg-slate-50 ${idx === activeIndex ? 'bg-slate-100' : ''}" data-idx="${idx}">
                        <div class="text-sm font-semibold text-slate-900">${item.queue_password} — ${item.citizen_name || 'Paciente sem nome'}</div>
                        <div class="text-xs text-slate-500">${item.citizen_cpf || 'CPF nao informado'} · ${item.care_type || '-'} · ${item.status || '-'}</div>
                    </button>
                `).join('');

                results.classList.remove('hidden');

                results.querySelectorAll('[data-idx]').forEach((button) => {
                    button.addEventListener('click', () => {
                        const idx = Number(button.dataset.idx || 0);
                        if (items[idx]) {
                            selectAttendance(items[idx]);
                        }
                    });
                });
            };

            const fetchAttendances = async (term) => {
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

            const runSearch = async () => {
                const term = (searchInput.value || '').trim();
                hiddenAttendanceId.value = '';
                selectedCard.classList.add('hidden');

                if (term.length < 2) {
                    hideResults();
                    setFeedback('Digite ao menos 2 caracteres para buscar paciente.', 'text-gray-500');
                    return;
                }

                setFeedback('Buscando atendimentos...', 'text-blue-600');

                try {
                    const list = await fetchAttendances(term);
                    renderResults(list);
                    if (list.length) {
                        setFeedback('Selecione um paciente da lista para prescrever.', 'text-slate-600');
                    }
                } catch (e) {
                    hideResults();
                    setFeedback('Falha ao buscar atendimentos. Tente novamente.', 'text-red-600');
                }
            };

            searchInput.addEventListener('input', () => {
                if (debounceTimer) {
                    clearTimeout(debounceTimer);
                }
                debounceTimer = setTimeout(runSearch, 350);
            });

            searchInput.addEventListener('keydown', (event) => {
                if (event.key === 'ArrowDown' && items.length > 0) {
                    event.preventDefault();
                    activeIndex = Math.min(activeIndex + 1, items.length - 1);
                    renderResults(items);
                }

                if (event.key === 'ArrowUp' && items.length > 0) {
                    event.preventDefault();
                    activeIndex = Math.max(activeIndex - 1, 0);
                    renderResults(items);
                }

                if (event.key === 'Enter' && items.length > 0) {
                    event.preventDefault();
                    selectAttendance(items[activeIndex] || items[0]);
                }
            });

            document.addEventListener('click', (event) => {
                if (!results.contains(event.target) && event.target !== searchInput) {
                    hideResults();
                }
            });

            searchInput.focus();
        });

        function addPrescriptionItem() {
            const container = document.getElementById('prescription-items');
            const idx = itemIndex++;
            const div = document.createElement('div');
            div.className = 'p-3 bg-gray-50 rounded-lg space-y-2 prescription-item';
            div.dataset.index = idx;
            div.innerHTML = `
                <div class="flex items-center justify-between">
                    <span class="text-xs font-bold text-gray-400">Item ${idx + 1}</span>
                    <button type="button" onclick="removePrescriptionItem(this)" class="text-xs text-red-500 hover:underline">Remover</button>
                </div>
                <div>
                    <select name="items[${idx}][medication_id]" class="sa-select text-sm">
                        <option value="">Selecionar medicamento existente...</option>
                        ${medicationOptions}
                    </select>
                </div>
                <div class="rounded border border-dashed border-slate-300 bg-white p-2">
                    <p class="text-[11px] font-semibold text-slate-500 mb-2">Ou cadastre um novo medicamento rapido</p>
                    <div class="space-y-2">
                        <input name="items[${idx}][new_medication_name]" class="sa-input text-sm" placeholder="Nome do novo medicamento">
                        <div class="grid grid-cols-2 gap-2">
                            <input name="items[${idx}][new_medication_presentation]" class="sa-input text-sm" placeholder="Apresentacao (ex: comprimido)">
                            <input name="items[${idx}][new_medication_concentration]" class="sa-input text-sm" placeholder="Concentracao (ex: 500mg)">
                        </div>
                    </div>
                </div>
                <div class="grid grid-cols-2 gap-2">
                    <input name="items[${idx}][dosage]" class="sa-input text-sm" placeholder="Dosagem (ex: 500mg)">
                    <input name="items[${idx}][frequency]" class="sa-input text-sm" placeholder="Frequência (ex: 8/8h)">
                </div>
                <div class="grid grid-cols-3 gap-2">
                    <select name="items[${idx}][administration_route]" class="sa-select text-sm">
                        <option value="VO">VO</option>
                        <option value="IV">IV</option>
                        <option value="IM">IM</option>
                        <option value="SC">SC</option>
                        <option value="TOPICA">Tópica</option>
                        <option value="INALATORIA">Inalatória</option>
                    </select>
                    <input name="items[${idx}][duration_days]" type="number" min="1" class="sa-input text-sm" placeholder="Dias" required>
                    <input name="items[${idx}][quantity]" type="number" min="1" class="sa-input text-sm" placeholder="Qtd" required>
                </div>
            `;
            container.appendChild(div);
            updateRemoveButtons();
        }

        function removePrescriptionItem(btn) {
            const item = btn.closest('.prescription-item');
            item.remove();
            updateRemoveButtons();
        }

        function updateRemoveButtons() {
            const items = document.querySelectorAll('.prescription-item');
            items.forEach((item, i) => {
                const removeBtn = item.querySelector('.remove-btn');
                if (removeBtn) {
                    removeBtn.classList.toggle('hidden', items.length <= 1);
                }
                item.querySelector('.text-gray-400').textContent = `Item ${i + 1}`;
            });
        }
    </script>
</x-app-layout>
