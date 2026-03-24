import React from 'react';

export default function PharmacyDashboard() {
  return (
    <div className="space-y-6">
      <div className="flex bg-white p-6 rounded-lg shadow-sm border-l-4 border-assai-accent items-center justify-between">
        <div>
          <h2 className="text-xl font-bold text-gray-800">Fila da Farmácia Central</h2>
          <p className="text-gray-600">Dispensações pendentes e validação de residência</p>
        </div>
      </div>
      
      <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <div className="bg-white p-6 rounded-lg shadow border border-gray-100">
          <h3 className="text-sm font-semibold text-gray-500 uppercase tracking-wider mb-4">Pacientes Aguardando</h3>
          <ul className="space-y-3">
            <li className="p-3 bg-gray-50 rounded-md flex justify-between items-center border-l-4 border-manchester-yellow">
              <div>
                <strong className="block text-gray-800">João da Silva</strong>
                <span className="text-xs text-gray-500">Validação: Residência Confirmada</span>
              </div>
              <button className="px-3 py-1 bg-assai-primary text-white text-sm rounded hover:bg-assai-secondary font-semibold">Atender</button>
            </li>
            <li className="p-3 bg-gray-50 rounded-md flex justify-between items-center border-l-4 border-manchester-green">
               <div>
                <strong className="block text-gray-800">Maria Oliveira</strong>
                <span className="text-xs text-red-500 font-bold">Residência: Pendente</span>
              </div>
              <button className="px-3 py-1 bg-gray-300 text-gray-700 text-sm rounded cursor-not-allowed">Bloqueado</button>
            </li>
          </ul>
        </div>

        <div className="bg-white p-6 rounded-lg shadow border border-gray-100">
          <h3 className="text-sm font-semibold text-red-600 uppercase tracking-wider mb-4">Estoque Crítico (Aviso)</h3>
          <ul className="space-y-2">
            <li className="flex justify-between text-sm">
              <span className="text-gray-700">Dipirona 500mg</span>
              <span className="font-bold text-red-600">12 unid.</span>
            </li>
            <li className="flex justify-between text-sm">
              <span className="text-gray-700">Losartana 50mg</span>
              <span className="font-bold text-red-600">8 unid.</span>
            </li>
          </ul>
        </div>
      </div>
    </div>
  );
}
