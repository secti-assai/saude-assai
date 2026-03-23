<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Painel Gestor (M8)</h2>
    </x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                @foreach ($kpis as $key => $value)
                    <div class="bg-white p-4 rounded shadow">
                        <p class="text-xs text-gray-500">{{ str_replace('_', ' ', strtoupper($key)) }}</p>
                        <p class="text-2xl font-bold">{{ $value }}</p>
                    </div>
                @endforeach
            </div>

            <div class="bg-white p-4 rounded shadow">
                <h3 class="font-semibold mb-3">Uso por Servidor</h3>
                <table class="w-full text-sm">
                    <thead>
                        <tr class="text-left border-b">
                            <th>Servidor</th>
                            <th>Perfil</th>
                            <th>Triagens</th>
                            <th>Prescricoes</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($usage as $row)
                            <tr class="border-b">
                                <td>{{ $row->name }}</td>
                                <td>{{ $row->role }}</td>
                                <td>{{ $row->triages_count }}</td>
                                <td>{{ $row->prescriptions_count }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-app-layout>
