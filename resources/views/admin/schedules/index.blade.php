<x-layouts.app>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-xl font-bold text-gray-900 leading-tight">
                    Gestão de Horários e Escalas
                </h2>
                <p class="text-sm text-gray-500 mt-1">Gerencie os horários e vagas para as clínicas e especialidades.</p>
            </div>
            <a href="{{ route('admin.schedules.create') }}" class="btn-primary flex items-center gap-2">
                @include('layouts.partials.nav-icon', ['icon' => 'plus'])
                Nova Regra
            </a>
        </div>
    </x-slot>

    @if (session('status'))
        <div class="mb-6 p-4 bg-green-50 border border-green-200 text-green-700 rounded-lg flex items-center gap-3">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
            {{ session('status') }}
        </div>
    @endif

    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm text-left">
                <thead class="text-xs text-gray-500 uppercase bg-gray-50 border-b border-gray-200">
                    <tr>
                        <th class="px-6 py-4 font-semibold">Clínica</th>
                        <th class="px-6 py-4 font-semibold">Especialidade</th>
                        <th class="px-6 py-4 font-semibold">Dia da Semana</th>
                        <th class="px-6 py-4 font-semibold">Semanas do Mês</th>
                        <th class="px-6 py-4 font-semibold text-center">Horário</th>
                        <th class="px-6 py-4 font-semibold text-center">Capacidade</th>
                        <th class="px-6 py-4 font-semibold text-center">Status</th>
                        <th class="px-6 py-4 font-semibold text-right">Ações</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($rules as $rule)
                        @php
                            $days = ['Domingo', 'Segunda-feira', 'Terça-feira', 'Quarta-feira', 'Quinta-feira', 'Sexta-feira', 'Sábado'];
                            $dayName = $days[$rule->day_of_week] ?? $rule->day_of_week;
                            $weeks = collect($rule->weeks_of_month)->sort()->map(fn($w) => $w . 'ª')->join(', ');
                        @endphp
                        <tr class="hover:bg-gray-50/50 transition">
                            <td class="px-6 py-4 font-medium text-gray-900">
                                {{ $rule->clinic_type === \App\Models\WomenClinicAppointment::CLINIC_WOMEN ? 'Clínica da Mulher' : 'Policlínica' }}
                            </td>
                            <td class="px-6 py-4 text-gray-600">
                                {{ \App\Models\WomenClinicAppointment::specialtyLabel($rule->specialty) }}
                            </td>
                            <td class="px-6 py-4 text-gray-600 font-medium">
                                {{ $dayName }}
                            </td>
                            <td class="px-6 py-4 text-gray-500 text-xs">
                                {{ count($rule->weeks_of_month) === 5 ? 'Todas' : $weeks }}
                            </td>
                            <td class="px-6 py-4 text-center font-bold text-gray-700">
                                {{ substr($rule->time, 0, 5) }}
                            </td>
                            <td class="px-6 py-4 text-center">
                                <span class="inline-flex items-center justify-center w-8 h-8 rounded-full bg-blue-50 text-blue-700 font-bold text-xs">
                                    {{ $rule->capacity }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-center">
                                @if($rule->is_active)
                                    <span class="inline-flex items-center px-2 py-1 rounded-md text-xs font-medium bg-green-100 text-green-700">
                                        Ativo
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-2 py-1 rounded-md text-xs font-medium bg-red-100 text-red-700">
                                        Inativo
                                    </span>
                                @endif
                            </td>
                            <td class="px-6 py-4 text-right">
                                <div class="flex items-center justify-end gap-2">
                                    <a href="{{ route('admin.schedules.edit', $rule) }}" class="p-2 text-gray-400 hover:text-blue-600 rounded-lg hover:bg-blue-50 transition" title="Editar">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"></path></svg>
                                    </a>
                                    <form action="{{ route('admin.schedules.destroy', $rule) }}" method="POST" onsubmit="return confirm('Tem certeza que deseja remover esta regra?');" class="inline">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="p-2 text-gray-400 hover:text-red-600 rounded-lg hover:bg-red-50 transition" title="Remover">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-6 py-12 text-center text-gray-500">
                                Nenhuma regra de horário configurada. As agendas ficarão indisponíveis.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</x-layouts.app>
