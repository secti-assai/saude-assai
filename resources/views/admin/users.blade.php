<x-app-layout>
    <x-slot name="header">
        <div class="sa-page-header">
            <h2 class="sa-page-title">Administracao - Usuarios e Permissoes</h2>
            <p class="sa-page-subtitle">Gestao multiusuario para operacao real</p>
        </div>
    </x-slot>

    <div class="space-y-6">
        @if ($errors->any())
            <div class="bg-red-50 border-l-4 border-red-500 p-4">
                <ul class="text-sm text-red-700 list-disc list-inside">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        @if (session('status'))
            <div class="sa-alert-success"><span class="text-sm font-medium">{{ session('status') }}</span></div>
        @endif

        <div class="sa-card">
            <div class="sa-card-header"><h3 class="sa-card-title">Criar Usuario</h3></div>
            <form method="POST" action="{{ route('admin.users.create') }}" class="grid grid-cols-1 md:grid-cols-3 gap-4">
                @csrf
                <div>
                    <label class="sa-label">Nome *</label>
                    <input name="name" class="sa-input" value="{{ old('name') }}" required>
                </div>
                <div>
                    <label class="sa-label">Email *</label>
                    <input name="email" type="email" class="sa-input" value="{{ old('email') }}" required>
                </div>
                <div>
                    <label class="sa-label">Perfil *</label>
                    <select name="role" class="sa-select" required>
                        @foreach($roles as $role)
                            <option value="{{ $role }}">{{ $role }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="sa-label">Especialidade (medicos das clinicas)</label>
                    <select name="clinic_specialty" class="sa-select">
                        <option value="">Sem especialidade</option>
                        @foreach($clinicSpecialties as $specialtyValue => $specialtyLabel)
                            <option value="{{ $specialtyValue }}" @selected(old('clinic_specialty') === $specialtyValue)>{{ $specialtyLabel }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="sa-label">Unidade</label>
                    <select name="health_unit_id" class="sa-select">
                        <option value="">Sem unidade</option>
                        @foreach($healthUnits as $unit)
                            <option value="{{ $unit->id }}">{{ $unit->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="sa-label">Senha *</label>
                    <input name="password" type="password" class="sa-input" required>
                </div>
                <div>
                    <label class="sa-label">Confirmacao *</label>
                    <input name="password_confirmation" type="password" class="sa-input" required>
                </div>
                <div class="md:col-span-3">
                    <label class="sa-label">Permissoes de modulo</label>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-2">
                        @foreach($permissions as $permission)
                            <label class="inline-flex items-center gap-2 text-sm">
                                <input type="checkbox" name="permissions[]" value="{{ $permission }}" class="rounded border-gray-300">
                                <span>{{ $permission }}</span>
                            </label>
                        @endforeach
                    </div>
                </div>
                <div class="md:col-span-3 flex justify-end">
                    <button type="submit" class="sa-btn-primary">Criar Usuario</button>
                </div>
            </form>
        </div>

        <div class="sa-card">
            <div class="sa-card-header"><h3 class="sa-card-title">Usuarios Cadastrados</h3></div>
            <div class="overflow-x-auto">
                <table class="sa-table">
                    <thead>
                        <tr>
                            <th>Nome</th>
                            <th>Email</th>
                            <th>Perfil</th>
                            <th>Especialidade</th>
                            <th>Unidade</th>
                            <th>Permissoes</th>
                            <th>Acoes</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($users as $item)
                            <tr>
                                <td>{{ $item->name }}</td>
                                <td>{{ $item->email }}</td>
                                <td>{{ $item->role }}</td>
                                <td>{{ $item->role === \App\Models\User::ROLE_MEDICO_CLINICA ? $item->clinicSpecialtyLabel() : '—' }}</td>
                                <td>{{ $item->healthUnit->name ?? '—' }}</td>
                                <td class="text-xs">{{ implode(', ', $item->permissions ?? []) ?: 'herda por perfil' }}</td>
                                <td>
                                    <form method="POST" action="{{ route('admin.users.update-permissions', $item) }}" class="space-y-2 mb-2">
                                        @csrf
                                        <div>
                                            <select name="role" class="sa-select text-xs">
                                                @foreach($roles as $role)
                                                    <option value="{{ $role }}" @selected($role === $item->role)>{{ $role }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div>
                                            <select name="health_unit_id" class="sa-select text-xs">
                                                <option value="">Sem unidade</option>
                                                @foreach($healthUnits as $unit)
                                                    <option value="{{ $unit->id }}" @selected((string) $item->health_unit_id === (string) $unit->id)>{{ $unit->name }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div>
                                            <select name="clinic_specialty" class="sa-select text-xs">
                                                <option value="">Sem especialidade</option>
                                                @foreach($clinicSpecialties as $specialtyValue => $specialtyLabel)
                                                    <option value="{{ $specialtyValue }}" @selected((string) $item->clinic_specialty === (string) $specialtyValue)>{{ $specialtyLabel }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="grid grid-cols-1 gap-1 text-xs max-h-32 overflow-auto border rounded p-2">
                                            @foreach($permissions as $permission)
                                                <label class="inline-flex items-center gap-1">
                                                    <input type="checkbox" name="permissions[]" value="{{ $permission }}" class="rounded border-gray-300" @checked(in_array($permission, $item->permissions ?? [], true))>
                                                    <span>{{ $permission }}</span>
                                                </label>
                                            @endforeach
                                        </div>
                                        <button type="submit" class="sa-btn-secondary text-xs">Salvar</button>
                                    </form>
                                    <form method="POST" action="{{ route('admin.users.remove', $item) }}" onsubmit="return confirm('Remover usuario?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-red-600 text-xs font-semibold">Remover</button>
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-app-layout>
