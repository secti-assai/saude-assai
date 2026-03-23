<x-app-layout>
    <x-slot name="header"><h2 class="font-semibold text-xl">Relatorio de Conformidade de Uso</h2></x-slot>
    <div class="py-6 max-w-7xl mx-auto sm:px-6 lg:px-8">
        <a href="{{ route('reports.conformity.csv') }}" class="bg-emerald-700 text-white px-3 py-2 rounded">Exportar CSV</a>
        <div class="bg-white p-4 rounded shadow mt-4">
            <table class="w-full text-sm">
                <thead><tr class="border-b"><th>Servidor</th><th>Perfil</th><th>Atend.</th><th>Triagens</th><th>Disp.</th><th>Indice</th></tr></thead>
                <tbody>
                    @foreach ($rows as $row)
                        <tr class="border-b"><td>{{ $row['name'] }}</td><td>{{ $row['role'] }}</td><td>{{ $row['attendances'] }}</td><td>{{ $row['triages'] }}</td><td>{{ $row['dispensations'] }}</td><td>{{ $row['conformity'] }}%</td></tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</x-app-layout>
