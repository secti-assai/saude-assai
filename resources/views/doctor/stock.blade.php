<x-app-layout>
    <x-slot name="header">
        <div class="sa-page-header">
            <div>
                <h2 class="sa-page-title">Consulta de Medicamentos</h2>
                <p class="sa-page-subtitle">Consulte a disponibilidade de medicamentos na Farmácia Central antes de prescrever</p>
            </div>
        </div>
    </x-slot>

    <div class="space-y-6">

        {{-- Card de Busca --}}
        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
            <div class="p-6">
                <form method="GET" action="{{ route('doctor.stock.index') }}" id="form-busca">
                    <div class="flex flex-col sm:flex-row gap-4 items-end">
                        <div class="flex-1">
                            <label for="q" class="sa-label">Nome do Medicamento</label>
                            <div class="relative">
                                <input type="text" name="q" id="q" value="{{ $query ?? '' }}"
                                       placeholder="Digite o nome do medicamento (ex: Dipirona, Amoxicilina)..."
                                       class="sa-input w-full pr-10"
                                       autofocus>
                                @if (!empty($query))
                                    <a href="{{ route('doctor.stock.index') }}" class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                                        </svg>
                                    </a>
                                @endif
                            </div>
                        </div>
                        <button type="submit" class="sa-btn-primary flex items-center gap-2">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z"/>
                            </svg>
                            Buscar medicamento
                        </button>
                    </div>
                </form>
            </div>
        </div>

        {{-- Resultados --}}
        @if ($medications !== null)
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    @if ($medications->count() > 0)
                        <div class="flex items-center justify-between mb-4">
                            <p class="text-sm text-gray-500">
                                <span class="font-semibold text-gray-700">{{ $medications->total() }}</span> medicamento(s) encontrado(s)
                                para "<span class="font-medium text-gray-700">{{ $query }}</span>"
                            </p>
                        </div>

                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Medicamento</th>
                                        <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Apresentação</th>
                                        <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Concentração</th>
                                        <th scope="col" class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">REMUME</th>
                                        <th scope="col" class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Saldo Disponível</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    @foreach ($medications as $med)
                                        <tr class="hover:bg-gray-50 transition-colors">
                                            <td class="px-4 py-3 whitespace-nowrap text-sm font-medium text-gray-900">
                                                {{ $med->name }}
                                            </td>
                                            <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-500">
                                                {{ $med->presentation ?? '—' }}
                                            </td>
                                            <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-500">
                                                {{ $med->concentration ?? '—' }}
                                            </td>
                                            <td class="px-4 py-3 whitespace-nowrap text-sm text-center">
                                                @if ($med->is_remume)
                                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">Sim</span>
                                                @else
                                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800">Não</span>
                                                @endif
                                            </td>
                                            <td class="px-4 py-3 whitespace-nowrap text-sm text-right">
                                                @if ($med->stock_total > 0)
                                                    <span class="font-bold text-green-600">{{ number_format($med->stock_total, 0, ',', '.') }} un</span>
                                                @else
                                                    <span class="font-bold text-red-500">Sem Estoque</span>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        {{-- Paginação --}}
                        @if ($medications->hasPages())
                            <div class="mt-4 border-t border-gray-100 pt-4">
                                {{ $medications->links() }}
                            </div>
                        @endif
                    @else
                        <div class="text-center py-8">
                            <svg class="mx-auto h-12 w-12 text-gray-300" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z"/>
                            </svg>
                            <p class="mt-2 text-sm text-gray-500">Nenhum medicamento encontrado para "<span class="font-medium text-gray-700">{{ $query }}</span>".</p>
                            <p class="mt-1 text-xs text-gray-400">Tente buscar por outro nome ou parte do nome.</p>
                        </div>
                    @endif
                </div>
            </div>
        @else
            {{-- Estado inicial (sem busca) --}}
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-8 text-center">
                    <svg class="mx-auto h-12 w-12 text-gray-300" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9.75 3.104v5.714a2.25 2.25 0 0 1-.659 1.591L5 14.5M9.75 3.104c-.251.023-.501.05-.75.082m.75-.082a24.301 24.301 0 0 1 4.5 0m0 0v5.714c0 .597.237 1.17.659 1.591L19.8 15.3M14.25 3.104c.251.023.501.05.75.082M19.8 15.3l-1.57.393A9.065 9.065 0 0 1 12 15a9.065 9.065 0 0 0-6.23.693L5 14.5m14.8.8 1.402 1.402c1.232 1.232.65 3.318-1.067 3.611A48.309 48.309 0 0 1 12 21c-2.773 0-5.491-.235-8.135-.687-1.718-.293-2.3-2.379-1.067-3.61L5 14.5"/>
                    </svg>
                    <p class="mt-3 text-sm text-gray-500">
                        Utilize o campo acima para buscar um medicamento na base de estoque da Farmácia Central.
                    </p>
                    <p class="mt-1 text-xs text-gray-400">
                        Os dados de estoque são atualizados periodicamente pela equipe administrativa via importação CSV.
                    </p>
                </div>
            </div>
        @endif

    </div>
</x-app-layout>
