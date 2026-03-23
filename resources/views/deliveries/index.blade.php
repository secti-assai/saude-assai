<x-app-layout>
    <x-slot name="header"><h2 class="font-semibold text-xl">M5 - Entregas Remedio em Casa</h2></x-slot>
    <div class="py-6 max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-4">
        @if (session('status'))<div class="p-3 bg-green-100 rounded">{{ session('status') }}</div>@endif
        @foreach ($deliveries as $d)
            <form method="POST" action="{{ route('deliveries.update', $d) }}" class="bg-white p-4 rounded shadow grid md:grid-cols-6 gap-2">
                @csrf
                <div class="md:col-span-2">Entrega #{{ $d->id }} - Prescricao #{{ $d->prescription_id }}</div>
                <select name="status" class="rounded border-gray-300">
                    <option @selected($d->status==='PENDENTE')>PENDENTE</option>
                    <option @selected($d->status==='EM_ROTA')>EM_ROTA</option>
                    <option @selected($d->status==='ENTREGUE')>ENTREGUE</option>
                    <option @selected($d->status==='FALHA')>FALHA</option>
                </select>
                <input name="gps_lat" placeholder="Lat" class="rounded border-gray-300">
                <input name="gps_lng" placeholder="Lng" class="rounded border-gray-300">
                <input name="failure_reason" placeholder="Motivo falha" class="rounded border-gray-300">
                <button class="bg-blue-700 text-white rounded px-3">Atualizar</button>
            </form>
        @endforeach
    </div>
</x-app-layout>
