<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center gap-4">
            <a href="{{ route('admin.schedules.index') }}" class="p-2 -ml-2 text-gray-400 hover:text-gray-600 rounded-lg hover:bg-gray-100 transition">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
            </a>
            <div>
                <h2 class="text-xl font-bold text-gray-900 leading-tight">
                    Editar Regra de Horário
                </h2>
                <p class="text-sm text-gray-500 mt-1">Atualize as configurações de horário e capacidade.</p>
            </div>
        </div>
    </x-slot>

    <div class="max-w-4xl">
        <form action="{{ route('admin.schedules.update', $schedule) }}" method="POST" class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden p-6" x-data="{ clinic: '{{ old('clinic_type', $schedule->clinic_type) }}' }">
            @csrf
            @method('PUT')

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                <!-- Clínica -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Clínica</label>
                    <select name="clinic_type" x-model="clinic" class="w-full rounded-lg border-gray-300 focus:border-blue-500 focus:ring-blue-500 text-sm">
                        <option value="{{ \App\Models\WomenClinicAppointment::CLINIC_WOMEN }}">Clínica da Mulher</option>
                        <option value="{{ \App\Models\WomenClinicAppointment::CLINIC_POLICLINICA }}">Policlínica</option>
                    </select>
                    @error('clinic_type') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>

                <!-- Especialidade -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Especialidade</label>
                    <select name="specialty" class="w-full rounded-lg border-gray-300 focus:border-blue-500 focus:ring-blue-500 text-sm">
                        <!-- Women Clinic -->
                        <template x-if="clinic === '{{ \App\Models\WomenClinicAppointment::CLINIC_WOMEN }}'">
                            <>
                                <option value="{{ \App\Models\WomenClinicAppointment::SPECIALTY_CARDIOLOGIA }}" {{ old('specialty', $schedule->specialty) == \App\Models\WomenClinicAppointment::SPECIALTY_CARDIOLOGIA ? 'selected' : '' }}>Cardiologia</option>
                                <option value="{{ \App\Models\WomenClinicAppointment::SPECIALTY_ORTOPEDIA }}" {{ old('specialty', $schedule->specialty) == \App\Models\WomenClinicAppointment::SPECIALTY_ORTOPEDIA ? 'selected' : '' }}>Ortopedia</option>
                                <option value="{{ \App\Models\WomenClinicAppointment::SPECIALTY_PSIQUIATRIA }}" {{ old('specialty', $schedule->specialty) == \App\Models\WomenClinicAppointment::SPECIALTY_PSIQUIATRIA ? 'selected' : '' }}>Psiquiatria</option>
                            </>
                        </template>
                        <!-- Policlinica -->
                        <template x-if="clinic === '{{ \App\Models\WomenClinicAppointment::CLINIC_POLICLINICA }}'">
                            <>
                                <option value="{{ \App\Models\WomenClinicAppointment::SPECIALTY_ODONTOLOGIA }}" {{ old('specialty', $schedule->specialty) == \App\Models\WomenClinicAppointment::SPECIALTY_ODONTOLOGIA ? 'selected' : '' }}>Odontologia</option>
                                <option value="{{ \App\Models\WomenClinicAppointment::SPECIALTY_FISIOTERAPIA }}" {{ old('specialty', $schedule->specialty) == \App\Models\WomenClinicAppointment::SPECIALTY_FISIOTERAPIA ? 'selected' : '' }}>Fisioterapia</option>
                            </>
                        </template>
                    </select>
                    @error('specialty') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>

                <!-- Dia da Semana -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Dia da Semana</label>
                    <select name="day_of_week" class="w-full rounded-lg border-gray-300 focus:border-blue-500 focus:ring-blue-500 text-sm">
                        <option value="1" {{ old('day_of_week', $schedule->day_of_week) == '1' ? 'selected' : '' }}>Segunda-feira</option>
                        <option value="2" {{ old('day_of_week', $schedule->day_of_week) == '2' ? 'selected' : '' }}>Terça-feira</option>
                        <option value="3" {{ old('day_of_week', $schedule->day_of_week) == '3' ? 'selected' : '' }}>Quarta-feira</option>
                        <option value="4" {{ old('day_of_week', $schedule->day_of_week) == '4' ? 'selected' : '' }}>Quinta-feira</option>
                        <option value="5" {{ old('day_of_week', $schedule->day_of_week) == '5' ? 'selected' : '' }}>Sexta-feira</option>
                        <option value="6" {{ old('day_of_week', $schedule->day_of_week) == '6' ? 'selected' : '' }}>Sábado</option>
                        <option value="0" {{ old('day_of_week', $schedule->day_of_week) == '0' ? 'selected' : '' }}>Domingo</option>
                    </select>
                    @error('day_of_week') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>

                <!-- Horário -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Horário</label>
                    <input type="time" name="time" value="{{ old('time', substr($schedule->time, 0, 5)) }}" required class="w-full rounded-lg border-gray-300 focus:border-blue-500 focus:ring-blue-500 text-sm">
                    @error('time') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>

                <!-- Capacidade -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Capacidade de Vagas</label>
                    <input type="number" name="capacity" min="0" value="{{ old('capacity', $schedule->capacity) }}" required class="w-full rounded-lg border-gray-300 focus:border-blue-500 focus:ring-blue-500 text-sm">
                    @error('capacity') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>

                <!-- Semanas do Mês -->
                <div class="md:col-span-2 border-t pt-4 mt-2">
                    <label class="block text-sm font-medium text-gray-700 mb-3">Semanas do Mês</label>
                    <p class="text-xs text-gray-500 mb-3">Selecione em quais semanas do mês esta regra se aplica (importante para meses com 5 semanas).</p>
                    <div class="flex flex-wrap gap-4">
                        @foreach([1, 2, 3, 4, 5] as $week)
                            <label class="inline-flex items-center">
                                <input type="checkbox" name="weeks_of_month[]" value="{{ $week }}" 
                                       class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50"
                                       {{ in_array($week, old('weeks_of_month', $schedule->weeks_of_month)) ? 'checked' : '' }}>
                                <span class="ml-2 text-sm text-gray-700">{{ $week }}ª Semana</span>
                            </label>
                        @endforeach
                    </div>
                    @error('weeks_of_month') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>

                <!-- Status -->
                <div class="md:col-span-2 border-t pt-4">
                    <label class="inline-flex items-center">
                        <input type="checkbox" name="is_active" value="1" 
                               class="rounded border-gray-300 text-green-600 shadow-sm focus:border-green-300 focus:ring focus:ring-green-200 focus:ring-opacity-50"
                               {{ old('is_active', $schedule->is_active) ? 'checked' : '' }}>
                        <span class="ml-2 text-sm text-gray-900 font-medium">Regra Ativa</span>
                    </label>
                    <p class="text-xs text-gray-500 mt-1 ml-6">Se desmarcado, esta regra não gerará vagas (não permitindo agendamento para novos horários).</p>
                </div>
            </div>

            <div class="flex justify-end pt-4 border-t border-gray-100">
                <button type="submit" class="btn-primary">
                    Atualizar Regra
                </button>
            </div>
        </form>
    </div>
</x-app-layout>
