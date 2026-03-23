<x-app-layout>
    <x-slot name="header"><h2 class="font-semibold text-xl">M5 - Prescricao Digital</h2></x-slot>
    <div class="py-6 max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
        @if (session('status'))<div class="p-3 bg-green-100 rounded">{{ session('status') }}</div>@endif
        <form method="POST" action="{{ route('prescriptions.store') }}" class="bg-white p-4 rounded shadow grid md:grid-cols-4 gap-2">
            @csrf
            <select name="attendance_id" class="rounded border-gray-300" required>
                @foreach ($attendances as $a)<option value="{{ $a->id }}">{{ $a->citizen->full_name }} - {{ $a->id }}</option>@endforeach
            </select>
            <select name="medication_id" class="rounded border-gray-300" required>
                @foreach ($medications as $m)<option value="{{ $m->id }}">{{ $m->name }}</option>@endforeach
            </select>
            <input name="dosage" placeholder="Dose" class="rounded border-gray-300">
            <input name="frequency" placeholder="Frequencia" class="rounded border-gray-300">
            <input type="number" name="duration_days" placeholder="Dias" class="rounded border-gray-300" required>
            <input type="number" name="quantity" placeholder="Quantidade" class="rounded border-gray-300" required>
            <select name="delivery_type" class="rounded border-gray-300"><option value="RETIRADA">Retirada</option><option value="ENTREGA">Entrega domiciliar</option></select>
            <button class="bg-blue-700 text-white rounded px-3">Emitir</button>
        </form>

        <div class="bg-white p-4 rounded shadow">
            <table class="w-full text-sm">
                <thead><tr class="border-b"><th>ID</th><th>Paciente</th><th>Status</th><th>Tipo</th></tr></thead>
                <tbody>@foreach ($prescriptions as $p)<tr class="border-b"><td>{{ $p->id }}</td><td>{{ $p->citizen->full_name }}</td><td>{{ $p->status }}</td><td>{{ $p->delivery_type }}</td></tr>@endforeach</tbody>
            </table>
        </div>
    </div>
</x-app-layout>
