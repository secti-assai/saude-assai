import React from 'react';

export default function DoctorDashboard() {
  return (
    <div className="space-y-6">
      <div className="flex bg-white p-6 rounded-lg shadow-sm border-l-4 border-manchester-blue items-center justify-between">
        <div>
          <h2 className="text-xl font-bold text-gray-800">Hospital Municipal - Médico</h2>
          <p className="text-gray-600">Pacientes em triagem aguardando atendimento</p>
        </div>
      </div>
      
      <div className="bg-white p-6 rounded-lg shadow border border-gray-100">
        <h3 className="text-sm font-semibold text-gray-500 uppercase tracking-wider mb-4">Minha Fila</h3>
        <div className="overflow-x-auto">
          <table className="min-w-full text-sm text-left">
            <thead className="bg-gray-50 text-gray-600">
              <tr>
                <th className="px-4 py-3">Prioridade</th>
                <th className="px-4 py-3">Paciente</th>
                <th className="px-4 py-3">Tempo Espera</th>
                <th className="px-4 py-3">Queixa Principal</th>
                <th className="px-4 py-3 text-right">Ação</th>
              </tr>
            </thead>
            <tbody className="divide-y divide-gray-100">
              <tr>
                <td className="px-4 py-3">
                  <span className="inline-block w-3 h-3 rounded-full bg-manchester-red mr-2"></span>
                  Emergência
                </td>
                <td className="px-4 py-3 font-semibold text-gray-800">Carlos Antunes</td>
                <td className="px-4 py-3 text-red-600 font-bold">5 min</td>
                <td className="px-4 py-3 truncate max-w-xs">Dor no peito irradiando, PA 190/110</td>
                <td className="px-4 py-3 text-right">
                  <button className="px-3 py-1 bg-manchester-red text-white rounded font-bold hover:bg-red-700">Atender</button>
                </td>
              </tr>
              <tr>
                <td className="px-4 py-3">
                  <span className="inline-block w-3 h-3 rounded-full bg-manchester-yellow mr-2"></span>
                  Urgente
                </td>
                <td className="px-4 py-3 font-semibold text-gray-800">Marina Souza</td>
                <td className="px-4 py-3">25 min</td>
                <td className="px-4 py-3 truncate max-w-xs">Crise de asma</td>
                <td className="px-4 py-3 text-right">
                  <button className="px-3 py-1 bg-assai-primary text-white rounded font-bold hover:bg-assai-secondary">Atender</button>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  );
}
