<?php

namespace App\Http\Controllers;

use App\Models\HealthUnit;
use App\Models\User;
use App\Services\AuditService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AdminManagementController extends Controller
{
    public function __construct(private readonly AuditService $audit)
    {
    }

    public function usersArea(): View
    {
        $users = User::with('healthUnit')->orderBy('name')->get();
        $healthUnits = HealthUnit::orderBy('name')->get();

        return view('admin.users', [
            'users' => $users,
            'healthUnits' => $healthUnits,
            'roles' => [
                User::ROLE_ADMIN,
                User::ROLE_AGENDADOR,
                User::ROLE_RECEPCAO_CLINICA,
                User::ROLE_MEDICO_CLINICA,
                User::ROLE_RECEPCAO_FARMACIA,
                User::ROLE_ATENDIMENTO_FARMACIA,
            ],
            'permissions' => User::allPermissionOptions(),
        ]);
    }

    public function createUser(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'role' => ['required', 'string', Rule::in([
                User::ROLE_ADMIN,
                User::ROLE_AGENDADOR,
                User::ROLE_RECEPCAO_CLINICA,
                User::ROLE_MEDICO_CLINICA,
                User::ROLE_RECEPCAO_FARMACIA,
                User::ROLE_ATENDIMENTO_FARMACIA,
            ])],
            'health_unit_id' => ['nullable', 'exists:health_units,id'],
            'permissions' => ['nullable', 'array'],
            'permissions.*' => ['string', Rule::in(User::allPermissionOptions())],
        ]);

        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'role' => $data['role'],
            'health_unit_id' => $data['health_unit_id'] ?? null,
            'permissions' => $data['permissions'] ?? null,
            'email_verified_at' => now(),
        ]);

        $this->audit->log($request, 'ADMIN', 'CRIAR_USUARIO', User::class, (int) $user->id, [
            'target_user_email' => $user->email,
            'target_user_role' => $user->role,
        ]);

        return back()->with('status', 'Usuario criado com sucesso.');
    }

    public function updatePermissions(Request $request, User $user): RedirectResponse
    {
        $data = $request->validate([
            'role' => ['required', 'string', Rule::in([
                User::ROLE_ADMIN,
                User::ROLE_AGENDADOR,
                User::ROLE_RECEPCAO_CLINICA,
                User::ROLE_MEDICO_CLINICA,
                User::ROLE_RECEPCAO_FARMACIA,
                User::ROLE_ATENDIMENTO_FARMACIA,
            ])],
            'permissions' => ['nullable', 'array'],
            'permissions.*' => ['string', Rule::in(User::allPermissionOptions())],
            'health_unit_id' => ['nullable', 'exists:health_units,id'],
        ]);

        $user->update([
            'role' => $data['role'],
            'permissions' => $data['permissions'] ?? null,
            'health_unit_id' => $data['health_unit_id'] ?? null,
        ]);

        $this->audit->log($request, 'ADMIN', 'ATUALIZAR_PERMISSOES', User::class, (int) $user->id, [
            'target_user_email' => $user->email,
            'target_user_role' => $user->role,
            'permissions' => $user->permissions,
        ]);

        return back()->with('status', 'Permissoes atualizadas com sucesso.');
    }

    public function removeUser(Request $request, User $user): RedirectResponse
    {
        if ((int) $request->user()->id === (int) $user->id) {
            return back()->withErrors(['user' => 'Voce nao pode remover seu proprio usuario.']);
        }

        $email = $user->email;
        $userId = (int) $user->id;
        $user->delete();

        $this->audit->log($request, 'ADMIN', 'REMOVER_USUARIO', User::class, $userId, [
            'target_user_email' => $email,
        ]);

        return back()->with('status', 'Usuario removido com sucesso.');
    }

    public function reportsArea(): View
    {
        $usersByRole = User::query()
            ->select('role', DB::raw('COUNT(*) as total'))
            ->groupBy('role')
            ->orderBy('role')
            ->get();

        $activityByModule = DB::table('audit_logs')
            ->select('module', DB::raw('COUNT(*) as total'))
            ->groupBy('module')
            ->orderByDesc('total')
            ->limit(20)
            ->get();

        $recentAudits = DB::table('audit_logs')
            ->orderByDesc('created_at')
            ->limit(40)
            ->get();

        return view('admin.reports', [
            'usersByRole' => $usersByRole,
            'activityByModule' => $activityByModule,
            'recentAudits' => $recentAudits,
        ]);
    }
}
