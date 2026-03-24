<?php

namespace App\Http\Controllers;

use App\Models\HealthUnit;
use App\Models\User;
use App\Services\AuditService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class AdminController extends Controller
{
    public function __construct(private readonly AuditService $audit)
    {
    }

    public function users(): View
    {
        $users = User::with('healthUnit')->latest()->get();
        $units = HealthUnit::orderBy('name')->get();

        return view('admin.users', compact('users', 'units'));
    }

    public function storeUser(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'role' => ['required', 'string'],
            'health_unit_id' => ['nullable', 'exists:health_units,id'],
            'password' => ['required', 'min:8'],
        ]);

        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'role' => $data['role'],
            'health_unit_id' => $data['health_unit_id'] ?? null,
            'password' => Hash::make($data['password']),
        ]);

        $this->audit->log($request, 'M2', 'CRIAR_USUARIO', User::class, $user->id);

        return back()->with('status', 'Usuario criado com sucesso.');
    }

    public function destroy(Request $request, User $user): RedirectResponse
    {
        if ($user->id === $request->user()->id) {
            return back()->withErrors(['email' => 'VocÃª nÃ£o pode excluir seu prÃ³prio usuÃ¡rio.']);
        }

        $this->audit->log($request, 'M2', 'DELETAR_USUARIO', User::class, $user->id);
        $user->delete();

        return back()->with('status', 'Usuario removido com sucesso.');
    }
}

