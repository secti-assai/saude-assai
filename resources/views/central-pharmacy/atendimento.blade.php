<x-app-layout>
    <x-slot name="header">
        <div class="sa-page-header">
            <h2 class="sa-page-title">Farmácia Central - ATENDIMENTO</h2>
            <p class="sa-page-subtitle">Área exclusiva de dispensação</p>
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
                <h3 class="sa-card-title">Fila de Dispensação</h3>
                <span id="pharmacy-atendimento-live-updated" class="text-xs text-gray-500">Atualização automática a cada 8 segundos</span>
            </div>
            <div class="overflow-x-auto">
                <table class="sa-table">
                    <thead><tr><th>Data Receita</th><th>Prescritor</th><th>Cidadão</th><th>Fármaco</th><th>Concentração</th><th>Qtd</th><th>Posologia</th><th>Ações</th></tr></thead>
                    <tbody id="pharmacy-atendimento-live-body">
                        @forelse($requests as $row)
                            <tr>
                                <td>{{ $row->prescription_date?->format('d/m/Y') ?? '—' }}</td>
                                <td>{{ $row->prescriber_name ?? '—' }}</td>
                                <td>{{ $row->citizen->full_name ?? '—' }}</td>
                                <td>{{ $row->medication_name }}</td>
                                <td>{{ $row->concentration ?? '—' }}</td>
                                <td>{{ $row->quantity }}</td>
                                <td>{{ $row->dosage ?? '—' }}</td>
                                <td>
                                    <div class="space-y-2 min-w-[260px]">
                                        <form method="POST" action="{{ route('central-pharmacy.dispense', $row) }}">
                                            @csrf
                                            <button type="submit" class="sa-btn-success w-full">Dispensar</button>
                                        </form>

                                        <details class="rounded-lg border border-red-200 bg-red-50 p-2">
                                            <summary class="cursor-pointer text-xs font-semibold text-red-700">Não Dispensar (Recusa Motivada)</summary>
                                            <form method="POST" action="{{ route('central-pharmacy.refuse', $row) }}" class="mt-2 space-y-2">
                                                @csrf
                                                <input name="refusal_reason" class="sa-input" placeholder="Motivo da recusa" required>
                                                <button type="submit" class="sa-btn-danger w-full">Confirmar Recusa</button>
                                            </form>
                                        </details>

                                        <details class="rounded-lg border border-amber-200 bg-amber-50 p-2">
                                            <summary class="cursor-pointer text-xs font-semibold text-amber-700">Dispensar Equivalente (Intercambialidade)</summary>
                                            <form method="POST" action="{{ route('central-pharmacy.dispense-equivalent', $row) }}" class="mt-2 space-y-2">
                                                @csrf
                                                <input name="equivalent_medication_name" class="sa-input" placeholder="Nome do equivalente" required>
                                                <input name="equivalent_concentration" class="sa-input" placeholder="Concentração do equivalente" required>
                                                <button type="submit" class="sa-btn-warning w-full">Dispensar Equivalente</button>
                                            </form>
                                        </details>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="8" class="text-center text-gray-500 py-6">Nenhuma solicitação pendente.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script>
        (() => {
            const tbody = document.getElementById('pharmacy-atendimento-live-body');
            const updatedLabel = document.getElementById('pharmacy-atendimento-live-updated');
            if (!tbody || !updatedLabel) {
                return;
            }

            const endpoint = @json(route('central-pharmacy.atendimento.data'));
            const csrfToken = @json(csrf_token());

            const escapeHtml = (value) => String(value ?? '')
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#39;');

            const renderRows = (rows) => {
                if (!Array.isArray(rows) || rows.length === 0) {
                    tbody.innerHTML = '<tr><td colspan="8" class="text-center text-gray-500 py-6">Nenhuma solicitação pendente.</td></tr>';
                    return;
                }

                tbody.innerHTML = rows.map((row) => `
                    <tr>
                        <td>${escapeHtml(row.prescription_date ?? '—')}</td>
                        <td>${escapeHtml(row.prescriber_name ?? '—')}</td>
                        <td>${escapeHtml(row.citizen_name ?? '—')}</td>
                        <td>${escapeHtml(row.medication_name ?? '—')}</td>
                        <td>${escapeHtml(row.concentration ?? '—')}</td>
                        <td>${escapeHtml(row.quantity ?? '—')}</td>
                        <td>${escapeHtml(row.dosage ?? '—')}</td>
                        <td>
                            <div class="space-y-2 min-w-[260px]">
                                <form method="POST" action="${escapeHtml(row.dispense_url)}">
                                    <input type="hidden" name="_token" value="${escapeHtml(csrfToken)}">
                                    <button type="submit" class="sa-btn-success w-full">Dispensar</button>
                                </form>

                                <details class="rounded-lg border border-red-200 bg-red-50 p-2">
                                    <summary class="cursor-pointer text-xs font-semibold text-red-700">Não Dispensar (Recusa Motivada)</summary>
                                    <form method="POST" action="${escapeHtml(row.refuse_url)}" class="mt-2 space-y-2">
                                        <input type="hidden" name="_token" value="${escapeHtml(csrfToken)}">
                                        <input name="refusal_reason" class="sa-input" placeholder="Motivo da recusa" required>
                                        <button type="submit" class="sa-btn-danger w-full">Confirmar Recusa</button>
                                    </form>
                                </details>

                                <details class="rounded-lg border border-amber-200 bg-amber-50 p-2">
                                    <summary class="cursor-pointer text-xs font-semibold text-amber-700">Dispensar Equivalente (Intercambialidade)</summary>
                                    <form method="POST" action="${escapeHtml(row.dispense_equivalent_url)}" class="mt-2 space-y-2">
                                        <input type="hidden" name="_token" value="${escapeHtml(csrfToken)}">
                                        <input name="equivalent_medication_name" class="sa-input" placeholder="Nome do equivalente" required>
                                        <input name="equivalent_concentration" class="sa-input" placeholder="Concentração do equivalente" required>
                                        <button type="submit" class="sa-btn-warning w-full">Dispensar Equivalente</button>
                                    </form>
                                </details>
                            </div>
                        </td>
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
                    const response = await fetch(endpoint, {
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
