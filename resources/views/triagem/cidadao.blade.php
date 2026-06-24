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
                <a href="{{ route('triagem.cidadao.cadastro') }}" class="sa-btn-primary inline-flex items-center gap-2 !py-2 !px-4 !text-sm">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                    </svg>
                    Novo Cadastro
                </a>
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
                                            @if ($p->status === 'AGUARDANDO')
                                                <button type="button" onclick="abrirModalAtendimento('{{ route('triagem.atendimento.iniciar', $p) }}', '{{ $p->full_name }}')" class="sa-btn-primary !py-2 !px-4 !text-sm whitespace-nowrap">
                                                    Iniciar Triagem
                                                </button>
                                            @elseif ($p->status === 'EM_TRIAGEM')
                                                <a href="{{ route('triagem.fila') }}" class="text-sm text-blue-600 hover:underline whitespace-nowrap font-medium">
                                                    Ver na fila →
                                                </a>
                                            @else
                                                <button type="button" onclick="abrirModalAtendimento('{{ route('triagem.atendimento.novo', $p) }}', '{{ $p->full_name }}')" class="inline-flex items-center gap-1.5 bg-emerald-600 hover:bg-emerald-700 text-white font-semibold py-2 px-4 rounded-lg text-sm whitespace-nowrap transition shadow-sm">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                                                    </svg>
                                                    Nova Triagem
                                                </button>
                                            @endif

                                            <a href="{{ route('triagem.cidadao.historico', $p) }}" class="text-gray-400 hover:text-gray-600 p-1 rounded hover:bg-gray-100 transition" title="Ver histórico">
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 12C3.75 7.5 7.5 4.5 12 4.5s8.25 3 9.75 7.5c-1.5 4.5-5.25 7.5-9.75 7.5S3.75 16.5 2.25 12z" />
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0z" />
                                                </svg>
                                            </a>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </div>

                    <hr class="border-gray-200">

                    <div>
                        <h4 class="text-base font-bold text-gray-800 mb-1">Cidadão não encontrado?</h4>
                        <p class="text-sm text-gray-500 mb-3">Se o paciente não está cadastrado na base local de triagem, utilize o link abaixo para efetuar o cadastro.</p>
                        <a href="{{ route('triagem.cidadao.cadastro', ['busca' => $busca, 'data_nascimento' => $dataNasc, 'nome_mae' => $nomeMae, 'municipio_nascimento' => $municipioNasc]) }}" class="sa-btn-primary inline-flex items-center gap-2">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                            </svg>
                            Cadastrar cidadão
                        </a>
                    </div>
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
                                        
                                        <optgroup label="Saúde da Mulher e Criança">
                                            <option value="Acompanhamento de crescimento e desenvolvimento infantil (puericultura)">Acompanhamento de crescimento e desenvolvimento infantil (puericultura)</option>
                                            <option value="Coleta de preventivo (Papanicolau)">Coleta de preventivo (Papanicolau)</option>
                                            <option value="Exame clínico das mamas e solicitação de mamografia">Exame clínico das mamas e solicitação de mamografia</option>
                                            <option value="Pré-natal de risco habitual">Pré-natal de risco habitual</option>
                                            <option value="Teste rápido de gravidez">Teste rápido de gravidez</option>
                                            <option value="Planejamento familiar (inserção de DIU, anticoncepcionais)">Planejamento familiar (inserção de DIU, anticoncepcionais)</option>
                                            <option value="Teste do pezinho">Teste do pezinho</option>
                                        </optgroup>

                                        <optgroup label="Consultas e Acompanhamentos Crônicos">
                                            <option value="Consultas de hipertensão e diabetes (HIPERDIA)">Consultas de hipertensão e diabetes (HIPERDIA)</option>
                                            <option value="Verificação de pressão arterial e glicemia capilar">Verificação de pressão arterial e glicemia capilar</option>
                                            <option value="Avaliação e acompanhamento de saúde do idoso">Avaliação e acompanhamento de saúde do idoso</option>
                                        </optgroup>

                                        <optgroup label="Procedimentos e Enfermagem">
                                            <option value="Curativos">Curativos</option>
                                            <option value="Retirada de pontos">Retirada de pontos</option>
                                            <option value="Lavagem de ouvido (remoção de cerume)">Lavagem de ouvido (remoção de cerume)</option>
                                            <option value="Drenagem de abscesso simples">Drenagem de abscesso simples</option>
                                            <option value="Coleta de exames laboratoriais">Coleta de exames laboratoriais</option>
                                            <option value="Eletrocardiograma (em unidades com suporte)">Eletrocardiograma (em unidades com suporte)</option>
                                        </optgroup>

                                        <optgroup label="Saúde Mental e Cuidados Gerais">
                                            <option value="Consulta com psicólogo">Consulta com psicólogo</option>
                                            <option value="Acompanhamento de saúde mental (transtornos leves/moderados)">Acompanhamento de saúde mental (transtornos leves/moderados)</option>
                                            <option value="Vacinação">Vacinação</option>
                                            <option value="Dispensação de medicamentos básicos">Dispensação de medicamentos básicos</option>
                                            <option value="Visita domiciliar (agentes comunitários e equipe)">Visita domiciliar (agentes comunitários e equipe)</option>
                                        </optgroup>
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

        document.addEventListener('DOMContentLoaded', function() {
            const buscaInput = document.getElementById('busca');

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

            if (buscaInput) {
                buscaInput.value = formatSearch(buscaInput.value);
                buscaInput.addEventListener('input', function(e) {
                    e.target.value = formatSearch(e.target.value);
                });
            }
        });
    </script>
</x-app-layout>