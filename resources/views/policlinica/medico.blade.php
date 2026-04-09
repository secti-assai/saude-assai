<x-app-layout>
    <x-slot name="header">
        <div class="sa-page-header">
            <h2 class="sa-page-title">Policlínica - MEDICO_POLICLINICA</h2>
            <p class="sa-page-subtitle">Área exclusiva de check-out</p>
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

        @if (!empty($doctorSpecialtyLabel))
            <div class="rounded-lg border border-blue-200 bg-blue-50 p-3 text-sm text-blue-900">
                Fila filtrada para sua especialidade: <strong>{{ $doctorSpecialtyLabel }}</strong>
            </div>
        @endif

        <div class="sa-card">
            <div class="sa-card-header">
                <h3 class="sa-card-title">Pacientes em Atendimento</h3>
                <span id="policlinica-medico-live-updated" class="text-xs text-gray-500">Atualização automática a cada 8 segundos</span>
            </div>
            <div class="overflow-x-auto">
                <table class="sa-table">
                    <thead><tr><th>Especialidade</th><th>Cidadão</th><th>Check-in</th><th>Ação</th></tr></thead>
                    <tbody id="policlinica-medico-live-body">
                        @forelse($appointments as $appointment)
                            <tr>
                                <td>{{ \App\Models\WomenClinicAppointment::specialtyLabel($appointment->specialty) }}</td>
                                <td>{{ $appointment->citizen->full_name ?? '—' }}</td>
                                <td>{{ $appointment->checked_in_at?->format('d/m/Y H:i') ?? '—' }}</td>
                                <td>
                                    <form method="POST" action="{{ route('policlinica.check-out', $appointment) }}">
                                        @csrf
                                        <button type="submit" class="sa-btn-success">Finalizar (Check-out)</button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="text-center text-gray-500 py-6">Nenhum paciente em consulta.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script>
        (() => {
            const tbody = document.getElementById('policlinica-medico-live-body');
            const updatedLabel = document.getElementById('policlinica-medico-live-updated');
            if (!tbody || !updatedLabel) {
                return;
            }

            const endpoint = @json(route('policlinica.medico.data'));
            const csrfToken = @json(csrf_token());

            const escapeHtml = (value) => String(value ?? '')
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#39;');

            const renderRows = (rows) => {
                if (!Array.isArray(rows) || rows.length === 0) {
                    tbody.innerHTML = '<tr><td colspan="4" class="text-center text-gray-500 py-6">Nenhum paciente em consulta.</td></tr>';
                    return;
                }

                tbody.innerHTML = rows.map((row) => `
                    <tr>
                        <td>${escapeHtml(row.specialty_label ?? 'Nao informado')}</td>
                        <td>${escapeHtml(row.citizen_name ?? '—')}</td>
                        <td>${escapeHtml(row.checked_in_at ?? '—')}</td>
                        <td>
                            <form method="POST" action="${escapeHtml(row.check_out_url)}">
                                <input type="hidden" name="_token" value="${escapeHtml(csrfToken)}">
                                <button type="submit" class="sa-btn-success">Finalizar (Check-out)</button>
                            </form>
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
