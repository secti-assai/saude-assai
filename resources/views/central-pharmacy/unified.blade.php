<x-app-layout>
    <x-slot name="header">
        <div class="sa-page-header">
            <h2 class="sa-page-title">Farmácia - Consulta e Dispensação</h2>
            <p class="sa-page-subtitle">Área unificada para consulta e liberação de medicações</p>
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

        <!-- Card de Busca -->
        <div class="sa-card">
            <div class="sa-card-header"><h3 class="sa-card-title">Consultar Cidadão</h3></div>
            <form method="POST" action="{{ route('central-pharmacy.unified.search') }}" class="flex items-end gap-4 p-4">
                @csrf
                <div class="flex-1 max-w-sm">
                    <label class="sa-label">Buscar por CPF</label>
                    <input name="cpf" class="sa-input" placeholder="000.000.000-00" required>
                </div>
                <button type="submit" class="sa-btn-primary">Consultar Gov.Assaí</button>
            </form>
        </div>

        @if(isset($info))
        @php
            $govStatus = $info['gov_lookup_status'] ?? 'UNAVAILABLE';
            $canProceedWithDispense = in_array($govStatus, ['FOUND', 'NOT_FOUND'], true);
        @endphp
        <!-- Card de Ação (Aparece após busca) -->
        <div class="sa-card border-t-4 {{ $info['success'] && $info['level'] >= 2 ? 'border-emerald-500' : 'border-amber-500' }}">
            <div class="p-6">
                <!-- Message Banner based on Level -->
                @if($govStatus !== 'FOUND' && $govStatus !== 'NOT_FOUND')
                    <div class="mb-6 p-4 rounded-lg bg-amber-50 text-amber-800 border border-amber-300 flex items-center">
                        <div>
                            <p class="font-bold text-lg">Nao foi possivel validar o Gov.Assai agora</p>
                            <p class="text-sm">{{ $info['gov_lookup_message'] ?? 'Tente novamente em instantes.' }}</p>
                        </div>
                    </div>
                @elseif($info['success'] && $info['level'] >= 2)
                    <div class="mb-6 p-4 rounded-lg bg-emerald-50 text-emerald-800 border bg-emerald-100 flex items-center">
                        <div>
                            <p class="font-bold text-lg">Cidadão Regularizado (Nível {{ $info['level'] }})</p>
                            <p class="text-sm border-l-4 border-emerald-500">A pessoa está regularizada com o Gov.Assaí e pode retirar a medicação.</p>
                        </div>
                    </div>
                @else
                    @if($info['pharmacy_lock_flag'])
                        <div class="mb-6 p-4 rounded-lg bg-red-50 text-red-800 border-red-300 border flex items-center">
                            <div>
                                <p class="font-bold text-lg">Dispensação Bloqueada - Nível Insuficiente</p>
                                <p class="text-sm">Este cidadão já foi notificado anteriormente e não atingiu o nível 2. Não pode retirar medicamentos.</p>
                            </div>
                        </div>

                        <form method="POST" action="{{ route('central-pharmacy.unified.no-dispense-blocked') }}" class="mb-6 grid grid-cols-1 md:grid-cols-2 gap-4 items-end bg-red-50 border border-red-200 rounded-lg p-4">
                            @csrf
                            <input type="hidden" name="cpf" value="{{ $info['normalized_cpf'] }}">

                            <div>
                                <label class="sa-label">Categoria não dispensada *</label>
                                @php($blockedCategory = old('dispense_category', 'MEDICACAO'))
                                <select name="dispense_category" class="sa-select" required>
                                    <option value="MEDICACAO" {{ $blockedCategory === 'MEDICACAO' ? 'selected' : '' }}>MEDICAÇÃO</option>
                                    <option value="LEITE" {{ $blockedCategory === 'LEITE' ? 'selected' : '' }}>LEITE</option>
                                    <option value="SUPLEMENTO" {{ $blockedCategory === 'SUPLEMENTO' ? 'selected' : '' }}>SUPLEMENTO</option>
                                </select>
                            </div>

                            <div class="flex justify-end">
                                <button type="submit" class="sa-btn-danger">Não Dispensar</button>
                            </div>
                        </form>
                    @else
                        <div class="mb-6 p-4 rounded-lg bg-amber-50 text-amber-800 border border-amber-300 flex items-center">
                            <div>
                                <p class="font-bold text-lg">A pessoa precisa se regularizar (Nível atual: {{ $info['level'] }})</p>
                                <p class="text-sm">Notifique que a próxima vez ela não conseguirá retirar sem o Nível 2 do Gov.Assaí.</p>
                            </div>
                        </div>
                    @endif
                @endif
                
                @if($canProceedWithDispense && (!$info['pharmacy_lock_flag'] || $info['level'] >= 2))
                <!-- Form de Cadastro / Dispensação (Se permitido) -->
                <form method="POST" action="{{ route('central-pharmacy.unified.dispense') }}" class="space-y-4">
                    @csrf
                    <input type="hidden" name="cpf" value="{{ $info['normalized_cpf'] }}">

                    <h4 class="text-md font-semibold text-gray-700 border-b pb-2">Informações do Cidadão</h4>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="sa-label">Nome Completo *</label>
                            <input
                                name="full_name"
                                class="sa-input"
                                value="{{ old('full_name', $info['citizen'] ? $info['citizen']->full_name : ($info['gov_data']['cidadao']['nome'] ?? '')) }}"
                                maxlength="255"
                                required
                                style="text-transform: uppercase;"
                                oninput="this.value = this.value.toUpperCase();"
                            >
                            @if($govStatus === 'NOT_FOUND')
                                <span class="text-xs text-amber-600">Não encontrado no Gov.Assaí. Insira os dados.</span>
                            @elseif($govStatus !== 'FOUND')
                                <span class="text-xs text-amber-600">Não foi possível confirmar os dados no Gov.Assaí agora.</span>
                            @endif
                        </div>
                        <div>
                            <label class="sa-label">Telefone p/ Contato *</label>
                            <input
                                name="phone"
                                class="sa-input"
                                value="{{ old('phone', $info['citizen'] ? $info['citizen']->phone : ($info['gov_data']['contato']['celular'] ?? '')) }}"
                                placeholder="(00) 00000-0000"
                                inputmode="numeric"
                                maxlength="15"
                                pattern="^\(\d{2}\)\s\d{4,5}-\d{4}$"
                                title="Use o formato (00) 00000-0000"
                                required
                                oninput="let v = this.value.replace(/\D/g, ''); if (v.length > 11) v = v.slice(0, 11); if (v.length > 10) { v = v.replace(/^(\d{2})(\d{5})(\d{0,4}).*/, '($1) $2-$3'); } else if (v.length > 6) { v = v.replace(/^(\d{2})(\d{4})(\d{0,4}).*/, '($1) $2-$3'); } else if (v.length > 2) { v = v.replace(/^(\d{2})(\d{0,5}).*/, '($1) $2'); } else if (v.length > 0) { v = v.replace(/^(\d{0,2}).*/, '($1'); } this.value = v;"
                            >
                            @if($govStatus === 'NOT_FOUND' || $govStatus === 'FOUND')
                                <span class="text-xs text-amber-600">Por favor, confirme ou atualize o telefone para notificar o cidadão.</span>
                            @endif
                        </div>
                    </div>

                    <h4 class="text-md font-semibold text-gray-700 border-b pb-2 mt-6">Dispensação</h4>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="sa-label">Categoria da dispensação *</label>
                            <select name="dispense_category" class="sa-select" required>
                                @php($selectedCategory = old('dispense_category', 'MEDICACAO'))
                                <option value="MEDICACAO" {{ $selectedCategory === 'MEDICACAO' ? 'selected' : '' }}>MEDICAÇÃO</option>
                                <option value="LEITE" {{ $selectedCategory === 'LEITE' ? 'selected' : '' }}>LEITE</option>
                                <option value="SUPLEMENTO" {{ $selectedCategory === 'SUPLEMENTO' ? 'selected' : '' }}>SUPLEMENTO</option>
                            </select>
                            <span class="text-xs text-gray-500">Seleção obrigatória para categorizar corretamente os relatórios da farmácia.</span>
                        </div>
                    </div>
                    
                    <div class="mt-6 flex justify-end">
                        @if($info['level'] >= 2)
                            <button type="submit" class="sa-btn-primary px-8 py-3 text-lg font-bold">OKAY - Finalizar Dispensação</button>
                        @else
                            <button type="submit" class="sa-btn-warning px-8 py-3 text-lg font-bold">NOTIFICAR E DISPENSAR</button>
                        @endif
                    </div>
                </form>
                @endif
            </div>
        </div>
        @endif

    </div>
</x-app-layout>
