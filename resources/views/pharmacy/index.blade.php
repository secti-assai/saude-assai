<x-app-layout>
    <x-slot name="header">
        <div class="sa-page-header">
            <h2 class="sa-page-title">Farmácia</h2>
            <p class="sa-page-subtitle">Dispensação e Acompanhamento de Medicamentos</p>
        </div>
    </x-slot>

    @php
        $allowedTabs = ['pendentes', 'falhas', 'historico'];
        $initialTab = in_array(request('tab'), $allowedTabs, true) ? request('tab') : 'pendentes';
    @endphp

    <div x-data="{ tab: '{{ $initialTab }}' }" class="space-y-6">

        @if (session('status'))
            <div class="sa-alert-success sa-fade-in">
                <svg class="w-5 h-5 text-green-600 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <span class="text-sm font-medium">{{ session('status') }}</span>
            </div>
        @endif

        <div class="sa-card">
            <form method="GET" action="{{ route('pharmacy.index') }}" class="flex flex-col md:flex-row gap-3 md:items-center">
                <div class="flex-1">
                    <label for="search" class="block text-xs font-bold text-gray-700 mb-1">Buscar (Nome, CPF ou Remédio)</label>
                    <input
                        id="search"
                        type="text"
                        name="search"
                        value="{{ $search }}"
                        placeholder="Digite nome, CPF ou remédio..."
                        class="sa-input w-full"
                    >
                </div>
                <input type="hidden" name="tab" :value="tab">
                <button type="submit" class="sa-btn-primary md:mt-5">Buscar</button>
            </form>
        </div>

        <div class="flex border-b border-gray-200 overflow-x-auto">
            <button @click="tab = 'pendentes'" :class="{ 'border-[var(--gov-primary)] text-[var(--gov-primary)]': tab === 'pendentes', 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300': tab !== 'pendentes' }" class="whitespace-nowrap py-3 px-6 border-b-2 font-medium text-sm transition-colors flex items-center gap-2">
                Fila de Dispensação
                <span class="bg-yellow-100 text-yellow-800 py-0.5 px-2.5 rounded-full text-xs font-bold">{{ $prescriptions->count() }}</span>
            </button>
            <button @click="tab = 'falhas'" :class="{ 'border-[var(--gov-primary)] text-[var(--gov-primary)]': tab === 'falhas', 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300': tab !== 'falhas' }" class="whitespace-nowrap py-3 px-6 border-b-2 font-medium text-sm transition-colors flex items-center gap-2">
                Devoluções / Falhas
                <span class="bg-red-100 text-red-800 py-0.5 px-2.5 rounded-full text-xs font-bold">{{ $failures->count() }}</span>
            </button>
            <button @click="tab = 'historico'" :class="{ 'border-[var(--gov-primary)] text-[var(--gov-primary)]': tab === 'historico', 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300': tab !== 'historico' }" class="whitespace-nowrap py-3 px-6 border-b-2 font-medium text-sm transition-colors flex items-center gap-2">
                Histórico
                <span class="bg-gray-100 text-gray-700 py-0.5 px-2.5 rounded-full text-xs font-bold">{{ $history->total() }}</span>
            </button>
        </div>

        <div x-show="tab === 'pendentes'" class="space-y-6" x-cloak>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                @forelse ($prescriptions as $p)
                    <div class="sa-card sa-fade-in">
                        <div class="flex items-start justify-between mb-3">
                            <div>
                                <h4 class="font-bold text-gray-900">{{ $p->citizen->full_name ?? '—' }}</h4>
                                <p class="text-xs text-gray-500">Senha: {{ $p->attendance->queue_password ?? '—' }}</p>
                            </div>
                            <span class="sa-badge sa-badge-warning">Pendente</span>
                        </div>

                        <div class="space-y-2 mb-4">
                            @foreach ($p->items as $item)
                                <div class="flex items-start gap-2 text-sm">
                                    <svg class="w-4 h-4 text-sa-primary flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M9.75 3.104v5.714a2.25 2.25 0 01-.659 1.591L5 14.5M9.75 3.104c-.251.023-.501.05-.75.082m.75-.082a24.301 24.301 0 014.5 0m0 0v5.714c0 .597.237 1.17.659 1.591L19.8 15.3" />
                                    </svg>
                                    <div>
                                        <p class="font-medium text-gray-800">{{ $item->medication->name ?? 'Medicamento' }}</p>
                                        <p class="text-xs text-gray-500">
                                            {{ $item->dosage ?? 'Dose n/i' }} · {{ $item->frequency ?? 'Frequência n/i' }} · Qtd: {{ $item->quantity }}
                                        </p>
                                    </div>
                                </div>
                            @endforeach
                            @if ($p->notes)
                                <p class="text-xs text-gray-400 italic">{{ $p->notes }}</p>
                            @endif
                        </div>

                        <form method="POST" action="{{ route('pharmacy.dispense', $p) }}" class="space-y-3 mt-4 pt-3 border-t border-gray-100">
                            @csrf

                            <div class="mb-3">
                                <label class="block text-xs font-bold text-gray-700 mb-1">
                                    Atribuir a um Entregador <span class="text-gray-400 font-normal">(Opcional para retirada no balcão)</span>
                                </label>
                                <select name="delivery_user_id" class="sa-select w-full text-sm bg-gray-50 border-gray-200">
                                    <option value="">Sem entregador (Retirada no Balcão)</option>
                                    @foreach($drivers as $driver)
                                        <option value="{{ $driver->id }}">{{ $driver->name }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <button type="submit" class="sa-btn-success w-full">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
                                </svg>
                                Confirmar e Dispensar
                            </button>
                        </form>
                    </div>
                @empty
                    <div class="sa-card col-span-full text-center py-12">
                        <svg class="w-12 h-12 mx-auto text-gray-300 mb-3" fill="none" stroke="currentColor" stroke-width="1" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <p class="text-gray-500 font-medium">Nenhuma receita na fila.</p>
                    </div>
                @endforelse
            </div>
        </div>

        <div x-show="tab === 'falhas'" class="space-y-6" x-cloak style="display: none;">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                @forelse ($failures as $f)
                    <div class="sa-card border-red-100 bg-red-50/40 flex flex-col h-full">
                        <div class="flex-grow">
                            <div class="flex items-start justify-between mb-3">
                                <div>
                                    <h4 class="font-bold text-gray-900">{{ $f->citizen->full_name ?? '—' }}</h4>
                                    <p class="text-xs text-gray-500">Última tentativa: {{ $f->updated_at->format('d/m/Y H:i') }}</p>
                                </div>
                                <span class="sa-badge sa-badge-danger">Falha</span>
                            </div>

                            <div class="space-y-1 mb-3">
                                @foreach ($f->items as $item)
                                    <p class="text-xs text-gray-600 truncate">• {{ $item->quantity }}x {{ $item->medication->name ?? 'Med' }}</p>
                                @endforeach
                            </div>

                            <div class="mt-3 p-3 bg-red-50 border border-red-200 rounded-lg">
                                <p class="text-xs text-red-700 mb-3">
                                    <strong>Motivo da Devolução:</strong> {{ $f->delivery->failure_reason ?? 'Não informado' }}
                                </p>

                                <form method="POST" action="{{ route('pharmacy.reassign', $f->delivery) }}" class="pt-3 border-t border-red-200">
                                    @csrf
                                    <label class="block text-xs font-bold text-gray-700 mb-1">Nova tentativa:</label>
                                    <div class="flex gap-2">
                                        <select name="delivery_user_id" class="sa-select text-xs w-full py-1.5" required>
                                            <option value="">Escolha o entregador...</option>
                                            @foreach($drivers as $driver)
                                                <option value="{{ $driver->id }}">{{ $driver->name }}</option>
                                            @endforeach
                                        </select>
                                        <button type="submit" class="bg-red-600 hover:bg-red-700 text-white font-bold py-1.5 px-3 rounded-lg text-xs transition-colors whitespace-nowrap">
                                            Reenviar
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="sa-card col-span-full text-center py-12 bg-gray-50">
                        <p class="text-gray-500 font-medium">Nenhuma devolução pendente de reatribuição.</p>
                    </div>
                @endforelse
            </div>
        </div>

        <div x-show="tab === 'historico'" class="space-y-6" x-cloak style="display: none;">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                @forelse ($history as $h)
                    <div class="sa-card bg-gray-50 border-gray-100 flex flex-col h-full">
                        <div class="flex-grow">
                            <div class="flex items-start justify-between mb-3">
                                <div>
                                    <h4 class="font-bold text-gray-900">{{ $h->citizen->full_name ?? '—' }}</h4>
                                    <p class="text-xs text-gray-500">Dispensado em: {{ $h->updated_at->format('d/m/Y H:i') }}</p>
                                </div>

                                @if($h->delivery)
                                    @if($h->delivery->status === 'EM_ROTA')
                                        <span class="sa-badge sa-badge-info">Em Rota</span>
                                    @elseif($h->delivery->status === 'ENTREGUE')
                                        <span class="sa-badge sa-badge-success">Entregue</span>
                                    @endif
                                @else
                                    <span class="sa-badge bg-gray-200 text-gray-700">Balcão</span>
                                @endif
                            </div>

                            <div class="space-y-1 mb-3">
                                @foreach ($h->items as $item)
                                    <p class="text-xs text-gray-600 truncate">• {{ $item->quantity }}x {{ $item->medication->name ?? 'Med' }}</p>
                                @endforeach
                            </div>
                        </div>

                        @if($h->delivery && $h->delivery->status === 'ENTREGUE')
                            <div class="mt-4 pt-3 border-t border-gray-200">
                                @php
                                    $signatureUrl = $h->delivery->signature_path ? asset('storage/' . $h->delivery->signature_path) : null;
                                    $lat = $h->delivery->latitude;
                                    $lng = $h->delivery->longitude;
                                    $date = $h->delivery->confirmed_at ? \Carbon\Carbon::parse($h->delivery->confirmed_at)->format('d/m/Y \à\s H:i') : 'Data não registrada';
                                @endphp

                                <button type="button"
                                    onclick="openReceiptModal('{{ $signatureUrl }}', '{{ $lat }}', '{{ $lng }}', '{{ $date }}')"
                                    class="w-full flex items-center justify-center gap-2 text-sm font-bold text-[#005a50] hover:text-[#00453d] bg-[#005a50]/10 hover:bg-[#005a50]/20 py-2 rounded-lg transition-colors">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                    </svg>
                                    Ver Comprovante
                                </button>
                            </div>
                        @endif
                    </div>
                @empty
                    <div class="sa-card col-span-full text-center py-12 bg-gray-50">
                        <p class="text-gray-500 font-medium">Nenhum histórico encontrado para os filtros aplicados.</p>
                    </div>
                @endforelse
            </div>

            <div>
                {{ $history->appends(['tab' => 'historico'])->links() }}
            </div>
        </div>

    </div>

    <div id="receiptModal" class="fixed inset-0 z-[100] hidden bg-gray-900/80 backdrop-blur-sm flex items-center justify-center p-4">
        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-sm overflow-hidden flex flex-col sa-fade-in">
            <div class="p-4 bg-white border-b border-gray-100 flex justify-between items-center">
                <h3 class="font-bold font-sora text-lg text-gray-900">Comprovante de Entrega</h3>
                <button type="button" onclick="closeReceiptModal()" class="text-gray-400 hover:text-gray-600 transition-colors">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>

            <div class="p-5 flex flex-col gap-4">
                <div class="text-center">
                    <p class="text-xs text-gray-500 uppercase tracking-wider font-bold mb-1">Entregue em</p>
                    <p id="receiptDate" class="text-sm font-medium text-gray-800"></p>
                </div>

                <div>
                    <p class="text-xs text-gray-500 uppercase tracking-wider font-bold mb-2">Assinatura do Recebedor</p>
                    <div class="border border-gray-200 rounded-xl bg-gray-50 h-40 flex items-center justify-center p-2">
                        <img id="receiptSignature" src="" alt="Assinatura" class="max-h-full max-w-full hidden">
                        <span id="receiptNoSignature" class="text-sm text-gray-400 hidden">Assinatura não coletada</span>
                    </div>
                </div>

                <div>
                    <p class="text-xs text-gray-500 uppercase tracking-wider font-bold mb-2">Localização GPS</p>
                    <div id="receiptGpsContainer" class="bg-blue-50 border border-blue-100 p-3 rounded-lg flex items-start gap-3">
                    </div>
                </div>
            </div>

            <div class="p-4 bg-gray-50 border-t border-gray-100 flex justify-end">
                <button type="button" onclick="closeReceiptModal()" class="px-5 py-2.5 text-sm font-bold text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors w-full">
                    Fechar
                </button>
            </div>
        </div>
    </div>

    <script>
        function openReceiptModal(signatureUrl, lat, lng, date) {
            document.getElementById('receiptModal').classList.remove('hidden');
            document.getElementById('receiptDate').innerText = date;

            const sigImg = document.getElementById('receiptSignature');
            const noSigTxt = document.getElementById('receiptNoSignature');

            if (signatureUrl && signatureUrl !== '') {
                sigImg.src = signatureUrl;
                sigImg.classList.remove('hidden');
                noSigTxt.classList.add('hidden');
            } else {
                sigImg.classList.add('hidden');
                noSigTxt.classList.remove('hidden');
            }

            const gpsContainer = document.getElementById('receiptGpsContainer');
            if (lat && lng && lat !== '' && lng !== '') {
                gpsContainer.innerHTML = `
                    <svg class="w-5 h-5 text-blue-600 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 11-6 0 3 3 0 016 0z"></path><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1115 0z"></path></svg>
                    <div>
                        <a href="https://maps.google.com/?q=${lat},${lng}" target="_blank" class="text-sm font-bold text-blue-700 hover:text-blue-900 hover:underline inline-flex items-center gap-1">
                            Ver no Mapa do Google
                            <svg class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"></path></svg>
                        </a>
                        <p class="text-xs text-blue-500 mt-0.5">${lat}, ${lng}</p>
                    </div>
                `;
            } else {
                gpsContainer.innerHTML = `
                    <svg class="w-5 h-5 text-gray-400 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M18.364 5.636a9 9 0 010 12.728m0 0l-2.829-2.829m2.829 2.829L21 21M15.536 8.464a5 5 0 010 7.072m0 0l-2.829-2.829m-4.243 2.829a4.978 4.978 0 01-1.414-2.83m-1.414 5.658a9 9 0 01-2.167-9.238m7.824 2.167a1 1 0 111.414 1.414m-1.414-1.414L3 3m8.293 8.293l1.414 1.414"></path></svg>
                    <p class="text-sm text-gray-500 font-medium">GPS não capturado no momento da entrega.</p>
                `;
            }
        }

        function closeReceiptModal() {
            document.getElementById('receiptModal').classList.add('hidden');
        }
    </script>
</x-app-layout>