import React, { useState } from 'react';
import { useForm, usePage } from '@inertiajs/react';
import AuthLayout from '@/Layouts/AuthLayout';
import VitalSignAlert from '@/Components/Health/VitalSignAlert';
import { PageProps } from '@/types';

// Mocked data for patient
const mockPatient = {
  name: "Carlos Antunes",
  age: 58,
  cns: "705.2323.4444.1111",
  allergies: ["Penicilina", "Frutos do Mar"]
};

export default function RecordForm() {
  const { auth } = usePage<PageProps>().props;
  
  const { data, setData, post, processing, errors } = useForm({
    pa_systolic: '120',
    pa_diastolic: '80',
    temperature: '36.5',
    spO2: '98',
    objective: '',
    assessment: '',
    cid: '',
    prescription: ''
  });

  // Regras de Alerta em Tempo Real (M7 - Documento Técnico)
  const systolic = Number(data.pa_systolic);
  const diastolic = Number(data.pa_diastolic);
  const spO2 = Number(data.spO2);

  const isEmergencyPA = systolic >= 180 || diastolic >= 120;
  const isEmergencyO2 = spO2 < 90 && spO2 > 0;
  
  const submitRecord = (e: React.FormEvent) => {
    e.preventDefault();
    // post('/hospital/records');
    alert('FichaAtendimentoIndividual enviada via LEDI APS!');
  };

  return (
    <AuthLayout header="Prontuário Hospitalar (Atendimento)">
      {/* Dados fixos do Paciente / Barra Lateral Embutida */}
      <div className="bg-white shadow rounded-lg p-4 mb-6 flex justify-between items-center border-l-4 border-manchester-red">
        <div>
          <h2 className="text-xl font-bold text-gray-800">{mockPatient.name} <span className="text-sm font-normal text-gray-500">| {mockPatient.age} anos</span></h2>
          <p className="text-sm font-mono text-gray-500">CNS: {mockPatient.cns}</p>
        </div>
        <div className="text-right">
          <span className="text-xs uppercase tracking-wider font-bold text-red-600 block">Alergias</span>
          <p className="text-sm text-gray-800">{mockPatient.allergies.join(', ')}</p>
        </div>
      </div>

      <form onSubmit={submitRecord} className="grid grid-cols-1 lg:grid-cols-2 gap-8">
        
        {/* LADO ESQUERDO: TRIAGEM E SINAIS VITAIS */}
        <section className="bg-white p-6 rounded-lg shadow-sm border border-gray-100 flex flex-col gap-6">
          <h3 className="text-lg font-bold border-b pb-2 text-assai-primary">Sinais Vitais (Triagem)</h3>
          
          {isEmergencyPA && (
             <VitalSignAlert level="red" message="ALERTA MÁXIMO: Pressão Arterial > 180/120 (Crise Hipertensiva)" />
          )}

          {isEmergencyO2 && (
             <VitalSignAlert level="orange" message="ALERTA: Saturação O2 abaixo de 90%. Hipóxia Secundária." />
          )}

          <div className="grid grid-cols-2 gap-4">
            <div>
              <label htmlFor="sys" className="block text-sm font-medium text-gray-700">P.A. Sistólica (mmHg)</label>
              <input
                id="sys" type="number"
                value={data.pa_systolic}
                onChange={e => setData('pa_systolic', e.target.value)}
                className={`mt-1 block w-full rounded-md shadow-sm focus:ring-assai-primary ${isEmergencyPA ? 'border-red-500 bg-red-50 ring-red-500' : 'border-gray-300'}`}
              />
            </div>
            <div>
              <label htmlFor="dia" className="block text-sm font-medium text-gray-700">P.A. Diastólica (mmHg)</label>
              <input
                id="dia" type="number"
                value={data.pa_diastolic}
                onChange={e => setData('pa_diastolic', e.target.value)}
                className={`mt-1 block w-full rounded-md shadow-sm focus:ring-assai-primary ${isEmergencyPA ? 'border-red-500 bg-red-50 ring-red-500' : 'border-gray-300'}`}
              />
            </div>

            <div>
              <label htmlFor="temp" className="block text-sm font-medium text-gray-700">Temperatura (°C)</label>
              <input
                id="temp" type="number" step="0.1"
                value={data.temperature}
                onChange={e => setData('temperature', e.target.value)}
                className="mt-1 block w-full rounded-md shadow-sm border-gray-300 focus:ring-assai-primary"
              />
            </div>
            <div>
              <label htmlFor="o2" className="block text-sm font-medium text-gray-700">SpO2 (%)</label>
              <input
                id="o2" type="number"
                value={data.spO2}
                onChange={e => setData('spO2', e.target.value)}
                className={`mt-1 block w-full rounded-md shadow-sm focus:ring-assai-primary ${isEmergencyO2 ? 'border-orange-500 bg-orange-50' : 'border-gray-300'}`}
              />
            </div>
          </div>
        </section>

        {/* LADO DIREITO: AVALIAÇÃO MÉDICA E PRESCRIÇÃO */}
        <section className="bg-white p-6 rounded-lg shadow-sm border border-gray-100 flex flex-col gap-6">
          <h3 className="text-lg font-bold border-b pb-2 text-assai-primary">Conduta / SOAP</h3>

          <div>
             <label htmlFor="obj" className="block text-sm font-medium text-gray-700">Exame Objetivo (O)</label>
             <textarea 
               id="obj" rows={3}
               value={data.objective}
               onChange={e => setData('objective', e.target.value)}
               className="mt-1 block w-full rounded-md shadow-sm border-gray-300 focus:ring-assai-primary"
               placeholder="Ausculta, exames perceptíveis..."
             />
          </div>

          <div>
             <label htmlFor="cid" className="block text-sm font-medium text-gray-700">CID-10 Principal</label>
             <input 
               id="cid" type="text"
               value={data.cid}
               onChange={e => setData('cid', e.target.value)}
               className="mt-1 block w-full rounded-md shadow-sm border-gray-300 focus:ring-assai-primary"
               placeholder="Ex: I10 - Hipertensão essencial"
             />
          </div>

          <div>
             <label htmlFor="pres" className="block text-sm font-medium text-gray-700">Prescrição e Conduta (REMUME)</label>
             <textarea 
               id="pres" rows={4}
               value={data.prescription}
               onChange={e => setData('prescription', e.target.value)}
               className="mt-1 block w-full rounded-md shadow-sm border-gray-300 focus:ring-assai-primary"
               placeholder="Descreva medicamentos, dosagem e tipo de saída."
             />
          </div>

          <div className="pt-4 border-t border-gray-100 flex justify-end gap-3">
             <button type="button" className="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-assai-primary">
                Gravar Rascunho
             </button>
             <button 
                type="submit" 
                className="px-4 py-2 flex items-center justify-center text-sm font-medium text-white bg-assai-primary border border-transparent rounded-md hover:bg-assai-secondary focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-assai-primary"
             >
                {/* Ícone de Certificado Digital ICP-Brasil Mockado */}
                <svg className="mr-2 -ml-1 h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                </svg>
                Assinar e Encerrar (Gov.BR)
             </button>
          </div>
        </section>
      </form>
    </AuthLayout>
  );
}
