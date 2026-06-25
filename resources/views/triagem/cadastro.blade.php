<x-app-layout>
    <x-slot name="header">
        <div class="sa-page-header flex items-center gap-3">
            <a href="{{ route('triagem.cidadao') }}"
               class="text-gray-400 hover:text-gray-700 transition"
               title="Voltar para busca">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18"/>
                </svg>
            </a>
            <div>
                <h2 class="sa-page-title">Cadastrar Novo Cidadão</h2>
                <p class="sa-page-subtitle">Insira os dados pessoais do cidadão para incluí-lo na base de atendimento</p>
            </div>
        </div>
    </x-slot>

    <div class="max-w-4xl mx-auto space-y-6">

        {{-- Flash messages / Erros --}}
        @if ($errors->any())
            <div class="bg-red-50 border-l-4 border-red-500 p-4 rounded-lg">
                <ul class="text-sm text-red-700 list-disc list-inside space-y-1">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        {{-- Formulário de Cadastro (estilo PEC — Dados Pessoais) --}}
        <div class="sa-card">
            <div class="sa-card-header">
                <h3 class="sa-card-title">Dados Pessoais — Novo Cadastro</h3>
            </div>

            <form method="POST" action="{{ route('triagem.cidadao.cadastrar') }}" class="p-5">
                @csrf

                {{-- Aviso bloco PEC --}}
                <div class="mb-5 rounded-md bg-blue-50 border border-blue-200 p-3 text-sm text-blue-800">
                    <strong>Bloco de dados pessoais:</strong> identifica o cidadão por meio do Cartão Nacional de Saúde (CNS) e/ou CPF.
                    O preenchimento dos campos obrigatórios é necessário para incluí-lo na base de atendimento e inseri-lo na fila de atendimento.
                </div>

                {{-- CPF / CNS --}}
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-4">
                    <div>
                        <label for="cpf" class="sa-label">CPF</label>
                        <input id="cpf" name="cpf" type="text" class="sa-input"
                               placeholder="000.000.000-00"
                               maxlength="14"
                               value="{{ old('cpf', preg_match('/^\d+$/', $busca) && strlen($busca) <= 11 ? $busca : '') }}">
                    </div>
                    <div>
                        <label for="cns" class="sa-label">CNS</label>
                        <input id="cns" name="cns" type="text" class="sa-input"
                               placeholder="000 0000 0000 0000"
                               maxlength="18"
                               value="{{ old('cns', preg_match('/^\d+$/', $busca) && strlen($busca) > 11 ? $busca : '') }}">
                    </div>
                </div>

                {{-- Nome completo / Nome social --}}
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-4">
                    <div>
                        <label for="full_name" class="sa-label">Nome completo <span class="text-red-500">*</span></label>
                        <input id="full_name" name="full_name" type="text" class="sa-input"
                               required value="{{ old('full_name', !preg_match('/^\d/', $busca) && $busca !== '' ? strtoupper($busca) : '') }}">
                    </div>
                    <div>
                        <label for="social_name" class="sa-label">Nome social</label>
                        <input id="social_name" name="social_name" type="text" class="sa-input"
                               value="{{ old('social_name') }}">
                    </div>
                </div>

                {{-- Data de nascimento / Sexo --}}
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-4">
                    <div>
                        <label for="birth_date" class="sa-label">Data de nascimento <span class="text-red-500">*</span></label>
                        <input id="birth_date" name="birth_date" type="date" class="sa-input"
                               required value="{{ old('birth_date', $dataNasc) }}">
                    </div>
                    <div>
                        <label for="sexo" class="sa-label">Sexo <span class="text-red-500">*</span></label>
                        <select id="sexo" name="sexo" class="sa-input" required>
                            <option value="">Selecione...</option>
                            @foreach($sexoOptions as $val => $label)
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
                            @foreach($racaCorOptions as $val => $label)
                                <option value="{{ $val }}" @selected(old('raca_cor') === $val)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label for="etnia" class="sa-label">Etnia</label>
                        <input id="etnia" name="etnia" type="text" class="sa-input"
                               placeholder="Etnia (opcional)" value="{{ old('etnia') }}">
                    </div>
                </div>

                {{-- Nome da mãe --}}
                <div class="mb-4">
                    <label for="nome_mae" class="sa-label">Nome da mãe <span class="text-red-500">*</span></label>
                    <div class="flex gap-3 items-center">
                        <input id="nome_mae" name="nome_mae" type="text" class="sa-input flex-1"
                               required value="{{ old('nome_mae', $nomeMae) }}">
                        <label class="flex items-center gap-1 text-xs text-gray-500 whitespace-nowrap cursor-pointer" id="label-mae-desconhecido">
                            <input type="checkbox" id="mae_desconhecido"
                                   onchange="document.getElementById('nome_mae').disabled = this.checked; document.getElementById('nome_mae').value = this.checked ? 'Desconhecido' : '';"
                                   class="rounded border-gray-300 text-blue-600">
                            Desconhece essa informação
                        </label>
                    </div>
                </div>

                {{-- Nome do pai --}}
                <div class="mb-4">
                    <label for="nome_pai" class="sa-label">Nome do pai</label>
                    <div class="flex gap-3 items-center">
                        <input id="nome_pai" name="nome_pai" type="text" class="sa-input flex-1"
                               value="{{ old('nome_pai') }}">
                        <label class="flex items-center gap-1 text-xs text-gray-500 whitespace-nowrap cursor-pointer">
                            <input type="checkbox" id="pai_desconhecido"
                                   onchange="document.getElementById('nome_pai').disabled = this.checked; document.getElementById('nome_pai').value = this.checked ? 'Desconhecido' : '';"
                                   class="rounded border-gray-300 text-blue-600">
                            Desconhece essa informação
                        </label>
                    </div>
                </div>

                {{-- Município de nascimento / Telefone --}}
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-6">
                    <div>
                        <label for="municipio_nascimento" class="sa-label">Município de nascimento</label>
                        <input id="municipio_nascimento" name="municipio_nascimento" type="text" class="sa-input"
                               placeholder="Cidade - UF" value="{{ old('municipio_nascimento', $municipioNasc) }}">
                    </div>
                    <div>
                        <label for="phone" class="sa-label">Telefone / WhatsApp</label>
                        <input id="phone" name="phone" type="text" class="sa-input"
                               placeholder="(00) 00000-0000" value="{{ old('phone') }}">
                    </div>
                </div>

                {{-- Ações --}}
                <div class="flex items-center justify-between gap-3 pt-4 border-t border-gray-100">
                    <a href="{{ route('triagem.cidadao') }}"
                       class="bg-gray-200 hover:bg-gray-300 text-gray-700 font-semibold py-2 px-5 rounded-lg transition text-sm">
                        Cancelar
                    </a>
                    <button type="submit" class="sa-btn-primary flex items-center gap-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/>
                        </svg>
                        Cadastrar na base local e adicionar à fila
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- Script de Formatação (Máscaras de Input) --}}
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const cpfInput = document.getElementById('cpf');
            const cnsInput = document.getElementById('cns');
            const phoneInput = document.getElementById('phone');

            // Formatação do CPF: 000.000.000-00
            function formatCPF(value) {
                let v = value.replace(/\D/g, '');
                if (v.length > 11) v = v.substring(0, 11);
                
                if (v.length > 9) {
                    return `${v.substring(0, 3)}.${v.substring(3, 6)}.${v.substring(6, 9)}-${v.substring(9)}`;
                } else if (v.length > 6) {
                    return `${v.substring(0, 3)}.${v.substring(3, 6)}.${v.substring(6)}`;
                } else if (v.length > 3) {
                    return `${v.substring(0, 3)}.${v.substring(3)}`;
                }
                return v;
            }

            // Formatação do CNS: 000 0000 0000 0000
            function formatCNS(value) {
                let v = value.replace(/\D/g, '');
                if (v.length > 15) v = v.substring(0, 15);
                
                let parts = [];
                if (v.length > 0) parts.push(v.substring(0, 3));
                if (v.length > 3) parts.push(v.substring(3, 7));
                if (v.length > 7) parts.push(v.substring(7, 11));
                if (v.length > 11) parts.push(v.substring(11, 15));
                
                return parts.join(' ');
            }

            // Formatação do Telefone: (00) 00000-0000 ou (00) 0000-0000
            function formatPhone(value) {
                let v = value.replace(/\D/g, '');
                if (v.length > 11) v = v.substring(0, 11);
                
                if (v.length > 6) {
                    if (v.length > 10) {
                        return `(${v.substring(0, 2)}) ${v.substring(2, 7)}-${v.substring(7)}`;
                    }
                    return `(${v.substring(0, 2)}) ${v.substring(2, 6)}-${v.substring(6)}`;
                } else if (v.length > 2) {
                    return `(${v.substring(0, 2)}) ${v.substring(2)}`;
                }
                return v;
            }

            // Aplicar máscaras nos eventos de digitação
            if (cpfInput) {
                cpfInput.value = formatCPF(cpfInput.value);
                cpfInput.addEventListener('input', function (e) {
                    e.target.value = formatCPF(e.target.value);
                });
            }

            if (cnsInput) {
                cnsInput.value = formatCNS(cnsInput.value);
                cnsInput.addEventListener('input', function (e) {
                    e.target.value = formatCNS(e.target.value);
                });
            }

            if (phoneInput) {
                phoneInput.value = formatPhone(phoneInput.value);
                phoneInput.addEventListener('input', function (e) {
                    e.target.value = formatPhone(e.target.value);
                });
            }
        });
    </script>
</x-app-layout>
