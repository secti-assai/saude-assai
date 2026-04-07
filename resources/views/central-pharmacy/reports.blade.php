<x-app-layout>
    <x-slot name="header">
        <div class="sa-page-header">
            <h2 class="sa-page-title">Farmácia - Relatórios</h2>
            <p class="sa-page-subtitle">Visão gerencial para acompanhamento do uso do módulo e validação Gov.Assaí</p>
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

        <div class="sa-card">
            <div class="sa-card-header">
                <h3 class="sa-card-title">Filtros do Relatório</h3>
            </div>
            @php
                $selectedStatus = $filters['status'] ?? 'TODOS';
                $selectedValidation = $filters['needs_validation'] ?? 'all';
                $selectedCategory = $filters['dispense_category'] ?? 'ALL';
            @endphp
            <form method="GET" action="{{ route('central-pharmacy.reports') }}" class="grid grid-cols-1 md:grid-cols-6 gap-3 items-end">
                <div>
                    <label class="sa-label">Data inicial</label>
                    <input type="date" name="date_start" class="sa-input" value="{{ $filters['date_start'] ?? now()->subDays(30)->toDateString() }}">
                </div>
                <div>
                    <label class="sa-label">Data final</label>
                    <input type="date" name="date_end" class="sa-input" value="{{ $filters['date_end'] ?? now()->toDateString() }}">
                </div>
                <div>
                    <label class="sa-label">Status</label>
                    <select name="status" class="sa-select">
                        <option value="TODOS" {{ $selectedStatus === 'TODOS' ? 'selected' : '' }}>Todos os status</option>
                        <option value="DISPENSADOS" {{ $selectedStatus === 'DISPENSADOS' ? 'selected' : '' }}>Somente dispensados</option>
                        <option value="DISPENSADO" {{ $selectedStatus === 'DISPENSADO' ? 'selected' : '' }}>Dispensado</option>
                        <option value="DISPENSADO_EQUIVALENTE" {{ $selectedStatus === 'DISPENSADO_EQUIVALENTE' ? 'selected' : '' }}>Dispensado equivalente</option>
                        <option value="NAO_DISPENSADO" {{ $selectedStatus === 'NAO_DISPENSADO' ? 'selected' : '' }}>Não dispensado</option>
                        <option value="RECEPCAO_VALIDADA" {{ $selectedStatus === 'RECEPCAO_VALIDADA' ? 'selected' : '' }}>Recepção validada</option>
                    </select>
                </div>
                <div>
                    <label class="sa-label">Categoria da dispensação</label>
                    <select name="dispense_category" class="sa-select">
                        <option value="ALL" {{ $selectedCategory === 'ALL' ? 'selected' : '' }}>Todas</option>
                        <option value="MEDICACAO" {{ $selectedCategory === 'MEDICACAO' ? 'selected' : '' }}>MEDICAÇÃO</option>
                        <option value="LEITE" {{ $selectedCategory === 'LEITE' ? 'selected' : '' }}>LEITE</option>
                        <option value="SUPLEMENTO" {{ $selectedCategory === 'SUPLEMENTO' ? 'selected' : '' }}>SUPLEMENTO</option>
                    </select>
                </div>
                <div>
                    <label class="sa-label">Nível Gov.Assaí</label>
                    <input type="text" name="gov_level" class="sa-input" maxlength="2" value="{{ $filters['gov_level'] ?? '' }}" placeholder="Ex: 1 ou 2">
                </div>
                <div>
                    <label class="sa-label">Precisa validar nível 2?</label>
                    <select name="needs_validation" class="sa-select">
                        <option value="all" {{ $selectedValidation === 'all' ? 'selected' : '' }}>Todos</option>
                        <option value="yes" {{ $selectedValidation === 'yes' ? 'selected' : '' }}>Sim</option>
                        <option value="no" {{ $selectedValidation === 'no' ? 'selected' : '' }}>Não</option>
                    </select>
                </div>
                <div>
                    <label class="sa-label">Nome do cidadão</label>
                    <input type="text" name="citizen_name" class="sa-input" value="{{ $filters['citizen_name'] ?? '' }}" placeholder="Buscar por nome">
                </div>

                <div class="md:col-span-6 flex justify-end gap-2">
                    <a href="{{ route('central-pharmacy.reports') }}" class="bg-gray-300 hover:bg-gray-400 text-gray-800 font-bold py-2 px-4 rounded">Limpar</a>
                    <button type="submit" class="sa-btn-primary">Aplicar filtros</button>
                </div>
            </form>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-4">
            <div class="sa-card">
                <p class="text-xs uppercase tracking-wide text-gray-500">Eventos no período</p>
                <p class="text-2xl font-bold text-gray-900">{{ $summary['total_events'] }}</p>
            </div>
            <div class="sa-card">
                <p class="text-xs uppercase tracking-wide text-gray-500">Dispensações no período</p>
                <p class="text-2xl font-bold text-emerald-700">{{ $summary['total_dispensed'] }}</p>
            </div>
            <div class="sa-card">
                <p class="text-xs uppercase tracking-wide text-gray-500">Cidadãos pendentes nível 2</p>
                <p class="text-2xl font-bold text-amber-700">{{ $summary['pending_level_two_validation'] }}</p>
            </div>
            <div class="sa-card">
                <p class="text-xs uppercase tracking-wide text-gray-500">Taxa regularizados (nível 2+)</p>
                <p class="text-2xl font-bold text-blue-700">{{ number_format((float) $summary['regularization_rate'], 1, ',', '.') }}%</p>
            </div>
            <div class="sa-card">
                <p class="text-xs uppercase tracking-wide text-gray-500">Dispensados nível 0/1</p>
                <p class="text-2xl font-bold text-red-700">{{ $summary['low_level_dispensed'] }}</p>
            </div>
            <div class="sa-card">
                <p class="text-xs uppercase tracking-wide text-gray-500">Dispensados nível 2+</p>
                <p class="text-2xl font-bold text-emerald-700">{{ $summary['level_two_plus_dispensed'] }}</p>
            </div>
            <div class="sa-card">
                <p class="text-xs uppercase tracking-wide text-gray-500">Cidadãos atendidos (período)</p>
                <p class="text-2xl font-bold text-gray-900">{{ $summary['total_citizens_period'] }}</p>
            </div>
            <div class="sa-card">
                <p class="text-xs uppercase tracking-wide text-gray-500">Atendentes ativos (período)</p>
                <p class="text-2xl font-bold text-gray-900">{{ $summary['active_attendants'] }}</p>
            </div>
        </div>

        <div class="grid grid-cols-1 xl:grid-cols-3 gap-6">
            <div class="sa-card">
                <div class="sa-card-header">
                    <h3 class="sa-card-title">Quebra por status</h3>
                </div>
                <div class="overflow-x-auto">
                    <table class="sa-table">
                        <thead>
                            <tr>
                                <th>Status</th>
                                <th>Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($statusBreakdown as $row)
                                <tr>
                                    <td>{{ $row['status'] }}</td>
                                    <td>{{ $row['total'] }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="2" class="text-center text-gray-500 py-6">Sem dados no período.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="sa-card">
                <div class="sa-card-header">
                    <h3 class="sa-card-title">Quebra por nível Gov.Assaí (dispensados)</h3>
                </div>
                <div class="overflow-x-auto">
                    <table class="sa-table">
                        <thead>
                            <tr>
                                <th>Nível</th>
                                <th>Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($levelBreakdown as $row)
                                <tr>
                                    <td>{{ $row['level'] }}</td>
                                    <td>{{ $row['total'] }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="2" class="text-center text-gray-500 py-6">Sem dispensações no período.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="sa-card">
                <div class="sa-card-header">
                    <h3 class="sa-card-title">Quebra por categoria (dispensados)</h3>
                </div>
                <div class="overflow-x-auto">
                    <table class="sa-table">
                        <thead>
                            <tr>
                                <th>Categoria</th>
                                <th>Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($categoryBreakdown as $row)
                                <tr>
                                    <td>{{ $row['category'] }}</td>
                                    <td>{{ $row['total'] }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="2" class="text-center text-gray-500 py-6">Sem dispensações no período.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="sa-card">
            <div class="sa-card-header">
                <h3 class="sa-card-title">Listagem geral da Farmácia Central</h3>
                <span class="text-xs text-gray-500">Inclui cidadão, nível Gov.Assaí e status de validação nível 2.</span>
            </div>
            <div class="overflow-x-auto">
                <table class="sa-table">
                    <thead>
                        <tr>
                            <th>Data</th>
                            <th>Cidadão</th>
                            <th>Nível Gov</th>
                            <th>Validação nível 2</th>
                            <th>Status</th>
                            <th>Atendente</th>
                            <th>Recepção</th>
                            <th>Categoria</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($rows as $row)
                            @php
                                $lockFlag = (bool) ($row->citizen->pharmacy_lock_flag ?? false);
                                $statusClass = match ($row->status) {
                                    'DISPENSADO' => 'bg-emerald-100 text-emerald-700',
                                    'DISPENSADO_EQUIVALENTE' => 'bg-blue-100 text-blue-700',
                                    'NAO_DISPENSADO' => 'bg-red-100 text-red-700',
                                    'RECEPCAO_VALIDADA' => 'bg-amber-100 text-amber-700',
                                    default => 'bg-gray-100 text-gray-700',
                                };
                                $categoryLabel = match (strtoupper((string) $row->medication_name)) {
                                    'MEDICACAO' => 'MEDICAÇÃO',
                                    'LEITE' => 'LEITE',
                                    'SUPLEMENTO' => 'SUPLEMENTO',
                                    default => 'N/A',
                                };
                            @endphp
                            <tr>
                                <td>{{ ($row->dispensed_at ?? $row->created_at)?->format('d/m/Y H:i') }}</td>
                                <td>{{ $row->citizen->full_name ?? '—' }}</td>
                                <td>{{ $row->gov_assai_level ?? 'N/A' }}</td>
                                <td>
                                    @if($lockFlag)
                                        <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-semibold bg-amber-100 text-amber-700">Pendente nível 2</span>
                                    @else
                                        <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-semibold bg-emerald-100 text-emerald-700">Regularizado</span>
                                    @endif
                                </td>
                                <td>
                                    <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-semibold {{ $statusClass }}">{{ $row->status }}</span>
                                </td>
                                <td>{{ $row->attendant->name ?? '—' }}</td>
                                <td>{{ $row->reception->name ?? '—' }}</td>
                                <td>{{ $categoryLabel }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="8" class="text-center text-gray-500 py-6">Nenhum registro encontrado para os filtros informados.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-4">
                {{ $rows->links() }}
            </div>
        </div>
    </div>
</x-app-layout>
