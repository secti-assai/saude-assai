<x-app-layout>
    <x-slot name="header">
        <div class="sa-page-header">
            <h2 class="sa-page-title">Entregas</h2>
            <p class="sa-page-subtitle">Rastreamento de entregas de medicamentos</p>
        </div>
    </x-slot>

    <div class="space-y-6">
        @if (session('status'))
        <div class="sa-alert-success sa-fade-in">
            <svg class="w-5 h-5 text-green-600 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            <span class="text-sm font-medium">{{ session('status') }}</span>
        </div>
        @endif

        @if ($errors->any())
        <div class="bg-red-50 text-red-700 p-4 rounded-xl text-sm font-medium mb-4 sa-fade-in">
            <ul>
                @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
        @endif

        {{-- Barra de Filtros --}}
        <div class="bg-white p-4 rounded-xl shadow-sm mb-6 border border-gray-100">
            <form method="GET" action="{{ route('deliveries.index') }}" class="flex flex-col md:flex-row gap-4">
                <div class="flex-1">
                    <input type="text" name="search" placeholder="Buscar por paciente..." value="{{ request('search') }}" class="sa-input w-full">
                </div>
                <div>
                    <select name="status" class="sa-select">
                        <option value="ativos" {{ request('status', 'ativos') === 'ativos' ? 'selected' : '' }}>Entregas Ativas</option>
                        <option value="historico" {{ request('status') === 'historico' ? 'selected' : '' }}>Histórico (Concluídas/Falhas)</option>
                        <option value="todos" {{ request('status') === 'todos' ? 'selected' : '' }}>Todos</option>
                    </select>
                </div>
                <div>
                    <button type="submit" class="sa-btn-primary w-full md:w-auto">Filtrar</button>
                </div>
            </form>
        </div>

        {{-- Delivery Cards --}}
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            @forelse ($deliveries as $d)
            <div class="sa-card sa-fade-in">
                <div class="flex items-start justify-between mb-4">
                    <div>
                        <h4 class="font-bold text-gray-900">{{ $d->prescription->citizen->full_name ?? '—' }}</h4>
                        <p class="text-xs text-gray-500 mt-0.5">
                            Senha: {{ $d->prescription->attendance->queue_password ?? '—' }}
                        </p>
                    </div>
                    @php
                    $deliveryStatusMap = [
                    'PENDENTE' => 'sa-badge-warning',
                    'EM_ROTA' => 'sa-badge-info',
                    'ENTREGUE' => 'sa-badge-success',
                    'FALHA' => 'sa-badge-danger',
                    ];
                    @endphp
                    <span class="sa-badge {{ $deliveryStatusMap[$d->status] ?? 'sa-badge-gray' }}">
                        {{ str_replace('_', ' ', $d->status) }}
                    </span>
                </div>

                {{-- Address and Medications --}}
                <div class="bg-gray-50 rounded-xl p-3 mb-4 text-sm text-gray-600">
                    <div class="mb-2 space-y-1">
                        @foreach($d->prescription->items as $item)
                        <p>
                            <span class="font-medium text-gray-700">{{ $item->medication->name ?? 'Medicamento' }}</span>
                            <span class="text-gray-500">· {{ $item->dosage ?? 'Dose n/i' }} · {{ $item->frequency ?? 'Frequencia n/i' }} · Qtd: {{ $item->quantity }}</span>
                        </p>
                        @endforeach
                    </div>
                    <div class="flex items-start gap-2">
                        <svg class="w-4 h-4 text-gray-400 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 11-6 0 3 3 0 016 0z" />
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1115 0z" />
                        </svg>
                        <span>{{ $d->address ?? $d->prescription->citizen->address ?? 'Endereco nao informado' }}</span>
                    </div>
                </div>

                {{-- Mapa e Navegação Expansível --}}
                @php
                    // 1. Prioriza as coordenadas decimais oficiais
                    $lat = $d->latitude;
                    $lng = $d->longitude;
                    
                    // 2. Se não houver coordenadas, tentamos o fallback pelo endereço (opcional, mas seguro)
                    $rawAddress = trim($d->address ?? $d->prescription->citizen->address ?? '');
                    $fullAddress = str_ireplace([', Sede', ' Sede'], '', $rawAddress);
                    $fullAddress = trim(rtrim(trim($fullAddress), ','));
                    $encodedAddress = urlencode($fullAddress . ', Assaí - PR');

                    // 3. Define a query do GPS: Se tem lat/lng usa eles, senão usa o endereço
                    if ($lat && $lng) {
                        $queryGps = "{$lat},{$lng}";
                        $iframeUrl = "https://maps.google.com/maps?q={$lat},{$lng}&t=&z=15&ie=UTF8&iwloc=&output=embed";
                        $googleMapsUrl = "https://www.google.com/maps/search/?api=1&query={$lat},{$lng}";
                        $wazeUrl = "https://waze.com/ul?ll={$lat},{$lng}&navigate=yes";
                        $hasLocation = true;
                    } else {
                        $queryGps = $encodedAddress;
                        $iframeUrl = "https://maps.google.com/maps?q={$encodedAddress}&t=&z=15&ie=UTF8&iwloc=&output=embed";
                        $googleMapsUrl = "https://www.google.com/maps/search/?api=1&query={$encodedAddress}";
                        $wazeUrl = "https://waze.com/ul?q={$encodedAddress}&navigate=yes";
                        $hasLocation = $fullAddress !== '' && !str_contains(strtolower($fullAddress), 'informado');
                    }
                @endphp

                @if($hasLocation)
                <details class="mb-4 group">
                    <summary class="list-none cursor-pointer flex items-center gap-2 text-sm font-bold text-[#005a50] hover:text-[#00453d] transition-colors bg-[#005a50]/5 p-3 rounded-xl border border-[#005a50]/20 select-none">
                        <svg class="w-5 h-5 transition-transform duration-300 group-open:rotate-90" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
                        </svg>
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                        </svg>
                        Abrir Mapa e Navegação @if($lat && $lng) <span class="text-[10px] bg-green-100 text-green-700 px-1.5 py-0.5 rounded-md">GPS Ativo</span> @endif
                    </summary>

                    <div class="mt-3 p-3 border border-gray-100 rounded-xl bg-white shadow-sm space-y-3">
                        {{-- Mini Mapa Embutido --}}
                        <div class="w-full h-40 rounded-lg overflow-hidden border border-gray-200 bg-gray-100 relative">
                            <iframe
                                class="relative z-10"
                                width="100%"
                                height="100%"
                                frameborder="0"
                                scrolling="no"
                                marginheight="0"
                                marginwidth="0"
                                src="{{ $iframeUrl }}">
                            </iframe>
                        </div>

                        {{-- Botões de Navegação --}}
                        <div class="flex gap-2">
                            <a href="{{ $googleMapsUrl }}" target="_blank" rel="noopener noreferrer" class="flex-1 flex justify-center items-center gap-1.5 bg-blue-50 hover:bg-blue-100 text-blue-700 px-3 py-2 rounded-lg text-xs font-bold transition-colors">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                </svg>
                                Google Maps
                            </a>
                            <a href="{{ $wazeUrl }}" target="_blank" rel="noopener noreferrer" class="flex-1 flex justify-center items-center gap-1.5 bg-sky-50 hover:bg-sky-100 text-sky-700 px-3 py-2 rounded-lg text-xs font-bold transition-colors">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8" />
                                </svg>
                                Ir com Waze
                            </a>
                        </div>
                    </div>
                </details>
                @endif

                {{-- Status Update Form / Concurrency Lock --}}
                @php
                $isCentral = in_array(auth()->user()->role, ['admin_secti', 'gestor', 'auditor']);
                $isLockedByOther = $d->delivery_user_id && $d->delivery_user_id !== auth()->id() && !$isCentral;

                // MUDANÇA AQUI: Agora apenas o status 'ENTREGUE' trava de vez a tela do motoboy.
                $isFinalized = $d->status === 'ENTREGUE';
                $isFailed = $d->status === 'FALHA';
                @endphp

                @if($isFinalized)
                {{-- Trava de Segurança: Entrega já finalizada com sucesso --}}
                <div class="bg-green-50 text-sm p-3 rounded-xl border border-green-200 flex flex-col gap-1.5 shadow-sm">
                    <div class="flex items-center gap-2 font-bold text-green-700">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"></path>
                        </svg>
                        Entrega Concluída com Sucesso
                    </div>
                </div>
                @elseif($isFailed)
                {{-- Estado de Falha: Apenas avisa para devolver na farmácia --}}
                <div class="bg-red-50 text-sm p-3 rounded-xl border border-red-200 flex flex-col gap-2 shadow-sm">
                    <div class="flex items-center gap-2 font-bold text-red-700">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                        Falha na Entrega
                    </div>
                    @if($d->failure_reason)
                    <p class="text-xs text-red-600 font-medium">Motivo: {{ $d->failure_reason }}</p>
                    @endif
                    <div class="mt-1 pt-2 border-t border-red-200">
                        <p class="text-xs text-gray-700 font-bold">⚠️ Por favor, devolva este pacote na Farmácia para reatribuição.</p>
                    </div>
                </div>
                @elseif($isLockedByOther)
                <div class="bg-yellow-50 text-yellow-800 text-sm p-3 rounded-lg border border-yellow-200">
                    🔒 Esta entrega está em rota com outro entregador.
                </div>
                @else
                <form method="POST" action="{{ route('deliveries.update', $d) }}" class="space-y-3" onsubmit="return handleFormSubmit(event, this, '{{ $d->id }}')">
                    @csrf
                    @method('PUT')

                    <div class="flex gap-2">
                        <div class="flex-1">
                            <select name="status" class="sa-select text-sm" onchange="toggleFailureReason(this)">
                                <option value="EM_ROTA" {{ $d->status === 'EM_ROTA' ? 'selected' : '' }}>Em Rota (Assumir)</option>
                                <option value="ENTREGUE" {{ $d->status === 'ENTREGUE' ? 'selected' : '' }}>Entregue</option>
                                <option value="FALHA" {{ $d->status === 'FALHA' ? 'selected' : '' }}>Falha na Entrega</option>
                            </select>
                        </div>
                        <button type="submit" class="sa-btn-primary text-sm">
                            Atualizar
                        </button>
                    </div>

                    {{-- Motivo da Falha --}}
                    <div class="failure-reason-container mt-2 transition-all duration-300" style="display: {{ $d->status === 'FALHA' ? 'block' : 'none' }}">
                        <input type="text" name="failure_reason" class="sa-input text-xs w-full" placeholder="Ex: Endereço não localizado, Cliente ausente..." value="{{ old('failure_reason', $d->failure_reason) }}">
                    </div>

                    {{-- GPS fields (collapsible) --}}
                    <details class="text-sm">
                        <summary class="text-gray-500 cursor-pointer hover:text-gray-700 transition flex items-center gap-1">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 11-6 0 3 3 0 016 0z" />
                            </svg>
                            Coordenadas GPS Manuais
                        </summary>
                        <div class="grid grid-cols-2 gap-2 mt-2">
                            <input name="gps_lat" class="sa-input text-xs" placeholder="Latitude" value="{{ old('gps_lat', $d->gps_lat) }}">
                            <input name="gps_lng" class="sa-input text-xs" placeholder="Longitude" value="{{ old('gps_lng', $d->gps_lng) }}">
                        </div>
                    </details>
                </form>
                @endif
            </div>
            @empty
            <div class="sa-card col-span-full text-center py-12">
                <svg class="w-12 h-12 mx-auto text-gray-300 mb-3" fill="none" stroke="currentColor" stroke-width="1" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 18.75a1.5 1.5 0 01-3 0m3 0a1.5 1.5 0 00-3 0m3 0h6m-9 0H3.375a1.125 1.125 0 01-1.125-1.125V14.25" />
                </svg>
                <p class="text-gray-500 font-medium">Nenhuma entrega encontrada.</p>
            </div>
            @endforelse
        </div>
    </div>

    {{-- MODAL DE ASSINATURA DIGITAL --}}
    <div id="signatureModal" class="fixed inset-0 z-[100] hidden bg-gray-900/80 backdrop-blur-sm flex items-center justify-center p-4">
        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-lg overflow-hidden flex flex-col">
            <div class="p-4 bg-white border-b border-gray-100 flex justify-between items-center">
                <h3 class="font-bold font-sora text-lg text-gray-900">Comprovante de Entrega</h3>
                <button type="button" onclick="closeSignatureModal()" class="text-gray-400 hover:text-gray-600 transition-colors">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>

            <div class="p-5 flex flex-col">
                <p class="text-sm text-gray-600 mb-3">Solicite a assinatura do paciente ou responsável pelo recebimento:</p>

                <div class="border-2 border-dashed border-gray-300 rounded-xl bg-gray-50 relative h-[280px] w-full overflow-hidden shadow-inner">
                    <canvas id="signatureCanvas" class="absolute inset-0 w-full h-full cursor-crosshair touch-none"></canvas>
                </div>

                <div class="flex justify-between mt-3 items-center">
                    <button type="button" onclick="clearCanvas()" class="text-sm font-bold text-red-600 hover:text-red-800 transition-colors">
                        Limpar Quadro
                    </button>
                    <div id="gps-status" class="text-xs font-medium text-orange-500 flex items-center bg-orange-50 px-3 py-1.5 rounded-full border border-orange-200">
                        <svg class="w-4 h-4 mr-1.5 animate-spin" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        Buscando Localização...
                    </div>
                </div>
            </div>

            <div class="p-4 bg-gray-50 border-t border-gray-100 flex justify-end gap-3">
                <button type="button" onclick="closeSignatureModal()" class="px-5 py-2.5 text-sm font-bold text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors">Cancelar</button>
                <button type="button" id="btnConfirmDelivery" onclick="submitDelivery()" class="px-5 py-2.5 text-sm font-bold text-white bg-[#005a50] hover:bg-[#00453d] rounded-lg shadow-sm transition-all focus:outline-none focus:ring-2 focus:ring-[#005a50]/50 disabled:opacity-50 disabled:cursor-not-allowed">
                    Finalizar Entrega
                </button>
            </div>

            <form id="deliveryModalForm" method="POST" action="">
                @csrf
                @method('PUT')
                <input type="hidden" name="status" value="ENTREGUE">
                <input type="hidden" name="signature" id="signatureInput">
                <input type="hidden" name="gps_lat" id="latInput">
                <input type="hidden" name="gps_lng" id="lngInput">
            </form>
        </div>
    </div>

    <script>
        function toggleFailureReason(selectElement) {
            const form = selectElement.closest('form');
            const reasonContainer = form.querySelector('.failure-reason-container');
            const reasonInput = form.querySelector('input[name="failure_reason"]');

            if (selectElement.value === 'FALHA') {
                reasonContainer.style.display = 'block';
                reasonInput.setAttribute('required', 'required');
            } else {
                reasonContainer.style.display = 'none';
                reasonInput.removeAttribute('required');
                reasonInput.value = '';
            }
        }

        let currentSubmitBtn = null;

        function handleFormSubmit(event, form, deliveryId) {
            const status = form.querySelector('select[name="status"]').value;

            if (status === 'ENTREGUE') {
                event.preventDefault();
                event.stopImmediatePropagation(); // Impede script global de travar o botão

                currentSubmitBtn = form.querySelector('button[type="submit"]');
                if (currentSubmitBtn) {
                    currentSubmitBtn.blur();
                }

                const actionUrl = form.action;
                openSignatureModal(actionUrl);
                return false;
            }

            return true;
        }

        let canvas, ctx;
        let isDrawing = false;

        document.addEventListener("DOMContentLoaded", () => {
            canvas = document.getElementById('signatureCanvas');
            ctx = canvas.getContext('2d');

            ctx.lineWidth = 3;
            ctx.lineCap = 'round';
            ctx.lineJoin = 'round';
            ctx.strokeStyle = '#1e3a8a';

            canvas.addEventListener('mousedown', startDrawing);
            canvas.addEventListener('mousemove', draw);
            canvas.addEventListener('mouseup', stopDrawing);
            canvas.addEventListener('mouseout', stopDrawing);

            canvas.addEventListener('touchstart', handleTouchStart, {
                passive: false
            });
            canvas.addEventListener('touchmove', handleTouchMove, {
                passive: false
            });
            canvas.addEventListener('touchend', stopDrawing);
        });

        function openSignatureModal(actionUrl) {
            document.getElementById('signatureModal').classList.remove('hidden');
            document.getElementById('deliveryModalForm').action = actionUrl;

            setTimeout(() => {
                resizeCanvas();
                clearCanvas();
                captureGPS();
            }, 50);
        }

        function closeSignatureModal() {
            document.getElementById('signatureModal').classList.add('hidden');

            if (currentSubmitBtn) {
                setTimeout(() => {
                    currentSubmitBtn.disabled = false;
                    currentSubmitBtn.innerHTML = 'Atualizar';
                    currentSubmitBtn.classList.remove('opacity-50', 'cursor-not-allowed', 'pointer-events-none');
                    currentSubmitBtn.blur();
                    currentSubmitBtn = null;
                }, 50);
            }
        }

        function resizeCanvas() {
            const rect = canvas.getBoundingClientRect();
            canvas.width = rect.width;
            canvas.height = rect.height;
            ctx.lineWidth = 3;
            ctx.lineCap = 'round';
            ctx.lineJoin = 'round';
            ctx.strokeStyle = '#1e3a8a';
        }

        function clearCanvas() {
            ctx.clearRect(0, 0, canvas.width, canvas.height);
        }

        function getPos(evt) {
            const rect = canvas.getBoundingClientRect();
            const clientX = evt.touches ? evt.touches[0].clientX : evt.clientX;
            const clientY = evt.touches ? evt.touches[0].clientY : evt.clientY;
            return {
                x: clientX - rect.left,
                y: clientY - rect.top
            };
        }

        function startDrawing(evt) {
            isDrawing = true;
            const pos = getPos(evt);
            ctx.beginPath();
            ctx.moveTo(pos.x, pos.y);
        }

        function handleTouchStart(evt) {
            evt.preventDefault();
            startDrawing(evt);
        }

        function draw(evt) {
            if (!isDrawing) return;
            evt.preventDefault();
            const pos = getPos(evt);
            ctx.lineTo(pos.x, pos.y);
            ctx.stroke();
        }

        function handleTouchMove(evt) {
            draw(evt);
        }

        function stopDrawing() {
            isDrawing = false;
            ctx.closePath();
        }

        function captureGPS() {
            const statusText = document.getElementById('gps-status');

            statusText.className = "text-xs font-medium text-orange-600 flex items-center bg-orange-50 px-3 py-1.5 rounded-full border border-orange-200";
            statusText.innerHTML = '<svg class="w-4 h-4 mr-1.5 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg> Buscando Localização...';

            if (navigator.geolocation) {
                navigator.geolocation.getCurrentPosition(
                    (position) => {
                        document.getElementById('latInput').value = position.coords.latitude;
                        document.getElementById('lngInput').value = position.coords.longitude;

                        statusText.className = "text-xs font-bold text-green-700 flex items-center bg-green-50 px-3 py-1.5 rounded-full border border-green-200";
                        statusText.innerHTML = '<svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"></path></svg> GPS Capturado';

                        document.getElementById('btnConfirmDelivery').disabled = false;
                    },
                    (error) => {
                        statusText.className = "text-xs font-bold text-red-700 flex items-center bg-red-50 px-3 py-1.5 rounded-full border border-red-200";
                        statusText.innerHTML = '⚠️ Erro no GPS. Permita o acesso.';
                        document.getElementById('btnConfirmDelivery').disabled = false;
                    }, {
                        enableHighAccuracy: true,
                        timeout: 10000
                    }
                );
            } else {
                statusText.innerHTML = 'GPS não suportado.';
                document.getElementById('btnConfirmDelivery').disabled = false;
            }
        }

        function submitDelivery() {
            const btn = document.getElementById('btnConfirmDelivery');
            const blank = document.createElement('canvas');
            blank.width = canvas.width;
            blank.height = canvas.height;

            if (canvas.toDataURL() === blank.toDataURL()) {
                alert("Por favor, colete a assinatura antes de confirmar a entrega.");
                btn.blur();
                return;
            }

            btn.disabled = true;
            btn.innerText = "Salvando...";

            const dataURL = canvas.toDataURL('image/png');
            document.getElementById('signatureInput').value = dataURL;

            document.getElementById('deliveryModalForm').submit();
        }
    </script>
</x-app-layout>