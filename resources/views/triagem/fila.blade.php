<x-app-layout>
    <x-slot name="header">
        <div class="sa-page-header">
            <h2 class="sa-page-title">Atendimento UBS — Fila de Atendimentos</h2>
            <p class="sa-page-subtitle">Gerencie a fila de pacientes aguardando atendimento</p>
        </div>
    </x-slot>

    <div class="space-y-6">

        {{-- Flash messages --}}
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

        {{-- Atalhos rápidos --}}
        <div class="flex flex-wrap items-center gap-3">
            <a href="{{ route('triagem.cidadao') }}" class="sa-btn-primary flex items-center gap-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                </svg>
                Buscar / Cadastrar Cidadão
            </a>
            <span class="text-sm text-gray-500">
                Total hoje:
                <strong class="text-gray-800">{{ $pacientes->count() }}</strong>
            </span>
        </div>

        {{-- Filtros --}}
        <div class="sa-card">
            <div class="sa-card-header">
                <h3 class="sa-card-title">Filtros da Fila</h3>
            </div>
            <div class="rounded-lg border border-emerald-100 bg-emerald-50/60 p-4">
                <form method="GET" action="{{ route('triagem.fila') }}"
                    class="grid grid-cols-1 md:grid-cols-4 gap-3 items-end">
                    <div>
                        <label for="date_start" class="sa-label">Data inicial</label>
                        <input id="date_start" name="date_start" type="date" class="sa-input"
                            value="{{ $filters['date_start'] ?? now()->toDateString() }}">
                    </div>
                    <div>
                        <label_for="date_end" class="sa-label">Data final</label>
                        <input id="date_end" name="date_end" type="date" class="sa-input"
                            value="{{ $filters['date_end'] ?? now()->toDateString() }}">
                    </div>
                    <div>
                        <label for="status" class="sa-label">Status</label>
                        <select id="status" name="status" class="sa-input">
                            @foreach ($statusOptions as $value => $label)
                                <option value="{{ $value }}" @selected(($filters['status'] ?? 'TODOS') === $value)>{{ $label }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="flex items-center justify-end gap-2">
                        <button type="submit" class="sa-btn-primary">Aplicar</button>
                        <a href="{{ route('triagem.fila') }}"
                            class="bg-gray-300 hover:bg-gray-400 text-gray-800 font-bold py-2 px-4 rounded">
                            Limpar
                        </a>
                    </div>
                </form>
            </div>

            {{-- Tabela da fila --}}
            <div class="overflow-x-auto mt-4">
                <table class="sa-table">
                    <thead>
                        <tr>
                            <th>Chegada</th>
                            <th>Paciente</th>
                            <th>Nasc.</th>
                            <th>Status</th>
                            <th>Prioridade</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($pacientes as $p)
                            @php
                                $statusClass = match ($p->status) {
                                    'AGUARDANDO' => 'bg-blue-100 text-blue-700',
                                    'EM_TRIAGEM' => 'bg-amber-100 text-amber-700',
                                    'FINALIZADO' => 'bg-emerald-100 text-emerald-700',
                                    default => 'bg-gray-100 text-gray-700',
                                };
                                $colorClass = match ($p->priority_color) {
                                    'VERMELHO' => 'bg-red-100 text-red-800',
                                    'LARANJA' => 'bg-orange-100 text-orange-800',
                                    'AMARELO' => 'bg-yellow-100 text-yellow-800',
                                    'VERDE' => 'bg-green-100 text-green-800',
                                    default => 'bg-blue-50 text-blue-700',
                                };
                            @endphp
                            <tr>
                                <td class="text-sm text-gray-600">{{ $p->arrived_at?->format('H:i') ?? '—' }}</td>
                                <td>
                                    <p class="font-semibold text-gray-900">{{ $p->full_name }}</p>
                                    @if ($p->social_name)
                                        <p class="text-xs text-gray-400">Nome social: {{ $p->social_name }}</p>
                                    @endif
                                    @if ($p->cpf)
                                        <p class="text-xs text-gray-400">CPF: {{ $p->cpf_formatted }}</p>
                                    @endif
                                </td>
                                <td class="text-sm text-gray-600">
                                    {{ $p->birth_date?->format('d/m/Y') ?? '—' }}
                                    @if ($p->idade)
                                        <span class="text-xs text-gray-400">({{ $p->idade }} anos)</span>
                                    @endif
                                </td>
                                <td>
                                    <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-semibold {{ $statusClass }}">
                                        {{ $p->statusLabel() }}
                                    </span>
                                </td>
                                <td>
                                    <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-semibold {{ $colorClass }}">
                                        {{ $p->priorityColorLabel() }}
                                    </span>
                                </td>
                                <td class="space-y-1">
                                    @if ($p->status === 'AGUARDANDO')
                                        <form method="POST" action="{{ route('triagem.atendimento.iniciar', $p) }}">
                                            @csrf
                                            <button type="submit" class="sa-btn-primary !py-1.5 !px-3 !text-xs w-full">
                                                Iniciar Atendimento
                                            </button>
                                        </form>
                                    @elseif($p->status === 'EM_TRIAGEM')
                                        <form method="POST" action="{{ route('triagem.atendimento.finalizar', $p) }}" class="flex flex-col gap-1">
                                            @csrf
                                            <select name="tipo_atendimento" class="sa-input !py-1 !text-xs" required>
                                                <option value="" disabled selected>Selecione o procedimento...</option>
                                                <option value="puericultura">Acompanhamento de crescimento (puericultura)</option>
                                                <option value="papanicolau">Coleta de preventivo (Papanicolau)</option>
                                                <option value="exame_mamas">Exame clínico das mamas e mamografia</option>
                                                <option value="pre_natal">Pré-natal de risco habitual</option>
                                                <option value="teste_gravidez">Teste rápido de gravidez</option>
                                                <option value="planejamento_familiar">Planejamento familiar (DIU, anticoncepcionais)</option>
                                                <option value="hiperdia">Consultas de hipertensão e diabetes (HIPERDIA)</option>
                                                <option value="pa_glicemia">Verificação de pressão arterial e glicemia capilar</option>
                                                <option value="curativos">Curativos</option>
                                                <option value="retirada_pontos">Retirada de pontos</option>
                                                <option value="lavagem_ouvido">Lavagem de ouvido (remoção de cerume)</option>
                                                <option value="drenagem_abscesso">Drenagem de abscesso simples</option>
                                                <option value="coleta_exames">Coleta de exames laboratoriais</option>
                                                <option value="teste_pezinho">Teste do pezinho</option>
                                                <option value="vacinacao">Vacinação</option>
                                                <option value="dispensacao_medicamentos">Dispensação de medicamentos básicos</option>
                                                <option value="psicologo">Consulta com psicólogo</option>
                                                <option value="saude_mental">Acompanhamento de saúde mental (leve/moderado)</option>
                                                <option value="visita_domiciliar">Visita domiciliar (ACS e equipe)</option>
                                                <option value="saude_idoso">Avaliação e acompanhamento de saúde do idoso</option>
                                                <option value="eletrocardiograma">Eletrocardiograma (em unidades com suporte)</option>
                                            </select>

                                            <select name="priority_color" class="sa-input !py-1 !text-xs" required>
                                                <option value="" disabled selected>Selecione a classificação...</option>
                                                <option value="VERMELHO">Vermelho</option>
                                                <option value="LARANJA">Laranja</option>
                                                <option value="AMARELO">Amarelo</option>
                                                <option value="VERDE">Verde</option>
                                                <option value="AZUL">Azul</option>
                                            </select>

                                            <button type="submit" class="bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold py-1.5 px-3 rounded w-full">
                                                Finalizar
                                            </button>
                                        </form>
                                    @else
                                        <div class="space-y-1.5 text-center">
                                            <div>
                                                <span class="text-xs text-emerald-700 font-semibold block">Atendimento concluído</span>
                                                @if ($p->triagem_finished_at)
                                                    <span class="text-[10px] text-gray-400 block">às {{ $p->triagem_finished_at->format('H:i') }}</span>
                                                @endif
                                            </div>
                                            
                                            <form method="POST" action="{{ route('triagem.atendimento.novo', $p) }}">
                                                @csrf
                                                <button type="submit" class="w-full bg-emerald-50 hover:bg-emerald-100 text-emerald-700 border border-emerald-200 text-[11px] font-bold py-1 px-2 rounded transition flex items-center justify-center gap-1">
                                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                                                    </svg>
                                                    Nova Triagem
                                                </button>
                                            </form>
                                        </div>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center text-gray-500 py-8">
                                    Nenhum paciente na fila para o período selecionado.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-app-layout>