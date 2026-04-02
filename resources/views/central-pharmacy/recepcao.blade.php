<x-app-layout>
    <x-slot name="header">
        <div class="sa-page-header">
            <h2 class="sa-page-title">Farmácia Central - RECEPCAO</h2>
            <p class="sa-page-subtitle">Área exclusiva de validação e cadastro</p>
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
            <div class="sa-card-header"><h3 class="sa-card-title">Cadastro de Solicitação</h3></div>
            @if (!is_array($flow) || !isset($flow['cpf']))
                <form method="POST" action="{{ route('central-pharmacy.reception.start') }}" class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    @csrf
                    <div class="md:col-span-2">
                        <label class="sa-label">Passo 1 de 3 - CPF *</label>
                        <input name="cpf" class="sa-input" value="{{ old('cpf') }}" placeholder="000.000.000-00" maxlength="14" required oninput="let v = this.value.replace(/\D/g, ''); if(v.length > 11) v = v.slice(0, 11); v = v.replace(/(\d{3})(\d)/, '$1.$2'); v = v.replace(/(\d{3})(\d)/, '$1.$2'); v = v.replace(/(\d{3})(\d{1,2})$/, '$1-$2'); this.value = v;">
                    </div>
                    <div class="md:col-span-3 flex justify-end"><button type="submit" class="sa-btn-primary">Validar CPF</button></div>
                </form>
            @elseif (empty($flow['identity_verified']))
                <form method="POST" action="{{ route('central-pharmacy.reception.verify-identity') }}" class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    @csrf
                    <div class="md:col-span-3">
                        <label class="sa-label">Passo 2 de 3 - Confirmacao de Identidade</label>
                        <p class="text-sm text-gray-700">Cidadão: <strong>{{ $flow['citizen_name'] ?? '—' }}</strong> | CPF: <strong>{{ $flow['cpf'] }}</strong></p>
                        <p class="text-sm text-gray-700 mt-1">{{ $flow['challenge']['prompt'] ?? '' }}</p>
                        <p class="text-xs text-gray-500">Voce pode responder com o dado solicitado ou com a data completa (ex: 12/03/2006).</p>
                    </div>
                    <div><label class="sa-label">Resposta *</label><input name="answer" class="sa-input" required></div>
                    <div class="md:col-span-3 flex justify-end space-x-2">
                        <button type="submit" class="sa-btn-primary">Confirmar Identidade</button>
                        <button type="submit" formnovalidate formaction="{{ route('central-pharmacy.reception.cancel') }}" class="bg-gray-300 hover:bg-gray-400 text-gray-800 font-bold py-2 px-4 rounded">Cancelar / Voltar</button>
                    </div>
                </form>
            @else
                <form method="POST" action="{{ route('central-pharmacy.register-reception') }}" class="grid grid-cols-1 md:grid-cols-6 gap-4">
                    @csrf
                    <input type="hidden" name="flow_cpf" value="{{ $flow['cpf'] ?? '' }}">
                    <div class="md:col-span-6">
                        <label class="sa-label">Passo 3 de 3 - Dados da Solicitacao</label>
                        <p class="text-sm text-gray-700">Identidade confirmada para <strong>{{ $flow['citizen_name'] ?? '—' }}</strong> (CPF {{ $flow['cpf'] }})</p>
                    </div>
                    <div>
                        <label class="sa-label">Data da Receita *</label>
                        <input name="prescription_date" type="date" class="sa-input" value="{{ old('prescription_date', now()->toDateString()) }}" required>
                    </div>
                    <div class="md:col-span-2">
                        <label class="sa-label">Nome do Prescritor *</label>
                        <input name="prescriber_name" class="sa-input" value="{{ old('prescriber_name') }}" required>
                    </div>
                    <div class="md:col-span-2">
                        <label class="sa-label">Nome do Fármaco *</label>
                        <input name="medication_name" class="sa-input" value="{{ old('medication_name') }}" required>
                    </div>
                    <div>
                        <label class="sa-label">Concentração (mg/ml) *</label>
                        <input name="concentration" class="sa-input" value="{{ old('concentration') }}" placeholder="Ex: 500 mg" required>
                    </div>
                    <div>
                        <label class="sa-label">Quantidade Solicitada *</label>
                        <input name="quantity" type="number" min="1" class="sa-input" value="{{ old('quantity', 1) }}" required>
                    </div>
                    <div class="md:col-span-3">
                        <label class="sa-label">Posologia *</label>
                        <input name="dosage" class="sa-input" value="{{ old('dosage') }}" placeholder="Ex: 1 comprimido a cada 8 horas" required>
                    </div>
                    <div class="md:col-span-2">
                        <label class="sa-label">Codigo da Receita</label>
                        <input name="prescription_code" class="sa-input" value="{{ old('prescription_code') }}">
                    </div>
                    <div class="md:col-span-6">
                        <label class="sa-label">Observações</label>
                        <input name="notes" class="sa-input" value="{{ old('notes') }}">
                    </div>
                    <div class="md:col-span-6 flex justify-end space-x-2">
                        <button type="submit" class="sa-btn-primary">Registrar</button>
                        <button type="submit" formnovalidate formaction="{{ route('central-pharmacy.reception.cancel') }}" class="bg-gray-300 hover:bg-gray-400 text-gray-800 font-bold py-2 px-4 rounded">Cancelar / Voltar</button>
                    </div>
                </form>
            @endif
        </div>

        <div class="sa-card">
            <div class="sa-card-header"><h3 class="sa-card-title">Solicitações Registradas</h3></div>
            <div class="rounded-lg border border-emerald-100 bg-emerald-50/60 p-4 mb-4">
                <div class="mb-3">
                    <p class="text-sm font-semibold text-emerald-900">Filtros de visualização</p>
                    <p class="text-xs text-emerald-800">Padrão desta tela: solicitações de hoje. Use o período para consultar dias anteriores.</p>
                </div>
                <form method="GET" action="{{ route('central-pharmacy.recepcao') }}" class="grid grid-cols-1 md:grid-cols-4 gap-3 items-end">
                    <div>
                        <label for="date_start" class="sa-label">Data inicial</label>
                        <input id="date_start" name="date_start" type="date" class="sa-input" value="{{ $filters['date_start'] ?? now()->toDateString() }}">
                    </div>
                    <div>
                        <label for="date_end" class="sa-label">Data final</label>
                        <input id="date_end" name="date_end" type="date" class="sa-input" value="{{ $filters['date_end'] ?? now()->toDateString() }}">
                    </div>
                    <div class="md:col-span-2 flex items-center justify-end gap-2">
                        <button type="submit" class="sa-btn-primary">Aplicar</button>
                        <a href="{{ route('central-pharmacy.recepcao') }}" class="bg-gray-300 hover:bg-gray-400 text-gray-800 font-bold py-2 px-4 rounded">Voltar ao padrão</a>
                    </div>
                </form>
            </div>
            <div class="overflow-x-auto">
                <table class="sa-table">
                    <thead><tr><th>Data Receita</th><th>Cidadão</th><th>Fármaco</th><th>Concentração</th><th>Qtd</th><th>Status</th><th>Detalhe</th></tr></thead>
                    <tbody>
                        @forelse($requests as $row)
                            <tr>
                                <td>{{ $row->prescription_date?->format('d/m/Y') ?? '—' }}</td>
                                <td>{{ $row->citizen->full_name ?? '—' }}</td>
                                <td>{{ $row->medication_name }}<div class="text-xs text-gray-500">{{ $row->prescriber_name ?? 'Prescritor não informado' }}</div></td>
                                <td>{{ $row->concentration ?? '—' }}</td>
                                <td>{{ $row->quantity }}</td>
                                <td>
                                    @php
                                        $statusClass = match ($row->status) {
                                            'RECEPCAO_VALIDADA' => 'bg-blue-100 text-blue-700',
                                            'DISPENSADO' => 'bg-emerald-100 text-emerald-700',
                                            'NAO_DISPENSADO' => 'bg-red-100 text-red-700',
                                            'DISPENSADO_EQUIVALENTE' => 'bg-amber-100 text-amber-700',
                                            default => 'bg-gray-100 text-gray-700',
                                        };

                                        $statusLabel = match ($row->status) {
                                            'RECEPCAO_VALIDADA' => 'Recepção validada',
                                            'DISPENSADO' => 'Dispensado',
                                            'NAO_DISPENSADO' => 'Não dispensado',
                                            'DISPENSADO_EQUIVALENTE' => 'Dispensado equivalente',
                                            default => $row->status,
                                        };
                                    @endphp
                                    <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-semibold {{ $statusClass }}">{{ $statusLabel }}</span>
                                </td>
                                <td>
                                    @if($row->status === 'NAO_DISPENSADO')
                                        <span class="text-xs text-red-700">{{ $row->refusal_reason ?? 'Recusa sem motivo informado' }}</span>
                                    @elseif($row->status === 'DISPENSADO_EQUIVALENTE')
                                        <span class="text-xs text-amber-700">{{ $row->equivalent_medication_name ?? 'Equivalente não informado' }} @if($row->equivalent_concentration)({{ $row->equivalent_concentration }})@endif</span>
                                    @else
                                        <span class="text-xs text-gray-500">—</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="7" class="text-center text-gray-500 py-6">Nenhuma solicitação registrada.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-app-layout>
