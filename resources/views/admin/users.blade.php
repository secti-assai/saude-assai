<x-app-layout>
    <x-slot name="header">
        <div class="sa-page-header">
            <h2 class="sa-page-title">Gerenciamento de Usuários</h2>
            <p class="sa-page-subtitle">Cadastro e permissões dos servidores</p>
        </div>
    </x-slot>

    <div class="space-y-6">
        @if (session('status'))
            <div class="sa-alert-success sa-fade-in">
                <svg class="w-5 h-5 text-green-600 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                <span class="text-sm font-medium">{{ session('status') }}</span>
            </div>
        @endif

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            {{-- New User Form --}}
            <div class="sa-card sa-fade-in lg:col-span-1">
                <div class="sa-card-header">
                    <h3 class="sa-card-title">Novo Servidor</h3>
                </div>
                <form method="POST" action="{{ route('admin.users.store') }}" class="space-y-4">
                    @csrf
                    <div>
                        <label class="sa-label">Nome *</label>
                        <input name="name" class="sa-input" required placeholder="Nome completo">
                        @error('name') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="sa-label">E-mail *</label>
                        <input name="email" type="email" class="sa-input" required placeholder="email@saude.assai.pr.gov.br">
                        @error('email') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="sa-label">Perfil *</label>
                        <select name="role" class="sa-select" required>
                            <option value="">Selecione...</option>
                            <option value="admin">Administrador</option>
                            <option value="medico">Médico</option>
                            <option value="enfermeiro">Enfermeiro</option>
                            <option value="farmaceutico">Farmacêutico</option>
                            <option value="recepcionista">Recepcionista</option>
                            <option value="motorista">Motorista</option>
                        </select>
                        @error('role') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="sa-label">Senha *</label>
                        <input name="password" type="password" class="sa-input" required placeholder="Mínimo 8 caracteres">
                        @error('password') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="sa-label">Confirmar Senha *</label>
                        <input name="password_confirmation" type="password" class="sa-input" required placeholder="Repita a senha">
                    </div>
                    <button type="submit" class="sa-btn-primary w-full">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M18 7.5v3m0 0v3m0-3h3m-3 0h-3m-2.25-4.125a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zM3 19.235v-.11a6.375 6.375 0 0112.75 0v.109A12.318 12.318 0 019.374 21c-2.331 0-4.512-.645-6.374-1.766z"/></svg>
                        Cadastrar Servidor
                    </button>
                </form>
            </div>

            {{-- Users Table --}}
            <div class="sa-card sa-fade-in lg:col-span-2">
                <div class="sa-card-header">
                    <h3 class="sa-card-title">Servidores Cadastrados</h3>
                    <span class="sa-badge sa-badge-gray">{{ $users->count() }}</span>
                </div>
                <div class="overflow-x-auto">
                    <table class="sa-table">
                        <thead>
                            <tr>
                                <th>Nome</th>
                                <th>E-mail</th>
                                <th>Perfil</th>
                                <th>Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($users as $user)
                                <tr>
                                    <td class="font-medium text-gray-900">{{ $user->name }}</td>
                                    <td class="text-gray-500 text-sm">{{ $user->email }}</td>
                                    <td>
                                        @php
                                            $roleColors = [
                                                'admin' => 'sa-badge-danger',
                                                'medico' => 'sa-badge-primary',
                                                'enfermeiro' => 'sa-badge-info',
                                                'farmaceutico' => 'sa-badge-success',
                                                'recepcionista' => 'sa-badge-warning',
                                                'motorista' => 'sa-badge-purple',
                                            ];
                                        @endphp
                                        <span class="sa-badge {{ $roleColors[$user->role] ?? 'sa-badge-gray' }}">{{ $user->role }}</span>
                                    </td>
                                    <td>
                                        @if ($user->id !== auth()->id())
                                            <form method="POST" action="{{ route('admin.users.destroy', $user) }}" onsubmit="return confirm('Tem certeza que deseja remover este servidor?')">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="text-red-500 hover:text-red-700 text-sm font-medium transition">
                                                    Remover
                                                </button>
                                            </form>
                                        @else
                                            <span class="text-gray-400 text-sm">Você</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="text-center text-gray-400 py-8">Nenhum servidor cadastrado.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
