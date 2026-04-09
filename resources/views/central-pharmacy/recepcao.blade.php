<x-app-layout>
    <x-slot name="header">
        <div class="sa-page-header">
            <h2 class="sa-page-title">Farmacia Central - Recepcao</h2>
            <p class="sa-page-subtitle">Validacao de CPF e registro da solicitacao</p>
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
            <div class="sa-card-header"><h3 class="sa-card-title">Nova Solicitacao</h3></div>

            @if (!is_array($flow) || !isset($flow['cpf']))
                <form method="POST" action="{{ route('central-pharmacy.reception.start') }}" class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    @csrf
                    <div class="md:col-span-2">
                        <label class="sa-label">Passo 1 de 3 - CPF do Cidadao *</label>
                        <input name="cpf" class="sa-input" value="{{ old('cpf') }}" placeholder="000.000.000-00" maxlength="14" required oninput="let v = this.value.replace(/\D/g, ''); if(v.length > 11) v = v.slice(0, 11); v = v.replace(/(\d{3})(\d)/, '$1.$2'); v = v.replace(/(\d{3})(\d)/, '$1.$2'); v = v.replace(/(\d{3})(\d{1,2})$/, '$1-$2'); this.value = v;">
                    </div>
                    <div class="md:col-span-3 flex justify-end"><button type="submit" class="sa-btn-primary">Validar CPF</button></div>
                </form>
            @elseif (empty($flow['identity_verified']))
                <form method="POST" action="{{ route('central-pharmacy.reception.verify-identity') }}" class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    @csrf
                    <div class="md:col-span-3">
                        <label class="sa-label">Passo 2 de 3 - Confirmacao de Identidade</label>
                        <p class="text-sm text-gray-700">Cidadao: <strong>{{ $flow['citizen_name'] ?? '—' }}</strong> | CPF: <strong>{{ $flow['cpf'] }}</strong></p>
                        <p class="text-sm text-gray-700 mt-1">{{ $flow['challenge']['prompt'] ?? '' }}</p>
                    </div>
                    <div>
                        <label class="sa-label">Resposta *</label>
                        <input name="answer" class="sa-input" required>
                    </div>
                    <div class="md:col-span-3 flex justify-end space-x-2">
                        <button type="submit" class="sa-btn-primary">Confirmar Identidade</button>
                        <button type="submit" formnovalidate formaction="{{ route('central-pharmacy.reception.cancel') }}" class="bg-gray-300 hover:bg-gray-400 text-gray-800 font-bold py-2 px-4 rounded">Cancelar / Voltar</button>
                    </div>
                </form>
            @else
                <form method="POST" action="{{ route('central-pharmacy.register-reception') }}" class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    @csrf
                    <input type="hidden" name="flow_cpf" value="{{ $flow['cpf'] }}">
                    <div class="md:col-span-3">
                        <label class="sa-label">Passo 3 de 3 - Cadastro da Solicitacao</label>
                        <p class="text-sm text-gray-700">Identidade confirmada para <strong>{{ $flow['citizen_name'] ?? '—' }}</strong> (CPF {{ $flow['cpf'] }})</p>
                    </div>

                    <div>
                        <label class="sa-label">Codigo da Receita</label>
                        <input name="prescription_code" class="sa-input" value="{{ old('prescription_code') }}" maxlength="100">
                    </div>
                    <div>
                        <label class="sa-label">Data da Receita *</label>
                        <input name="prescription_date" type="date" class="sa-input" value="{{ old('prescription_date', now()->toDateString()) }}" required>
                    </div>
                    <div>
                        <label class="sa-label">Profissional Prescritor *</label>
                        <input name="prescriber_name" class="sa-input" value="{{ old('prescriber_name') }}" maxlength="255" required>
                    </div>
                    <div>
                        <label class="sa-label">Medicamento *</label>
                        <input name="medication_name" class="sa-input" value="{{ old('medication_name') }}" maxlength="255" required>
                    </div>
                    <div>
                        <label class="sa-label">Concentracao *</label>
                        <input name="concentration" class="sa-input" value="{{ old('concentration') }}" maxlength="100" required>
                    </div>
                    <div>
                        <label class="sa-label">Quantidade *</label>
                        <input name="quantity" type="number" min="1" class="sa-input" value="{{ old('quantity', 1) }}" required>
                    </div>
                    <div class="md:col-span-2">
                        <label class="sa-label">Posologia *</label>
                        <input name="dosage" class="sa-input" value="{{ old('dosage') }}" maxlength="1000" required>
                    </div>
                    <div class="md:col-span-3">
                        <label class="sa-label">Observacoes</label>
                        <input name="notes" class="sa-input" value="{{ old('notes') }}" maxlength="1000">
                    </div>
                    <div class="md:col-span-3 flex justify-end space-x-2">
                        <button type="submit" class="sa-btn-primary">Registrar Solicitacao</button>
                        <button type="submit" formnovalidate formaction="{{ route('central-pharmacy.reception.cancel') }}" class="bg-gray-300 hover:bg-gray-400 text-gray-800 font-bold py-2 px-4 rounded">Cancelar / Voltar</button>
                    </div>
                </form>
            @endif
        </div>

        <div class="sa-card">
            <div class="sa-card-header"><h3 class="sa-card-title">Solicitacoes da Recepcao</h3></div>
            <form method="GET" action="{{ route('central-pharmacy.recepcao') }}" class="grid grid-cols-1 md:grid-cols-3 gap-3 items-end mb-4">
                <div>
                    <label class="sa-label">Data inicial</label>
                    <input name="date_start" type="date" class="sa-input" value="{{ $filters['date_start'] ?? now()->toDateString() }}">
                </div>
                <div>
                    <label class="sa-label">Data final</label>
                    <input name="date_end" type="date" class="sa-input" value="{{ $filters['date_end'] ?? now()->toDateString() }}">
                </div>
                <div class="flex items-center justify-end gap-2">
                    <button type="submit" class="sa-btn-primary">Aplicar</button>
                    <a href="{{ route('central-pharmacy.recepcao') }}" class="bg-gray-300 hover:bg-gray-400 text-gray-800 font-bold py-2 px-4 rounded">Voltar ao padrao</a>
                </div>
            </form>
            <div class="overflow-x-auto">
                <table class="sa-table">
                    <thead><tr><th>Data Receita</th><th>Cidadao</th><th>Medicamento</th><th>Status</th></tr></thead>
                    <tbody>
                        @forelse($requests as $requestItem)
                            <tr>
                                <td>{{ $requestItem->prescription_date?->format('d/m/Y') ?? '—' }}</td>
                                <td>{{ $requestItem->citizen->full_name ?? '—' }}</td>
                                <td>{{ $requestItem->medication_name }}</td>
                                <td>{{ $requestItem->status }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="text-center text-gray-500 py-6">Nenhuma solicitacao encontrada.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-app-layout>
