<x-app-layout>
    <x-slot name="header"><h2 class="font-semibold text-xl">M2 - Administracao</h2></x-slot>
    <div class="py-6 max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
        @if (session('status'))<div class="p-3 bg-green-100 rounded">{{ session('status') }}</div>@endif

        <form method="POST" action="{{ route('admin.users.store') }}" class="bg-white p-4 rounded shadow grid md:grid-cols-3 gap-2">
            @csrf
            <input name="name" placeholder="Nome" class="rounded border-gray-300" required>
            <input name="email" placeholder="Email" class="rounded border-gray-300" required>
            <input name="password" placeholder="Senha" class="rounded border-gray-300" required>
            <select name="role" class="rounded border-gray-300" required>
                <option value="admin_secti">admin_secti</option><option value="gestor">gestor</option><option value="recepcionista">recepcionista</option>
                <option value="enfermeiro">enfermeiro</option><option value="medico_ubs">medico_ubs</option><option value="medico_hospital">medico_hospital</option>
                <option value="farmaceutico">farmaceutico</option><option value="entregador">entregador</option><option value="auditor">auditor</option>
            </select>
            <select name="health_unit_id" class="rounded border-gray-300"><option value="">Unidade</option>@foreach($units as $u)<option value="{{ $u->id }}">{{ $u->name }}</option>@endforeach</select>
            <button class="bg-blue-700 text-white rounded px-3">Criar Usuario</button>
        </form>

        <form method="POST" action="{{ route('admin.portal.store') }}" class="bg-white p-4 rounded shadow grid md:grid-cols-3 gap-2">
            @csrf
            <select name="type" class="rounded border-gray-300" required>
                <option value="NOTICIA">NOTICIA</option>
                <option value="AVISO">AVISO</option>
                <option value="ALERTA">ALERTA</option>
                <option value="CAMPANHA">CAMPANHA</option>
            </select>
            <input name="title" placeholder="Titulo" class="rounded border-gray-300" required>
            <input name="body" placeholder="Conteudo" class="rounded border-gray-300">
            <button class="bg-emerald-700 text-white rounded px-3">Publicar Conteudo</button>
        </form>

        <div class="bg-white p-4 rounded shadow">
            <table class="w-full text-sm">
                <thead><tr class="border-b"><th>Nome</th><th>Email</th><th>Perfil</th><th>Unidade</th></tr></thead>
                <tbody>@foreach ($users as $u)<tr class="border-b"><td>{{ $u->name }}</td><td>{{ $u->email }}</td><td>{{ $u->role }}</td><td>{{ $u->healthUnit?->name }}</td></tr>@endforeach</tbody>
            </table>
        </div>
    </div>
</x-app-layout>
