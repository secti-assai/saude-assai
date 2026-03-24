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
                        <select name="attendance_id" class="sa-select" required>
                            <option value="">Selecione o paciente...</option>
                            @foreach($attendances as $a)
                                <option value="{{ $a->id }}">{{ $a->queue_password }} — {{ $a->citizen->full_name ?? '—' }}</option>
                            @endforeach
                        </select>
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
                                    <select name="items[0][medication_id]" class="sa-select text-sm" required>
                                        <option value="">Medicamento...</option>
                                        @foreach($medications as $med)
                                            <option value="{{ $med->id }}">{{ $med->name }}</option>
                                        @endforeach
                                    </select>
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
                    <select name="items[${idx}][medication_id]" class="sa-select text-sm" required>
                        <option value="">Medicamento...</option>
                        ${medicationOptions}
                    </select>
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
