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
                    <div class="md:col-span-2"><label class="sa-label">Passo 1 de 3 - CPF *</label><input name="cpf" class="sa-input" value="{{ old('cpf') }}" required></div>
                    <div class="md:col-span-3 flex justify-end"><button type="submit" class="sa-btn-primary">Validar CPF</button></div>
                </form>
            @elseif (empty($flow['identity_verified']))
                <form method="POST" action="{{ route('central-pharmacy.reception.verify-identity') }}" class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    @csrf
                    <div class="md:col-span-3">
                        <label class="sa-label">Passo 2 de 3 - Confirmacao de Identidade</label>
                        <p class="text-sm text-gray-700">Cidadão: <strong>{{ $flow['citizen_name'] ?? '—' }}</strong> | CPF: <strong>{{ $flow['cpf'] }}</strong></p>
                        <p class="text-sm text-gray-700 mt-1">{{ $flow['challenge']['prompt'] ?? '' }}</p>
                        <p class="text-xs text-gray-500">Dica de mascara: {{ $flow['challenge']['mask_hint'] ?? '' }}</p>
                        <p class="text-xs text-gray-500">Voce pode responder com o dado solicitado ou com a data completa (ex: 12/03/2006).</p>
                    </div>
                    <div><label class="sa-label">Resposta *</label><input name="answer" class="sa-input" required></div>
                    <div class="md:col-span-3 flex justify-end"><button type="submit" class="sa-btn-primary">Confirmar Identidade</button></div>
                </form>
            @else
                <form method="POST" action="{{ route('central-pharmacy.register-reception') }}" class="grid grid-cols-1 md:grid-cols-5 gap-4">
                    @csrf
                    <div class="md:col-span-5">
                        <label class="sa-label">Passo 3 de 3 - Dados da Solicitacao</label>
                        <p class="text-sm text-gray-700">Identidade confirmada para <strong>{{ $flow['citizen_name'] ?? '—' }}</strong> (CPF {{ $flow['cpf'] }})</p>
                    </div>
                    <div><label class="sa-label">Receita</label><input name="prescription_code" class="sa-input" value="{{ old('prescription_code') }}"></div>
                    <div><label class="sa-label">Medicação *</label><input name="medication_name" class="sa-input" value="{{ old('medication_name') }}" required></div>
                    <div><label class="sa-label">Quantidade *</label><input name="quantity" type="number" min="1" class="sa-input" value="{{ old('quantity', 1) }}" required></div>
                    <div><label class="sa-label">Observações</label><input name="notes" class="sa-input" value="{{ old('notes') }}"></div>
                    <div class="md:col-span-5 flex justify-end"><button type="submit" class="sa-btn-primary">Registrar</button></div>
                </form>
            @endif
        </div>

        <div class="sa-card">
            <div class="sa-card-header"><h3 class="sa-card-title">Solicitações Registradas</h3></div>
            <div class="overflow-x-auto">
                <table class="sa-table">
                    <thead><tr><th>Cidadão</th><th>Medicação</th><th>Qtd</th><th>Status</th></tr></thead>
                    <tbody>
                        @forelse($requests as $row)
                            <tr>
                                <td>{{ $row->citizen->full_name ?? '—' }}</td>
                                <td>{{ $row->medication_name }}</td>
                                <td>{{ $row->quantity }}</td>
                                <td>{{ $row->status }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="text-center text-gray-500 py-6">Nenhuma solicitação registrada.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-app-layout>
