<x-app-layout>
    <x-slot name="header">
        <div class="sa-page-header flex items-center gap-3">
            <a href="{{ route('triagem.fila') }}" class="text-gray-400 hover:text-gray-700 transition" title="Voltar à fila">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18" />
                </svg>
            </a>
            <div>
                <h2 class="sa-page-title">Cidadão — Buscar Cidadão</h2>
                <p class="sa-page-subtitle">Busque na base local de atendimento para iniciar o atendimento</p>
            </div>
        </div>
    </x-slot>

    <div class="space-y-6">

        {{-- Alertas do Sistema --}}
        @if ($errors->any())
            <div class="bg-red-50 border-l-4 border-red-500 p-4 rounded-lg">
                <ul class="text-sm text-red-700 list-disc list-inside space-y-1">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif
        @if (session('status'))
            <div class="sa-alert-success"><span class="text-sm font-medium">{{ session('status') }}</span></div>
        @endif

        {{-- Bloco de Filtros (Estilo e-SUS) --}}
        <div class="sa-card">
            <div class="sa-card-header flex items-center justify-between">
                <h3 class="sa-card-title">Cidadão</h3>
                <button type="button" onclick="abrirModalCadastro()" class="sa-btn-primary inline-flex items-center gap-2 !py-2 !px-4 !text-sm">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                    </svg>
                    Novo Cadastro
                </button>
            </div>

            <div class="p-5">
                <form method="GET" action="{{ route('triagem.cidadao') }}" id="form-busca">
                    <div class="mb-4">
                        <label for="busca" class="sa-label">Nome / CNS / CPF do cidadão</label>
                        <div class="relative">
                            <input id="busca" name="busca" type="text" class="sa-input pr-10" placeholder="Digite o nome, CPF ou CNS..." value="{{ $busca }}">
                            @if ($busca)
                                <a href="{{ route('triagem.cidadao') }}" class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                                    </svg>
                                </a>
                            @endif
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-5">
                        <div>
                            <label for="data_nascimento" class="sa-label">Data de nascimento</label>
                            <input id="data_nascimento" name="data_nascimento" type="date" class="sa-input" value="{{ $dataNasc }}">
                        </div>
                        <div>
                            <label for="nome_mae" class="sa-label">Nome da mãe</label>
                            <input id="nome_mae" name="nome_mae" type="text" class="sa-input" placeholder="Nome da mãe" value="{{ $nomeMae }}">
                        </div>
                        <div>
                            <label for="municipio_nascimento" class="sa-label">Município de nascimento</label>
                            <input id="municipio_nascimento" name="municipio_nascimento" type="text" class="sa-input" placeholder="Município" value="{{ $municipioNasc }}">
                        </div>
                    </div>

                    <div class="flex items-center justify-between gap-3">
                        <a href="{{ route('triagem.cidadao') }}" class="bg-gray-200 hover:bg-gray-300 text-gray-700 font-semibold py-2 px-5 rounded-lg transition text-sm">
                            Limpar filtros
                        </a>
                        <button type="submit" class="sa-btn-primary flex items-center gap-2">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" />
                            </svg>
                            Buscar cidadão
                        </button>
                    </div>
                </form>
            </div>

            {{-- Lista de Resultados Encontrados --}}
            @if (request()->has('busca'))
                <div class="border-t border-gray-100 p-5 space-y-6">
                    <div>
                        <h4 class="text-base font-bold text-gray-800 mb-3">Cadastros na base local</h4>

                        @if ($resultados->isEmpty())
                            <p class="text-sm text-gray-500 italic">Nenhum resultado encontrado na base local de triagem.</p>
                        @else
                            <div class="space-y-3">
                                @foreach ($resultados as $p)
                                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 border border-gray-200 rounded-lg p-4 bg-gray-50 hover:bg-white transition">
                                        <div class="space-y-0.5 text-sm">
                                            <p class="font-semibold text-gray-900">{{ $p->full_name }}</p>
                                            <div class="flex flex-wrap gap-x-4 gap-y-0.5 text-gray-500 text-xs">
                                                @if ($p->cpf) <span>CPF {{ $p->cpf_formatted }}</span> @endif
                                                @if ($p->cns) <span>CNS {{ $p->cns }}</span> @endif
                                                <span>Nasc. {{ $p->birth_date?->format('d/m/Y') }} @if ($p->idade) ({{ $p->idade }} anos) @endif</span>
                                                @if ($p->sexo) <span>Sexo {{ \App\Models\TriagemPaciente::sexoOptions()[$p->sexo] ?? $p->sexo }}</span> @endif
                                                @if ($p->nome_mae) <span>Nome da mãe {{ $p->nome_mae }}</span> @endif
                                                @if ($p->municipio_nascimento) <span>Município de nasc. {{ $p->municipio_nascimento }}</span> @endif
                                            </div>
                                            
                                            @php
                                                $statusClass = match ($p->status) {
                                                    'AGUARDANDO' => 'bg-blue-100 text-blue-700',
                                                    'EM_TRIAGEM' => 'bg-amber-100 text-amber-700',
                                                    'FINALIZADO' => 'bg-emerald-100 text-emerald-700',
                                                    default => 'bg-gray-100 text-gray-700',
                                                };
                                            @endphp
                                            <div class="mt-1">
                                                <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-semibold {{ $statusClass }}">
                                                    {{ $p->statusLabel() }}
                                                </span>
                                                @if($p->tipo_atendimento)
                                                    <span class="text-xs bg-gray-200 text-gray-700 px-2 py-0.5 rounded ml-2 font-medium">
                                                        {{ $p->tipo_atendimento }}
                                                    </span>
                                                @endif
                                                @if ($p->arrived_at)
                                                    <span class="text-xs text-gray-400 ml-2">Chegou às {{ $p->arrived_at->format('H:i') }}</span>
                                                @endif
                                            </div>
                                        </div>

                                        {{-- Ações baseadas no estado unificado --}}
                                        <div class="flex items-center gap-3 shrink-0">
                                            <a href="{{ route('triagem.cidadao.historico', $p) }}" class="bg-gray-200 hover:bg-gray-300 text-gray-700 font-semibold py-2 px-4 rounded-lg text-sm whitespace-nowrap transition shadow-sm" title="Ver histórico">
                                                Visualizar
                                            </a>
                                            <button type="button" onclick="abrirModalAtendimento('{{ $p->status === 'AGUARDANDO' ? route('triagem.atendimento.iniciar', $p) : route('triagem.atendimento.novo', $p) }}', '{{ $p->full_name }}')" class="sa-btn-primary !py-2 !px-4 !text-sm whitespace-nowrap shadow-sm">
                                                Atender
                                            </button>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </div>

                    <hr class="border-gray-200">

                    <div>
                        <h4 class="text-base font-bold text-gray-800 mb-1">Cidadão não encontrado?</h4>
                        <p class="text-sm text-gray-500 mb-3">Se o paciente não está cadastrado na base local de triagem, utilize o botão abaixo para efetuar o cadastro.</p>
                        <button type="button" onclick="abrirModalCadastro()" class="sa-btn-primary inline-flex items-center gap-2">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                            </svg>
                            Cadastrar cidadão
                        </button>
                    </div>\
                </div>
            @else
                <div class="border-t border-gray-100 p-5 text-sm text-gray-500 italic">
                    Utilize o campo acima para buscar um cidadão na base local de triagem.
                </div>
            @endif
        </div>
    </div>

    {{-- MODAL INTERATIVO SELEÇÃO DE ATENDIMENTO --}}
    <div id="modal-atendimento" class="fixed inset-0 z-50 hidden overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" onclick="fecharModalAtendimento()"></div>
            <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

            <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                <form id="form-modal-atendimento" method="POST" action="">
                    @csrf
                    <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                        <div class="sm:flex sm:items-start">
                            <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left w-full">
                                <h3 class="text-lg leading-6 font-bold text-gray-900" id="modal-title">
                                    Iniciar Atendimento
                                </h3>
                                <p class="text-xs text-gray-500 mt-1">
                                    Selecione o motivo/serviço para o cidadão: <span id="modal-nome-paciente" class="font-semibold text-gray-700"></span>
                                </p>

                                <div class="mt-4">
                                    <label for="tipo_atendimento" class="sa-label">Serviço/Tipo de Atendimento</label>
                                    <select id="tipo_atendimento" name="tipo_atendimento" required class="sa-input w-full mt-1">
                                        <option value="" disabled selected>Escolha uma opção...</option>
                                        <option value="Urgências e Emergências de Baixa Complexidade">Urgências e Emergências de Baixa Complexidade</option>
                                        <option value="Vacinação">Vacinação</option>
                                        <option value="Profilaxias de Urgência">Profilaxias de Urgência</option>
                                        <option value="Notificações Compulsórias">Notificações Compulsórias</option>
                                        <option value="Acolhimento e Classificação de Risco">Acolhimento e Classificação de Risco</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse gap-2">
                        <button type="submit" class="sa-btn-primary !bg-emerald-600 hover:!bg-emerald-700 w-full sm:w-auto text-sm">
                            Confirmar e Enviar para Fila
                        </button>
                        <button type="button" onclick="fecharModalAtendimento()" class="mt-3 w-full sm:mt-0 sm:w-auto bg-white border border-gray-300 rounded-lg shadow-sm px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 transition">
                            Cancelar
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- MODAL CADASTRO CIDADÃO --}}
    <div id="modal-cadastro" class="fixed inset-0 z-50 hidden overflow-y-auto" aria-labelledby="modal-cadastro-title" role="dialog" aria-modal="true">
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" onclick="fecharModalCadastro()"></div>
            <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

            <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-4xl sm:w-full">
                <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4 border-b border-gray-100">
                    <h3 class="text-lg leading-6 font-bold text-gray-900" id="modal-cadastro-title">
                        Cadastrar Novo Cidadão
                    </h3>
                    <p class="text-sm text-gray-500 mt-1">Insira os dados pessoais do cidadão para incluí-lo na base de atendimento.</p>
                </div>
                <form method="POST" action="{{ route('triagem.cidadao.cadastrar') }}" class="px-4 py-5 sm:p-6 bg-gray-50">
                    @csrf
                    
                    {{-- CPF / CNS --}}
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-4">
                        <div>
                            <label for="cpf" class="sa-label">CPF</label>
                            <input id="cpf" name="cpf" type="text" class="sa-input" placeholder="000.000.000-00" maxlength="14" value="{{ old('cpf', preg_match('/^\d+$/', $busca ?? '') && strlen($busca ?? '') <= 11 ? $busca : '') }}">
                        </div>
                        <div>
                            <label for="cns" class="sa-label">CNS</label>
                            <input id="cns" name="cns" type="text" class="sa-input" placeholder="000 0000 0000 0000" maxlength="18" value="{{ old('cns', preg_match('/^\d+$/', $busca ?? '') && strlen($busca ?? '') > 11 ? $busca : '') }}">
                        </div>
                    </div>

                    {{-- Nome completo / Nome social --}}
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-4">
                        <div>
                            <label for="full_name" class="sa-label">Nome completo <span class="text-red-500">*</span></label>
                            <input id="full_name" name="full_name" type="text" class="sa-input" required value="{{ old('full_name', !preg_match('/^\d/', $busca ?? '') && ($busca ?? '') !== '' ? strtoupper($busca) : '') }}">
                        </div>
                        <div>
                            <label for="social_name" class="sa-label">Nome social</label>
                            <input id="social_name" name="social_name" type="text" class="sa-input" value="{{ old('social_name') }}">
                        </div>
                    </div>

                    {{-- Data de nascimento / Sexo --}}
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-4">
                        <div>
                            <label for="birth_date" class="sa-label">Data de nascimento <span class="text-red-500">*</span></label>
                            <input id="birth_date" name="birth_date" type="date" class="sa-input" required value="{{ old('birth_date', $dataNasc ?? '') }}">
                        </div>
                        <div>
                            <label for="sexo" class="sa-label">Sexo <span class="text-red-500">*</span></label>
                            <select id="sexo" name="sexo" class="sa-input" required>
                                <option value="">Selecione...</option>
                                @foreach($sexoOptions ?? [] as $val => $label)
                                    <option value="{{ $val }}" @selected(old('sexo') === $val)>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    {{-- Raça/Cor / Etnia --}}
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-4">
                        <div>
                            <label for="raca_cor" class="sa-label">Raça/Cor <span class="text-red-500">*</span></label>
                            <select id="raca_cor" name="raca_cor" class="sa-input" required>
                                <option value="">Selecione...</option>
                                @foreach($racaCorOptions ?? [] as $val => $label)
                                    <option value="{{ $val }}" @selected(old('raca_cor') === $val)>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label for="etnia" class="sa-label">Etnia</label>
                            <input id="etnia" name="etnia" type="text" class="sa-input" placeholder="Etnia (opcional)" value="{{ old('etnia') }}">
                        </div>
                    </div>

                    {{-- Nome da mãe --}}
                    <div class="mb-4">
                        <label for="nome_mae_cad" class="sa-label">Nome da mãe <span class="text-red-500">*</span></label>
                        <div class="flex gap-3 items-center">
                            <input id="nome_mae_cad" name="nome_mae" type="text" class="sa-input flex-1" required value="{{ old('nome_mae', $nomeMae ?? '') }}">
                            <label class="flex items-center gap-1 text-xs text-gray-500 whitespace-nowrap cursor-pointer">
                                <input type="checkbox" onchange="document.getElementById('nome_mae_cad').readOnly = this.checked; document.getElementById('nome_mae_cad').value = this.checked ? 'Desconhecido' : '';" class="rounded border-gray-300 text-blue-600">
                                Desconhece essa informação
                            </label>
                        </div>
                    </div>

                    {{-- Nome do pai --}}
                    <div class="mb-4">
                        <label for="nome_pai" class="sa-label">Nome do pai</label>
                        <div class="flex gap-3 items-center">
                            <input id="nome_pai" name="nome_pai" type="text" class="sa-input flex-1" value="{{ old('nome_pai') }}">
                            <label class="flex items-center gap-1 text-xs text-gray-500 whitespace-nowrap cursor-pointer">
                                <input type="checkbox" onchange="document.getElementById('nome_pai').readOnly = this.checked; document.getElementById('nome_pai').value = this.checked ? 'Desconhecido' : '';" class="rounded border-gray-300 text-blue-600">
                                Desconhece essa informação
                            </label>
                        </div>
                    </div>

                    {{-- Município de nascimento / Telefone --}}
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-2">
                        <div>
                            <label for="municipio_nascimento_cad" class="sa-label">Município de nascimento <span class="text-red-500">*</span></label>
                            <input id="municipio_nascimento_cad" name="municipio_nascimento" type="text" class="sa-input" required placeholder="Cidade - UF" value="{{ old('municipio_nascimento', $municipioNasc ?? '') }}">
                        </div>
                        <div>
                            <label for="phone" class="sa-label">Telefone / WhatsApp</label>
                            <input id="phone" name="phone" type="text" class="sa-input" placeholder="(00) 00000-0000" value="{{ old('phone') }}">
                        </div>
                    </div>
                    
                    <div class="mt-6 flex flex-row-reverse gap-3">
                        <button type="submit" class="sa-btn-primary">
                            Cadastrar Cidadão
                        </button>
                        <button type="button" onclick="fecharModalCadastro()" class="bg-white border border-gray-300 rounded-lg shadow-sm px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 transition">
                            Cancelar
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- Scripts Javascript --}}
    <script>
        function abrirModalAtendimento(rotaUrl, nomePaciente) {
            const modal = document.getElementById('modal-atendimento');
            const form = document.getElementById('form-modal-atendimento');
            const txtNome = document.getElementById('modal-nome-paciente');
            
            form.action = rotaUrl;
            txtNome.textContent = nomePaciente;
            document.getElementById('tipo_atendimento').value = ""; 
            
            modal.classList.remove('hidden');
        }

        function fecharModalAtendimento() {
            document.getElementById('modal-atendimento').classList.add('hidden');
        }

        function abrirModalCadastro() {
            document.getElementById('modal-cadastro').classList.remove('hidden');
        }

        function fecharModalCadastro() {
            document.getElementById('modal-cadastro').classList.add('hidden');
        }

        document.addEventListener('DOMContentLoaded', function() {
            const buscaInput = document.getElementById('busca');
            const cpfInput = document.getElementById('cpf');
            const cnsInput = document.getElementById('cns');
            const phoneInput = document.getElementById('phone');

            function formatSearch(value) {
                if (/[a-zA-Z]/.test(value)) return value;
                let v = value.replace(/\D/g, '');

                if (v.length > 11) {
                    if (v.length > 15) v = v.substring(0, 15);
                    let parts = [];
                    if (v.length > 0) parts.push(v.substring(0, 3));
                    if (v.length > 3) parts.push(v.substring(3, 7));
                    if (v.length > 7) parts.push(v.substring(7, 11));
                    if (v.length > 11) parts.push(v.substring(11, 15));
                    return parts.join(' ');
                }

                if (v.length > 9) {
                    return `${v.substring(0, 3)}.${v.substring(3, 6)}.${v.substring(6, 9)}-${v.substring(9)}`;
                } else if (v.length > 6) {
                    return `${v.substring(0, 3)}.${v.substring(3, 6)}.${v.substring(6)}`;
                } else if (v.length > 3) {
                    return `${v.substring(0, 3)}.${v.substring(3)}`;
                }
                return v;
            }

            function formatPhone(value) {
                let v = value.replace(/\D/g, '');
                if (v.length > 11) v = v.substring(0, 11);
                
                if (v.length > 6) {
                    if (v.length > 10) return `(${v.substring(0, 2)}) ${v.substring(2, 7)}-${v.substring(7)}`;
                    return `(${v.substring(0, 2)}) ${v.substring(2, 6)}-${v.substring(6)}`;
                } else if (v.length > 2) {
                    return `(${v.substring(0, 2)}) ${v.substring(2)}`;
                }
                return v;
            }

            if (buscaInput) {
                buscaInput.value = formatSearch(buscaInput.value);
                buscaInput.addEventListener('input', function(e) {
                    e.target.value = formatSearch(e.target.value);
                });
            }

            if (cpfInput) {
                cpfInput.value = formatSearch(cpfInput.value);
                cpfInput.addEventListener('input', function(e) { e.target.value = formatSearch(e.target.value); });
            }

            if (cnsInput) {
                cnsInput.value = formatSearch(cnsInput.value);
                cnsInput.addEventListener('input', function(e) { e.target.value = formatSearch(e.target.value); });
            }

            if (phoneInput) {
                phoneInput.value = formatPhone(phoneInput.value);
                phoneInput.addEventListener('input', function(e) { e.target.value = formatPhone(e.target.value); });
            }
        });
    </script>
</x-app-layout>