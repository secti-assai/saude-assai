import React, { useState } from 'react';
import { Head } from '@inertiajs/react';
import AuthLayout from '@/Layouts/AuthLayout';
import { Search } from 'lucide-react';
import axios from 'axios';

interface Medication {
    id: number;
    name: string;
    presentation: string | null;
    concentration: string | null;
    is_remume: boolean;
    stock_available: number;
}

export default function StockConsultation() {
    const [search, setSearch] = useState('');
    const [loading, setLoading] = useState(false);
    const [medications, setMedications] = useState<Medication[]>([]);

    const handleSearch = async (e?: React.FormEvent) => {
        if (e) e.preventDefault();
        setLoading(true);
        
        try {
            const response = await axios.get('/medico/estoque/pesquisar', {
                params: { q: search }
            });
            setMedications(response.data.data);
        } catch (error) {
            console.error('Error fetching stock:', error);
        } finally {
            setLoading(false);
        }
    };

    return (
        <AuthLayout header="Consulta de Estoque - Médico">
            <Head title="Consulta de Estoque" />

            <div className="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8 space-y-6">
                <div className="bg-white shadow rounded-lg p-6">
                    <h2 className="text-lg font-semibold text-gray-900 mb-2">Buscar Medicamentos na Farmácia Central</h2>
                    <p className="text-sm text-gray-600 mb-6">
                        Consulte a disponibilidade de medicamentos antes de prescrever.
                    </p>
                    <form onSubmit={handleSearch} className="flex gap-4">
                        <input 
                            type="text"
                            placeholder="Digite o nome do medicamento (ex: Dipirona)"
                            value={search}
                            onChange={(e) => setSearch(e.target.value)}
                            className="flex-1 max-w-md px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                        />
                        <button 
                            type="submit" 
                            disabled={loading}
                            className="inline-flex items-center px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-white hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 disabled:opacity-50"
                        >
                            <Search className="w-4 h-4 mr-2" />
                            Pesquisar
                        </button>
                    </form>
                </div>

                <div className="bg-white shadow rounded-lg overflow-hidden">
                    <div className="p-6 overflow-x-auto">
                        {loading ? (
                            <div className="text-center py-8 text-gray-500">Buscando...</div>
                        ) : (
                            <table className="min-w-full divide-y divide-gray-200">
                                <thead className="bg-gray-50">
                                    <tr>
                                        <th scope="col" className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Medicamento</th>
                                        <th scope="col" className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Apresentação / Conc.</th>
                                        <th scope="col" className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">REMUME</th>
                                        <th scope="col" className="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Saldo Disponível</th>
                                    </tr>
                                </thead>
                                <tbody className="bg-white divide-y divide-gray-200">
                                    {medications.length === 0 ? (
                                        <tr>
                                            <td colSpan={4} className="px-6 py-8 text-center text-gray-500">
                                                Nenhum medicamento encontrado.
                                            </td>
                                        </tr>
                                    ) : (
                                        medications.map((med) => (
                                            <tr key={med.id}>
                                                <td className="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">{med.name}</td>
                                                <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                    {med.presentation} {med.concentration && `- ${med.concentration}`}
                                                </td>
                                                <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                    {med.is_remume ? (
                                                        <span className="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">Sim</span>
                                                    ) : (
                                                        <span className="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800">Não</span>
                                                    )}
                                                </td>
                                                <td className="px-6 py-4 whitespace-nowrap text-sm text-right">
                                                    {med.stock_available > 0 ? (
                                                        <span className="text-green-600 font-bold">{med.stock_available} un</span>
                                                    ) : (
                                                        <span className="text-red-500 font-bold">Sem Estoque</span>
                                                    )}
                                                </td>
                                            </tr>
                                        ))
                                    )}
                                </tbody>
                            </table>
                        )}
                    </div>
                </div>
            </div>
        </AuthLayout>
    );
}
