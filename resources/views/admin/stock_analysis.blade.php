<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Análise de Estoque e Custos
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <header class="mb-8">
                <h1 class="text-3xl font-bold text-gray-900">Painel Analítico de Custos de Medicamentos</h1>
                <p class="text-gray-500 mt-2">Visão detalhada sobre entradas no estoque, valores investidos e ranking de fornecedores.</p>
            </header>

            <!-- Filtros -->
            <section class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 mb-8">
                <h2 class="text-lg font-semibold mb-4 text-gray-700">Filtros de Busca</h2>
                <form action="{{ route('admin.stock.analysis') }}" method="GET" class="grid grid-cols-1 md:grid-cols-5 gap-4">
                    
                    <div>
                        <label for="year" class="block text-sm font-medium text-gray-700 mb-1">Ano</label>
                        <select name="year" id="year" class="w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500 p-2 border">
                            <option value="">Todos os Anos</option>
                            @foreach($availableYears as $year)
                                <option value="{{ $year }}" {{ $selectedYear == $year ? 'selected' : '' }}>{{ $year }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label for="month" class="block text-sm font-medium text-gray-700 mb-1">Mês</label>
                        <select name="month" id="month" class="w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500 p-2 border">
                            <option value="">Todos os Meses</option>
                            @foreach(range(1, 12) as $m)
                                <option value="{{ $m }}" {{ $selectedMonth == $m ? 'selected' : '' }}>
                                    {{ DateTime::createFromFormat('!m', $m)->format('F') }} ({{ $m }})
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="md:col-span-2">
                        <label for="supplier" class="block text-sm font-medium text-gray-700 mb-1">Fornecedor</label>
                        <select name="supplier" id="supplier" class="w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500 p-2 border">
                            <option value="">Todos os Fornecedores</option>
                            @foreach($availableSuppliers as $supplier)
                                <option value="{{ $supplier }}" {{ $selectedSupplier == $supplier ? 'selected' : '' }}>{{ $supplier }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="md:col-span-2">
                        <label for="resource_origin" class="block text-sm font-medium text-gray-700 mb-1">Origem do Recurso</label>
                        <select name="resource_origin" id="resource_origin" class="w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500 p-2 border">
                            <option value="">Todos os Recursos</option>
                            <option value="gov" {{ $selectedResource == 'gov' ? 'selected' : '' }}>Recursos do Governo/Estado</option>
                            <option value="mun" {{ $selectedResource == 'mun' ? 'selected' : '' }}>Recursos Próprios (Município)</option>
                        </select>
                    </div>

                    <div class="md:col-span-2">
                        <label for="product_category" class="block text-sm font-medium text-gray-700 mb-1">Categoria de Produto</label>
                        <select name="product_category" id="product_category" class="w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500 p-2 border">
                            <option value="">Todas as Categorias</option>
                            <option value="fralda" {{ $selectedCategory == 'fralda' ? 'selected' : '' }}>Fraldas</option>
                            <option value="leite" {{ $selectedCategory == 'leite' ? 'selected' : '' }}>Leites / Fórmulas</option>
                            <option value="medicacao" {{ $selectedCategory == 'medicacao' ? 'selected' : '' }}>Medicações e Insumos</option>
                        </select>
                    </div>

                    <div class="md:col-span-1 lg:col-span-1">
                        <label for="medication_id" class="block text-sm font-medium text-gray-700 mb-1">Medicamento Específico</label>
                        <select name="medication_id" id="medication_id" class="w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500 p-2 border">
                            <option value="">Selecione...</option>
                            @foreach($availableMedications as $med)
                                <option value="{{ $med->id }}" {{ $selectedMedication == $med->id ? 'selected' : '' }}>{{ $med->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="md:col-span-5 flex justify-end items-end mt-2">
                        <a href="{{ route('admin.stock.analysis') }}" class="text-gray-500 hover:text-gray-700 mr-4 py-2 font-medium">Limpar Filtros</a>
                        <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-medium py-2 px-6 rounded-lg shadow transition">
                            Filtrar Dados
                        </button>
                    </div>
                </form>
            </section>

            <!-- KPIs -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 border-l-4 border-l-blue-500">
                    <p class="text-xs text-gray-500 font-medium uppercase tracking-wide">Custo Total Global</p>
                    <h3 class="text-2xl font-bold text-gray-900 mt-2">R$ {{ number_format($totalCost, 2, ',', '.') }}</h3>
                    <p class="text-xs text-gray-400 mt-1">Soma de todos os recursos</p>
                </div>
                
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 border-l-4 border-l-purple-500">
                    <p class="text-xs text-gray-500 font-medium uppercase tracking-wide">Recursos do Estado/Gov</p>
                    <h3 class="text-2xl font-bold text-purple-700 mt-2">R$ {{ number_format($totalGovCost, 2, ',', '.') }}</h3>
                    <p class="text-xs text-gray-400 mt-1">Consórcio, 17º Regional, etc.</p>
                </div>

                <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 border-l-4 border-l-indigo-500">
                    <p class="text-xs text-gray-500 font-medium uppercase tracking-wide">Recursos Próprios (Mun)</p>
                    <h3 class="text-2xl font-bold text-indigo-700 mt-2">R$ {{ number_format($totalMunCost, 2, ',', '.') }}</h3>
                    <p class="text-xs text-gray-400 mt-1">Compra direta pelo município</p>
                </div>

                <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 border-l-4 border-l-green-500">
                    <p class="text-xs text-gray-500 font-medium uppercase tracking-wide">Total de Unidades</p>
                    <h3 class="text-2xl font-bold text-gray-900 mt-2">{{ number_format($totalQuantity, 0, ',', '.') }} un.</h3>
                    <p class="text-xs text-gray-400 mt-1">Quantidade física adquirida</p>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-8">
                
                <!-- Tabela de Fornecedores -->
                <section class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                    <div class="p-6 border-b border-gray-100 bg-gray-50">
                        <h2 class="text-lg font-semibold text-gray-800">Ranking de Fornecedores</h2>
                        <p class="text-xs text-gray-500 mt-1">Agrupamento do valor pago para cada empresa.</p>
                    </div>
                    <div class="overflow-y-auto max-h-[400px]">
                        <table class="w-full text-left border-collapse">
                            <thead class="bg-gray-50 sticky top-0">
                                <tr>
                                    <th class="py-3 px-4 text-xs font-semibold text-gray-600 uppercase border-b">Fornecedor</th>
                                    <th class="py-3 px-4 text-xs font-semibold text-gray-600 uppercase border-b text-right">Custo Total (R$)</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                @forelse($supplierCosts as $sc)
                                <tr class="hover:bg-gray-50 transition">
                                    <td class="py-3 px-4 text-sm text-gray-800 font-medium">{{ $sc['supplier'] }}</td>
                                    <td class="py-3 px-4 text-sm text-gray-600 text-right">R$ {{ number_format($sc['total_cost'], 2, ',', '.') }}</td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="2" class="py-4 text-center text-gray-500 text-sm">Nenhum dado encontrado.</td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </section>

                <!-- Tabela de Medicamentos -->
                <section class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                    <div class="p-6 border-b border-gray-100 bg-gray-50">
                        <h2 class="text-lg font-semibold text-gray-800">Resumo de Medicamentos</h2>
                        <p class="text-xs text-gray-500 mt-1">Os produtos que mais consumiram recursos (Top 50).</p>
                    </div>
                    <div class="overflow-y-auto max-h-[400px]">
                        <table class="w-full text-left border-collapse">
                            <thead class="bg-gray-50 sticky top-0">
                                <tr>
                                    <th class="py-3 px-4 text-xs font-semibold text-gray-600 uppercase border-b">Medicamento</th>
                                    <th class="py-3 px-4 text-xs font-semibold text-gray-600 uppercase border-b text-right">Qtd</th>
                                    <th class="py-3 px-4 text-xs font-semibold text-gray-600 uppercase border-b text-right">Custo Total</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                @forelse($medicationCosts as $mc)
                                <tr class="hover:bg-gray-50 transition">
                                    <td class="py-3 px-4 text-sm text-gray-800 font-medium truncate max-w-[200px]" title="{{ $mc['name'] }}">{{ $mc['name'] }}</td>
                                    <td class="py-3 px-4 text-sm text-gray-500 text-right">{{ number_format($mc['total_quantity'], 0, '', '.') }}</td>
                                    <td class="py-3 px-4 text-sm text-gray-800 font-semibold text-right">R$ {{ number_format($mc['total_cost'], 2, ',', '.') }}</td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="3" class="py-4 text-center text-gray-500 text-sm">Nenhum dado encontrado.</td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </section>
            </div>

            <!-- Tabela Detalhada Individual -->
            <section class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                <div class="p-6 border-b border-gray-100 bg-gray-50 flex justify-between items-center">
                    <div>
                        <h2 class="text-lg font-semibold text-gray-800">Histórico Detalhado de Entradas</h2>
                        <p class="text-xs text-gray-500 mt-1">Todas as compras/notas individuais importadas do CSV, linha a linha.</p>
                    </div>
                    <span class="bg-blue-100 text-blue-800 text-xs font-semibold px-2.5 py-0.5 rounded border border-blue-200">
                        Total de Lançamentos: {{ $detailedItems->total() }}
                    </span>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse whitespace-nowrap">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="py-3 px-4 text-xs font-semibold text-gray-600 uppercase border-b">Data</th>
                                <th class="py-3 px-4 text-xs font-semibold text-gray-600 uppercase border-b">Medicamento</th>
                                <th class="py-3 px-4 text-xs font-semibold text-gray-600 uppercase border-b">Fornecedor</th>
                                <th class="py-3 px-4 text-xs font-semibold text-gray-600 uppercase border-b">Lote</th>
                                <th class="py-3 px-4 text-xs font-semibold text-gray-600 uppercase border-b text-right">Quantidade</th>
                                <th class="py-3 px-4 text-xs font-semibold text-gray-600 uppercase border-b text-right">Custo Unit.</th>
                                <th class="py-3 px-4 text-xs font-semibold text-gray-600 uppercase border-b text-right">Custo Total</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @forelse($detailedItems as $item)
                            <tr class="hover:bg-gray-50 transition">
                                <td class="py-3 px-4 text-sm text-gray-700">{{ $item->entry_date ? \Carbon\Carbon::parse($item->entry_date)->format('d/m/Y') : '-' }}</td>
                                <td class="py-3 px-4 text-sm font-medium text-gray-900 truncate max-w-[200px]" title="{{ $item->medication->name ?? 'N/A' }}">
                                    <div class="flex flex-col">
                                        <span>{{ $item->medication->name ?? 'N/A' }}</span>
                                        @php
                                            $n = mb_strtolower($item->medication->name ?? '');
                                            $isFralda = str_contains($n, 'fralda');
                                            $isLeite = str_contains($n, 'aptamil') || str_contains($n, 'neocate') || str_contains($n, 'alfamino') || str_contains($n, 'alfare') || str_contains($n, 'pediasure') || str_contains($n, 'enteral') || str_contains($n, 'milk') || str_contains($n, 'leite') || str_contains($n, 'formula') || str_contains($n, 'fórmula') || str_contains($n, 'soja') || str_contains($n, 'nutren');
                                        @endphp
                                        @if($isFralda)
                                            <span class="text-[10px] font-semibold text-orange-600 uppercase mt-0.5">Fralda</span>
                                        @elseif($isLeite)
                                            <span class="text-[10px] font-semibold text-pink-600 uppercase mt-0.5">Leite/Fórmula</span>
                                        @else
                                            <span class="text-[10px] font-semibold text-emerald-600 uppercase mt-0.5">Medicação/Insumo</span>
                                        @endif
                                    </div>
                                </td>
                                <td class="py-3 px-4 text-sm text-gray-600 truncate max-w-[250px]" title="{{ $item->supplier }}">
                                    <div class="flex flex-col">
                                        <span>{{ $item->supplier ?? 'Não Informado' }}</span>
                                        @php
                                            $s = mb_strtolower($item->supplier ?? '');
                                            $isGov = str_contains($s, 'parana') || str_contains($s, '17º regional') || str_contains($s, '17') || str_contains($s, 'consorcio') || str_contains($s, 'prefeitura');
                                        @endphp
                                        @if($isGov)
                                            <span class="text-[10px] font-semibold text-purple-600 uppercase mt-0.5">Estado/Governo</span>
                                        @else
                                            <span class="text-[10px] font-semibold text-indigo-600 uppercase mt-0.5">Recurso Próprio</span>
                                        @endif
                                    </div>
                                </td>
                                <td class="py-3 px-4 text-sm text-gray-500">{{ $item->batch ?? '-' }}</td>
                                <td class="py-3 px-4 text-sm text-gray-700 text-right">{{ number_format($item->quantity, 0, '', '.') }}</td>
                                <td class="py-3 px-4 text-sm text-gray-500 text-right">R$ {{ number_format($item->unit_cost, 2, ',', '.') }}</td>
                                <td class="py-3 px-4 text-sm text-gray-900 font-semibold text-right">R$ {{ number_format($item->total_cost, 2, ',', '.') }}</td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="7" class="py-6 text-center text-gray-500 text-sm">Nenhum lançamento encontrado para os filtros atuais.</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                
                @if($detailedItems->hasPages())
                <div class="p-4 border-t border-gray-100 bg-white">
                    {{ $detailedItems->links() }}
                </div>
                @endif
            </section>

        </div>
    </div>
</x-app-layout>
