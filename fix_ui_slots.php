<?php

$file = 'resources/views/women-clinic/agendador.blade.php';
$content = file_get_contents($file);

// Let's completely rewrite the slots section and the JS logic for generating these slots
$searchOuter = '/<div class="md:col-span-3 mt-4 mb-4" id="agenda-slots-container" style="display: none;">(.*?)<\/script>/s';

$replacementOuter = <<<HTML
<div class="md:col-span-3 mt-6 mb-4 bg-white border border-gray-200 rounded shadow-sm" id="agenda-slots-container" style="display: none;">
                        <div class="bg-gray-50 border-b border-gray-200 p-4 font-semibold text-gray-700 flex justify-between items-center">
                            <span>Agenda do Profissional/Serviço</span>
                            <span class="text-sm font-normal text-gray-500">Duração: <span id="slot-duration-text"></span></span>
                        </div>
                        <div id="agenda-slots-list" class="divide-y divide-gray-100 max-h-[600px] overflow-y-auto w-full">
                            <!-- Slots serão renderizados aqui -->
                        </div>
                        <div class="bg-gray-100 p-2 text-center text-xs text-gray-500 font-semibold border-t border-gray-200">
                            Fim da agenda.
                        </div>
                    </div>

                    <div class="md:col-span-3 flex justify-end space-x-2 mt-4">
                        <button type="button" formnovalidate onclick="window.location.href='{{ route('clinic-scheduler.index') }}'" class="bg-gray-300 hover:bg-gray-400 text-gray-800 font-bold py-2 px-4 rounded">Cancelar</button>
                        <!-- Submit via form nativo com JS quando clicar no Slot -->
                        <button type="submit" class="hidden" id="btn-agendar-submit-hidden">Agendar</button>
                    </div>
                </form>
            @endif
        </div>
    </div>

    <style>
        .pec-slot-row { display: flex; min-height: 48px; }
        .pec-time-col { width: 60px; flex-shrink: 0; padding: 12px 8px; font-size: 13px; color: #4B5563; font-weight: 600; text-align: center; border-right: 1px solid #F3F4F6; background: #FAFAFA; }
        .pec-content-col { flex-grow: 1; padding: 0; margin: 0; }
        .pec-slot-box { 
            width: 100%; height: 100%; min-height: 48px; display: flex; align-items: center; px-4; transition: all 0.2s; 
            background-color: #F8FAFC; border: 1px solid transparent; 
        }
        .pec-slot-free { 
            background-color: #FFFFFF; cursor: pointer; color: #1D4ED8; font-weight: 500; font-size: 14px; 
            border-top: 1px solid #EFF6FF; border-bottom: 1px solid #EFF6FF; justify-content: center;
        }
        .pec-slot-free:hover { background-color: #EFF6FF; border-color: #BFDBFE; box-shadow: inset 0 0 0 1px #93C5FD; }
        .pec-slot-busy { 
            background-color: #F8FAFC; color: #6B7280; font-size: 13px; padding-left: 1rem;
        }
        .pec-slot-busy-internal {
            background-color: #FFFFFF; color: #374151; font-weight: 500; padding: 0.5rem 1rem;
            border: 1px solid #E5E7EB; border-radius: 4px; display: flex; justify-content: space-between; align-items: center; width: 98%; margin: 4px auto; box-shadow: 0 1px 2px rgba(0,0,0,0.05);
        }
    </style>

    <script>
        (function() {
            const clinicSelect = document.getElementById('scheduler-clinic-type');
            const specialtySelect = document.querySelector('select[name="specialty"]');
            const hiddenInput = document.getElementById('scheduled-for-hidden');
            
            if (!clinicSelect || !specialtySelect) {
                return;
            }

            const specialtiesByClinic = @json(\$specialtiesByClinic ?? []);
            const oldSpecialty = @json(old('specialty'));

            const populateSpecialtyOptions = (clinicType) => {
                const specialties = specialtiesByClinic[clinicType] || {};
                const availableEntries = Object.entries(specialties);

                specialtySelect.innerHTML = '';

                const emptyOption = document.createElement('option');
                emptyOption.value = '';
                emptyOption.textContent = 'Selecione';
                specialtySelect.appendChild(emptyOption);

                availableEntries.forEach(([value, label]) => {
                    const option = document.createElement('option');
                    option.value = value;
                    option.textContent = label;

                    if (oldSpecialty && oldSpecialty === value) {
                        option.selected = true;
                    }

                    specialtySelect.appendChild(option);
                });

                const hasSelectedSpecialty = Array.from(specialtySelect.options).some((option) => option.selected && option.value !== '');
                if (!hasSelectedSpecialty) {
                    specialtySelect.value = '';
                }
            };

            clinicSelect.addEventListener('change', () => {
                specialtySelect.dataset.userChanged = '1';
                populateSpecialtyOptions(clinicSelect.value);
                loadSlots();
            });
            
            specialtySelect.addEventListener('change', loadSlots);
            document.getElementById('scheduler-date').addEventListener('change', loadSlots);

            function loadSlots() {
                const date = document.getElementById('scheduler-date').value;
                const specialty = specialtySelect.value;
                const clinicType = clinicSelect.value;
                const hiddenInput = document.getElementById('scheduled-for-hidden');
                const container = document.getElementById('agenda-slots-container');
                const list = document.getElementById('agenda-slots-list');
                
                if (!date || !specialty) {
                    container.style.display = 'none';
                    return;
                }

                container.style.display = 'block';
                list.innerHTML = '<div class="p-8 text-center text-gray-500"><i class="fas fa-spinner fa-spin mr-2"></i> Carregando agenda...</div>';

                document.getElementById('slot-duration-text').textContent = "Buscando...";

                fetch(`/agendador/slots?date=\${date}&specialty=\${specialty}&clinic_type=\${clinicType}`)
                    .then(r => r.json())
                    .then(slots => {
                        if (slots.error) {
                            list.innerHTML = `<div class="p-6 text-center text-red-500">\${slots.error}</div>`;
                            return;
                        }

                        if (slots.length === 0) {
                            list.innerHTML = '<div class="p-6 text-center text-gray-500">Nenhum horário configurado para este dia.</div>';
                            return;
                        }

                        document.getElementById('slot-duration-text').textContent = "Padrão de minutos configurado";

                        list.innerHTML = '';
                        slots.forEach(slot => {
                            const row = document.createElement('div');
                            row.className = 'pec-slot-row';
                            
                            const timeCol = document.createElement('div');
                            timeCol.className = 'pec-time-col';
                            timeCol.textContent = slot.time;
                            row.appendChild(timeCol);

                            const contentCol = document.createElement('div');
                            contentCol.className = 'pec-content-col';

                            // Identificando slot ocupado via gov.br check / cidadão name
                            if (!slot.available) {
                                // Busy
                                const isCitizenNamed = slot.patient_name && slot.patient_name !== 'Cidadão';
                                if (isCitizenNamed) {
                                    // Internal booked
                                    contentCol.innerHTML = `
                                        <div class="pec-slot-busy-internal">
                                            <div class="flex items-center gap-2">
                                                <i class="fas fa-user-circle text-blue-500"></i>
                                                <span class="font-bold">\${slot.patient_name}</span>
                                                <span class="text-gray-500 text-xs ml-2"><i class="fas fa-phone mr-1"></i>\${slot.patient_phone || 'Sem número'}</span>
                                            </div>
                                            <div class="flex gap-3 text-gray-400 hover:text-gray-600 transition-colors cursor-pointer">
                                                <i class="fas fa-user-edit"></i>
                                                <i class="fas fa-trash-alt"></i>
                                            </div>
                                        </div>
                                    `;
                                    // wrapper
                                    const wrap = document.createElement('div');
                                    wrap.className = 'pec-slot-box bg-gray-50 p-0';
                                    wrap.appendChild(contentCol.children[0]);
                                    contentCol.innerHTML = '';
                                    contentCol.appendChild(wrap);
                                } else {
                                    // Conecte SUS
                                    contentCol.innerHTML = `
                                        <div class="pec-slot-box pec-slot-busy text-blue-400">
                                            <div class="flex items-center gap-2">
                                                <i class="far fa-calendar-check pt-1"></i>
                                                <span>Reservado | Ocupado neste horário</span>
                                            </div>
                                        </div>
                                    `;
                                }
                            } else {
                                // Free
                                contentCol.innerHTML = `
                                    <div class="pec-slot-box pec-slot-free">
                                        <div class="flex items-center gap-2 w-full justify-start pl-8">
                                            <i class="fas fa-plus font-bold text-lg"></i>
                                            <span style="font-size: 15px;">Adicionar agendamento</span>
                                        </div>
                                    </div>
                                `;
                                contentCol.querySelector('.pec-slot-free').onclick = () => {
                                    hiddenInput.value = `\${date} \${slot.time}:00`;
                                    
                                    // Confirmação para evitar clique acidental
                                    if(confirm(`Confirmar agendamento para \${slot.time}?`)) {
                                        document.getElementById('btn-agendar-submit-hidden').click();
                                    }
                                };
                            }
                            
                            row.appendChild(contentCol);
                            list.appendChild(row);
                        });
                    })
                    .catch(err => {
                        list.innerHTML = '<div class="p-4 text-center text-red-500">Erro ao carregar horários. Tente novamente.</div>';
                    });
            }

            // Init call
            populateSpecialtyOptions(clinicSelect.value);
            if (document.getElementById('scheduler-date').value && specialtySelect.value) {
                loadSlots();
            }

        })();
    </script>
HTML;

$content = preg_replace($searchOuter, $replacementOuter, $content);
file_put_contents($file, $content);

echo "Success!\n";
