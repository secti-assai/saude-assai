<x-app-layout>
    <x-slot name="header">
        <div class="sa-page-header">
            <h2 class="sa-page-title">Clínica da Mulher - RECEPCAO_CLINICA</h2>
            <p class="sa-page-subtitle">Área exclusiva de check-in</p>
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
            <div class="sa-card-header">
                <h3 class="sa-card-title">Fila de Check-in</h3>
                <span id="women-recepcao-live-updated" class="text-xs text-gray-500">Atualização automática a cada 8 segundos</span>
            </div>
            <div class="rounded-lg border border-emerald-100 bg-emerald-50/60 p-4">
                <div class="mb-3">
                    <p class="text-sm font-semibold text-emerald-900">Filtros de visualização</p>
                    <p class="text-xs text-emerald-800">Padrão desta tela: atendimentos de hoje, em ordem de horário. Você pode filtrar por período e status quando necessário.</p>
                </div>
                <form method="GET" action="{{ route('women-clinic.recepcao') }}" class="grid grid-cols-1 md:grid-cols-4 gap-3 items-end">
                    <div>
                        <label for="date_start" class="sa-label">Data inicial</label>
                        <input id="date_start" name="date_start" type="date" class="sa-input" value="{{ $filters['date_start'] ?? now()->toDateString() }}">
                    </div>
                    <div>
                        <label for="date_end" class="sa-label">Data final</label>
                        <input id="date_end" name="date_end" type="date" class="sa-input" value="{{ $filters['date_end'] ?? now()->toDateString() }}">
                    </div>
                    <div>
                        <label for="status" class="sa-label">Status</label>
                        <select id="status" name="status" class="sa-input">
                            @foreach(($statusOptions ?? []) as $value => $label)
                                <option value="{{ $value }}" @selected(($filters['status'] ?? 'TODOS') === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="flex items-center justify-end gap-2">
                        <button type="submit" class="sa-btn-primary">Aplicar</button>
                        <a href="{{ route('women-clinic.recepcao') }}" class="bg-gray-300 hover:bg-gray-400 text-gray-800 font-bold py-2 px-4 rounded">Voltar ao padrão</a>
                    </div>
                </form>
            </div>
            <div class="overflow-x-auto">
                <table class="sa-table">
                    <thead><tr><th>Data</th><th>Cidadão</th><th>Status</th><th>Ação</th></tr></thead>
                    <tbody id="women-recepcao-live-body">
                        @forelse($appointments as $appointment)
                            <tr>
                                <td>{{ $appointment->scheduled_for?->format('d/m/Y H:i') }}</td>
                                <td>{{ $appointment->citizen->full_name ?? '—' }}</td>
                                <td>
                                    @php
                                        $statusClass = match ($appointment->status) {
                                            'AGENDADO' => 'bg-blue-100 text-blue-700',
                                            'CHECKIN' => 'bg-amber-100 text-amber-700',
                                            'FINALIZADO' => 'bg-emerald-100 text-emerald-700',
                                            default => 'bg-gray-100 text-gray-700',
                                        };
                                    @endphp
                                    <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-semibold {{ $statusClass }}">{{ $appointment->status }}</span>
                                </td>
                                <td>
                                    @if($appointment->status === 'AGENDADO')
                                        <form method="POST" action="{{ route('women-clinic.check-in', $appointment) }}">
                                            @csrf
                                            <button type="submit" class="sa-btn-primary !py-2 !px-4">Check-in</button>
                                        </form>
                                    @elseif($appointment->status === 'FINALIZADO')
                                        <span class="text-xs text-emerald-700 font-semibold">Atendimento finalizado</span>
                                    @else
                                        <span class="text-xs text-gray-500">Aguardando médico</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="text-center text-gray-500 py-6">Nenhum paciente para check-in.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script>
        (() => {
            const tbody = document.getElementById('women-recepcao-live-body');
            const updatedLabel = document.getElementById('women-recepcao-live-updated');
            if (!tbody || !updatedLabel) {
                return;
            }

            const endpoint = @json(route('women-clinic.recepcao.data'));
            const csrfToken = @json(csrf_token());
            const currentParams = new URLSearchParams(window.location.search);

            const escapeHtml = (value) => String(value ?? '')
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#39;');

            const statusClass = (status) => {
                if (status === 'AGENDADO') return 'bg-blue-100 text-blue-700';
                if (status === 'CHECKIN') return 'bg-amber-100 text-amber-700';
                if (status === 'FINALIZADO') return 'bg-emerald-100 text-emerald-700';
                return 'bg-gray-100 text-gray-700';
            };

            const actionHtml = (row) => {
                if (row.status === 'AGENDADO' && row.check_in_url) {
                    return `<form method="POST" action="${escapeHtml(row.check_in_url)}"><input type="hidden" name="_token" value="${escapeHtml(csrfToken)}"><button type="submit" class="sa-btn-primary !py-2 !px-4">Check-in</button></form>`;
                }

                if (row.status === 'FINALIZADO') {
                    return '<span class="text-xs text-emerald-700 font-semibold">Atendimento finalizado</span>';
                }

                return '<span class="text-xs text-gray-500">Aguardando médico</span>';
            };

            const renderRows = (rows) => {
                if (!Array.isArray(rows) || rows.length === 0) {
                    tbody.innerHTML = '<tr><td colspan="4" class="text-center text-gray-500 py-6">Nenhum paciente para check-in.</td></tr>';
                    return;
                }

                tbody.innerHTML = rows.map((row) => `
                    <tr>
                        <td>${escapeHtml(row.scheduled_for ?? '—')}</td>
                        <td>${escapeHtml(row.citizen_name ?? '—')}</td>
                        <td><span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-semibold ${statusClass(row.status)}">${escapeHtml(row.status ?? '')}</span></td>
                        <td>${actionHtml(row)}</td>
                    </tr>
                `).join('');
            };

            let isRefreshing = false;

            const refreshQueue = async () => {
                if (isRefreshing) {
                    return;
                }

                isRefreshing = true;
                try {
                    const url = currentParams.toString() ? `${endpoint}?${currentParams.toString()}` : endpoint;
                    const response = await fetch(url, {
                        headers: {
                            Accept: 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                        },
                    });

                    if (!response.ok) {
                        return;
                    }

                    const payload = await response.json();
                    renderRows(payload.rows ?? []);
                    updatedLabel.textContent = `Atualizado às ${new Date().toLocaleTimeString('pt-BR')}`;
                } catch (error) {
                    // Keep UI stable even if network temporarily fails.
                } finally {
                    isRefreshing = false;
                }
            };

            window.setInterval(refreshQueue, 8000);
        })();
    </script>
</x-app-layout>
