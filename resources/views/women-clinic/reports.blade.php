<x-app-layout>
    <x-slot name="header">
        <div class="sa-page-header">
            <h2 class="sa-page-title">Clínica da Mulher - Relatórios</h2>
            <p class="sa-page-subtitle">Panorama gerencial de atendimentos, feedbacks e tempo médio de espera</p>
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
                $selectedFeedback = $filters['has_feedback'] ?? 'all';
            @endphp

            <form method="GET" action="{{ route('women-clinic.reports') }}" class="grid grid-cols-1 md:grid-cols-5 gap-3 items-end">
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
                        <option value="AGENDADO" {{ $selectedStatus === 'AGENDADO' ? 'selected' : '' }}>Agendado</option>
                        <option value="CHECKIN" {{ $selectedStatus === 'CHECKIN' ? 'selected' : '' }}>Check-in</option>
                        <option value="FINALIZADO" {{ $selectedStatus === 'FINALIZADO' ? 'selected' : '' }}>Finalizado</option>
                        <option value="CANCELADO" {{ $selectedStatus === 'CANCELADO' ? 'selected' : '' }}>Cancelado</option>
                    </select>
                </div>
                <div>
                    <label class="sa-label">Feedback enviado</label>
                    <select name="has_feedback" class="sa-select">
                        <option value="all" {{ $selectedFeedback === 'all' ? 'selected' : '' }}>Todos</option>
                        <option value="yes" {{ $selectedFeedback === 'yes' ? 'selected' : '' }}>Sim</option>
                        <option value="no" {{ $selectedFeedback === 'no' ? 'selected' : '' }}>Não</option>
                    </select>
                </div>
                <div>
                    <label class="sa-label">Nome do cidadão</label>
                    <input type="text" name="citizen_name" class="sa-input" value="{{ $filters['citizen_name'] ?? '' }}" placeholder="Buscar por nome">
                </div>

                <div class="md:col-span-5 flex justify-end gap-2">
                    <a href="{{ route('women-clinic.reports') }}" class="bg-gray-300 hover:bg-gray-400 text-gray-800 font-bold py-2 px-4 rounded">Limpar</a>
                    <button type="submit" class="sa-btn-primary">Aplicar filtros</button>
                </div>
            </form>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-4">
            <div class="sa-card">
                <p class="text-xs uppercase tracking-wide text-gray-500">Atendimentos no período</p>
                <p class="text-2xl font-bold text-gray-900">{{ $summary['total_appointments'] }}</p>
            </div>
            <div class="sa-card">
                <p class="text-xs uppercase tracking-wide text-gray-500">Consultas finalizadas</p>
                <p class="text-2xl font-bold text-emerald-700">{{ $summary['total_finalized'] }}</p>
            </div>
            <div class="sa-card">
                <p class="text-xs uppercase tracking-wide text-gray-500">Consultas canceladas</p>
                <p class="text-2xl font-bold text-red-700">{{ $summary['total_cancelled'] }}</p>
            </div>
            <div class="sa-card">
                <p class="text-xs uppercase tracking-wide text-gray-500">Agendadas e atrasadas</p>
                <p class="text-2xl font-bold text-amber-700">{{ $summary['delayed_scheduled'] }}</p>
            </div>
            <div class="sa-card">
                <p class="text-xs uppercase tracking-wide text-gray-500">Feedbacks recebidos</p>
                <p class="text-2xl font-bold text-blue-700">{{ $summary['total_with_feedback'] }}</p>
            </div>
            <div class="sa-card">
                <p class="text-xs uppercase tracking-wide text-gray-500">Nota média (1 a 5)</p>
                <p class="text-2xl font-bold text-indigo-700">{{ number_format((float) $summary['average_feedback_score'], 2, ',', '.') }}</p>
            </div>
            <div class="sa-card">
                <p class="text-xs uppercase tracking-wide text-gray-500">Cobertura de feedback (finalizadas)</p>
                <p class="text-2xl font-bold text-cyan-700">{{ number_format((float) $summary['feedback_coverage_rate'], 1, ',', '.') }}%</p>
            </div>
            <div class="sa-card">
                <p class="text-xs uppercase tracking-wide text-gray-500">Tempo médio de espera</p>
                <p class="text-2xl font-bold text-gray-900">{{ number_format((float) $summary['average_wait_minutes'], 1, ',', '.') }} min</p>
                <p class="text-xs text-gray-500 mt-1">Da hora agendada até o check-in</p>
            </div>
            <div class="sa-card">
                <p class="text-xs uppercase tracking-wide text-gray-500">Tempo médio de atendimento</p>
                <p class="text-2xl font-bold text-gray-900">{{ number_format((float) $summary['average_service_minutes'], 1, ',', '.') }} min</p>
                <p class="text-xs text-gray-500 mt-1">Do check-in até o check-out</p>
            </div>
            <div class="sa-card">
                <p class="text-xs uppercase tracking-wide text-gray-500">Agendadas</p>
                <p class="text-2xl font-bold text-blue-700">{{ $summary['total_scheduled'] }}</p>
            </div>
            <div class="sa-card">
                <p class="text-xs uppercase tracking-wide text-gray-500">Em check-in</p>
                <p class="text-2xl font-bold text-amber-700">{{ $summary['total_checkin'] }}</p>
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
                    <h3 class="sa-card-title">Distribuição de notas</h3>
                </div>
                <div class="overflow-x-auto">
                    <table class="sa-table">
                        <thead>
                            <tr>
                                <th>Nota</th>
                                <th>Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($feedbackBreakdown as $row)
                                <tr>
                                    <td>{{ $row['score'] }}</td>
                                    <td>{{ $row['total'] }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="2" class="text-center text-gray-500 py-6">Sem feedbacks no período.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="sa-card">
                <div class="sa-card-header">
                    <h3 class="sa-card-title">Panorama diário (últimos 15 dias)</h3>
                </div>
                <div class="overflow-x-auto">
                    <table class="sa-table">
                        <thead>
                            <tr>
                                <th>Dia</th>
                                <th>Total</th>
                                <th>Finalizadas</th>
                                <th>Feedbacks</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($dailyBreakdown as $row)
                                <tr>
                                    <td>{{ $row['day'] === 'N/A' ? 'N/A' : \Illuminate\Support\Carbon::parse($row['day'])->format('d/m/Y') }}</td>
                                    <td>{{ $row['total'] }}</td>
                                    <td>{{ $row['finalized'] }}</td>
                                    <td>{{ $row['feedbacks'] }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="4" class="text-center text-gray-500 py-6">Sem dados no período.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="sa-card">
            <div class="sa-card-header">
                <h3 class="sa-card-title">Listagem geral da Clínica da Mulher</h3>
                <span class="text-xs text-gray-500">Com indicadores de espera, atendimento e feedback</span>
            </div>

            <div class="overflow-x-auto">
                <table class="sa-table">
                    <thead>
                        <tr>
                            <th>Consulta</th>
                            <th>Cidadão</th>
                            <th>Status</th>
                            <th>Check-in</th>
                            <th>Check-out</th>
                            <th>Espera</th>
                            <th>Atendimento</th>
                            <th>Feedback</th>
                            <th>Equipe</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($rows as $row)
                            @php
                                $waitMinutes = null;
                                if ($row->scheduled_for && $row->checked_in_at) {
                                    $waitMinutes = max(0, $row->scheduled_for->diffInMinutes($row->checked_in_at, false));
                                }

                                $serviceMinutes = null;
                                if ($row->checked_in_at && $row->checked_out_at) {
                                    $serviceMinutes = max(0, $row->checked_in_at->diffInMinutes($row->checked_out_at, false));
                                }

                                $statusClass = match ($row->status) {
                                    'AGENDADO' => 'bg-blue-100 text-blue-700',
                                    'CHECKIN' => 'bg-amber-100 text-amber-700',
                                    'FINALIZADO' => 'bg-emerald-100 text-emerald-700',
                                    'CANCELADO' => 'bg-red-100 text-red-700',
                                    default => 'bg-gray-100 text-gray-700',
                                };
                            @endphp
                            <tr>
                                <td>{{ $row->scheduled_for?->format('d/m/Y H:i') ?? '—' }}</td>
                                <td>{{ $row->citizen->full_name ?? '—' }}</td>
                                <td>
                                    <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-semibold {{ $statusClass }}">{{ $row->status }}</span>
                                </td>
                                <td>{{ $row->checked_in_at?->format('d/m/Y H:i') ?? '—' }}</td>
                                <td>{{ $row->checked_out_at?->format('d/m/Y H:i') ?? '—' }}</td>
                                <td>{{ $waitMinutes === null ? '—' : number_format((float) $waitMinutes, 0, ',', '.').' min' }}</td>
                                <td>{{ $serviceMinutes === null ? '—' : number_format((float) $serviceMinutes, 0, ',', '.').' min' }}</td>
                                <td>
                                    @if($row->feedback_score)
                                        <div class="text-sm font-semibold text-indigo-700">Nota {{ $row->feedback_score }}/5</div>
                                        <div class="text-xs text-gray-500">{{ \Illuminate\Support\Str::limit((string) ($row->feedback_comment ?? ''), 70) }}</div>
                                    @else
                                        <span class="text-xs text-gray-500">Sem feedback</span>
                                    @endif
                                </td>
                                <td class="text-xs text-gray-700">
                                    <div>Agendador: {{ $row->scheduler->name ?? '—' }}</div>
                                    <div>Recepção: {{ $row->reception->name ?? '—' }}</div>
                                    <div>Médico: {{ $row->doctor->name ?? '—' }}</div>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="9" class="text-center text-gray-500 py-6">Nenhum atendimento encontrado para os filtros informados.</td></tr>
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
