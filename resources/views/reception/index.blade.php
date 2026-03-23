<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl">M3 - Recepção UBS</h2>
        <p class="text-sm text-gray-600 mt-1">Registro de entrada de pacientes e fila digital</p>
    </x-slot>
    <div class="py-6 max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
        @if (session('status'))
            <div class="p-4 bg-green-50 border border-green-200 rounded-lg text-green-800">
                <strong>✓ Sucesso:</strong> {{ session('status') }}
            </div>
        @endif

        <!-- Formulário de Recepção -->
        <div class="bg-white p-6 rounded-lg shadow-md border border-gray-100">
            <h3 class="font-semibold text-lg mb-4 text-gray-800">Novo Atendimento</h3>
            <form method="POST" action="{{ route('reception.store') }}" class="grid md:grid-cols-4 gap-4">
                @csrf
                <div class="md:col-span-1">
                    <label class="block text-sm font-medium text-gray-700 mb-1">CPF</label>
                    <input name="cpf" placeholder="000.000.000-00" class="w-full px-3 py-2 rounded border border-gray-300 focus:ring-2 focus:ring-blue-500 focus:border-transparent" required>
                </div>
                <div class="md:col-span-1">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Nome Completo</label>
                    <input name="full_name" placeholder="Nome" class="w-full px-3 py-2 rounded border border-gray-300 focus:ring-2 focus:ring-blue-500 focus:border-transparent" required>
                </div>
                <div class="md:col-span-1">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Data Nascimento</label>
                    <input type="date" name="birth_date" class="w-full px-3 py-2 rounded border border-gray-300 focus:ring-2 focus:ring-blue-500 focus:border-transparent" required>
                </div>
                <div class="md:col-span-1">
                    <label class="block text-sm font-medium text-gray-700 mb-1">CNS (opcional)</label>
                    <input name="cns" placeholder="CNS" class="w-full px-3 py-2 rounded border border-gray-300 focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                </div>

                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Unidade de Saúde</label>
                    <select name="health_unit_id" class="w-full px-3 py-2 rounded border border-gray-300 focus:ring-2 focus:ring-blue-500 focus:border-transparent" required>
                        <option value="">-- Selecione --</option>
                        @foreach ($units as $unit)
                            <option value="{{ $unit->id }}">{{ $unit->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Tipo de Atendimento</label>
                    <select name="care_type" class="w-full px-3 py-2 rounded border border-gray-300 focus:ring-2 focus:ring-blue-500 focus:border-transparent" required>
                        <option>Consulta Médica</option>
                        <option>Retorno</option>
                        <option>Enfermagem</option>
                        <option>Urgência</option>
                    </select>
                </div>

                <div class="md:col-span-3">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Motivo Resumido</label>
                    <input name="summary_reason" placeholder="Ex: Prescrição, Hipertensão, Dor nos olhos" class="w-full px-3 py-2 rounded border border-gray-300 focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">&nbsp;</label>
                    <button type="submit" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-semibold rounded px-4 py-2 transition">
                        ➕ Recepcionar Paciente
                    </button>
                </div>
            </form>
        </div>

        <!-- Fila Digital -->
        <div class="bg-white p-6 rounded-lg shadow-md border border-gray-100">
            <h3 class="font-semibold text-lg mb-4 text-gray-800">Fila Digital</h3>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="bg-gray-50 border-b-2 border-gray-200">
                            <th class="px-4 py-3 text-left font-semibold text-gray-700">Senha</th>
                            <th class="px-4 py-3 text-left font-semibold text-gray-700">Paciente</th>
                            <th class="px-4 py-3 text-left font-semibold text-gray-700">Status</th>
                            <th class="px-4 py-3 text-center font-semibold text-gray-700">Residência</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($attendances as $item)
                            <tr class="border-b hover:bg-gray-50 transition">
                                <td class="px-4 py-3">
                                    <span class="inline-block bg-blue-100 text-blue-800 font-bold text-lg px-3 py-1 rounded-full min-w-16 text-center">
                                        {{ $item->queue_password ?? 'N/A' }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 font-medium text-gray-900">{{ $item->citizen->full_name }}</td>
                                <td class="px-4 py-3">
                                    <span class="inline-block px-3 py-1 rounded-full text-sm font-medium
                                        @if($item->status === 'RECEPCAO') bg-blue-100 text-blue-800
                                        @elseif($item->status === 'TRIAGEM_CONCLUIDA') bg-green-100 text-green-800
                                        @elseif($item->status === 'ATENDIMENTO') bg-purple-100 text-purple-800
                                        @else bg-gray-100 text-gray-800
                                        @endif">
                                        {{ str_replace('_', ' ', $item->status) }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-center">
                                    @if($item->residence_status === 'RESIDENTE')
                                        <span class="inline-block px-3 py-1 rounded-full text-sm font-semibold bg-green-100 text-green-800">✓ RESIDENTE</span>
                                    @elseif($item->residence_status === 'NAO_RESIDENTE')
                                        <span class="inline-block px-3 py-1 rounded-full text-sm font-semibold bg-red-100 text-red-800">✗ NÃO RESIDENTE</span>
                                    @else
                                        <span class="inline-block px-3 py-1 rounded-full text-sm font-semibold bg-yellow-100 text-yellow-800">⏳ VERIFICANDO</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-4 py-8 text-center text-gray-500">Nenhum paciente na fila</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-app-layout>
