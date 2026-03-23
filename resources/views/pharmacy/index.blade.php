<x-app-layout>
    <x-slot name="header"><h2 class="font-semibold text-xl">M6 - Farmacia Central</h2></x-slot>
    <div class="py-6 max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
        @if (session('status'))<div class="p-3 bg-green-100 rounded">{{ session('status') }}</div>@endif

        <div class="space-y-3">
            @foreach ($prescriptions as $p)
                <form method="POST" action="{{ route('pharmacy.dispense', $p) }}" class="bg-white p-4 rounded shadow flex items-center gap-3">
                    @csrf
                    <div class="flex-1">#{{ $p->id }} - {{ $p->citizen->full_name }} ({{ $p->status }})</div>
                    <label><input type="checkbox" name="emergency_override" value="1"> Emergencial</label>
                    <input name="justification" placeholder="Justificativa" class="rounded border-gray-300">
                    <button class="bg-blue-700 text-white rounded px-3">Dispensar</button>
                </form>
            @endforeach
        </div>

        <div class="bg-white p-4 rounded shadow">
            <h3 class="font-semibold mb-2">Ultimas Dispensacoes</h3>
            @foreach ($dispensations as $d)
                <p class="text-sm border-b py-1">#{{ $d->id }} - status {{ $d->blocked ? 'BLOQUEADA' : 'OK' }} - residencia {{ $d->residence_status }}</p>
            @endforeach
        </div>
    </div>
</x-app-layout>
