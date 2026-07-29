import React, { useState } from 'react';
import { Head } from '@inertiajs/react';
import AuthLayout from '@/Layouts/AuthLayout';
import { Input } from '@/Components/ui/input';
import { Button } from '@/Components/ui/button';
import { Search } from 'lucide-react';
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/Components/ui/card';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/Components/ui/table';
import { Badge } from '@/Components/ui/badge';
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
                <Card>
                    <CardHeader>
                        <CardTitle>Buscar Medicamentos na Farmácia Central</CardTitle>
                        <CardDescription>
                            Consulte a disponibilidade de medicamentos antes de prescrever.
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        <form onSubmit={handleSearch} className="flex gap-4">
                            <Input 
                                type="text"
                                placeholder="Digite o nome do medicamento (ex: Dipirona)"
                                value={search}
                                onChange={(e) => setSearch(e.target.value)}
                                className="max-w-md"
                            />
                            <Button type="submit" disabled={loading}>
                                <Search className="w-4 h-4 mr-2" />
                                Pesquisar
                            </Button>
                        </form>
                    </CardContent>
                </Card>

                <Card>
                    <CardContent className="pt-6">
                        {loading ? (
                            <div className="text-center py-8 text-muted-foreground">Buscando...</div>
                        ) : (
                            <Table>
                                <TableHeader>
                                    <TableRow>
                                        <TableHead>Medicamento</TableHead>
                                        <TableHead>Apresentação / Conc.</TableHead>
                                        <TableHead>REMUME</TableHead>
                                        <TableHead className="text-right">Saldo Disponível</TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    {medications.length === 0 ? (
                                        <TableRow>
                                            <TableCell colSpan={4} className="text-center py-8 text-muted-foreground">
                                                Nenhum medicamento encontrado.
                                            </TableCell>
                                        </TableRow>
                                    ) : (
                                        medications.map((med) => (
                                            <TableRow key={med.id}>
                                                <TableCell className="font-medium">{med.name}</TableCell>
                                                <TableCell>
                                                    {med.presentation} {med.concentration && `- ${med.concentration}`}
                                                </TableCell>
                                                <TableCell>
                                                    {med.is_remume ? (
                                                        <Badge variant="default" className="bg-green-600">Sim</Badge>
                                                    ) : (
                                                        <Badge variant="secondary">Não</Badge>
                                                    )}
                                                </TableCell>
                                                <TableCell className="text-right">
                                                    {med.stock_available > 0 ? (
                                                        <span className="text-green-600 font-bold">{med.stock_available} un</span>
                                                    ) : (
                                                        <span className="text-red-500 font-bold">Sem Estoque</span>
                                                    )}
                                                </TableCell>
                                            </TableRow>
                                        ))
                                    )}
                                </TableBody>
                            </Table>
                        )}
                    </CardContent>
                </Card>
            </div>
        </AuthLayout>
    );
}
