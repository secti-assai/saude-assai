import React from 'react';

export default function AdminDashboard() {
  return (
    <div className="space-y-6">
      <div className="flex bg-white p-6 rounded-lg shadow-sm border-l-4 border-assai-primary items-center justify-between">
        <div>
          <h2 className="text-xl font-bold text-gray-800">Painel Geral de GestÃ£o</h2>
          <p className="text-gray-600">MÃ©tricas em tempo real consolidadas pelo Gover.AssaÃ­</p>
        </div>
      </div>
      
      <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
        <div className="bg-white p-6 rounded-lg shadow-sm border border-gray-100">
          <h3 className="text-sm font-semibold text-gray-500 uppercase tracking-wider">Atendimentos Hoje</h3>
          <p className="mt-2 text-3xl font-black text-assai-primary">142</p>
        </div>
        <div className="bg-white p-6 rounded-lg shadow-sm border border-gray-100">
          <h3 className="text-sm font-semibold text-gray-500 uppercase tracking-wider">Erros LEDI APS (Fichas)</h3>
          <p className="mt-2 text-3xl font-black text-red-600">3</p>
          <span className="text-xs text-red-400">Requerem atuaÃ§Ã£o manual</span>
        </div>
        <div className="bg-white p-6 rounded-lg shadow-sm border border-gray-100">
          <h3 className="text-sm font-semibold text-gray-500 uppercase tracking-wider">Entregas Domiciliares</h3>
          <p className="mt-2 text-3xl font-black text-green-600">28</p>
          <span className="text-xs text-gray-400">Prog. RemÃ©dio em Casa</span>
        </div>
      </div>
    </div>
  );
}
