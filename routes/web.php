<?php

use App\Http\Controllers\AdminManagementController;
use App\Http\Controllers\CallController;
use App\Http\Controllers\CentralPharmacyController;
use App\Http\Controllers\PrescriptionMedicationController;
use App\Http\Controllers\TwoFactorController;
use App\Http\Controllers\WomenClinicController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/login');

Route::middleware(['auth'])->group(function () {
    Route::get('/2fa/setup', [TwoFactorController::class, 'setup'])->name('2fa.setup');
    Route::post('/2fa/enable', [TwoFactorController::class, 'enable'])->name('2fa.enable');
});

Route::middleware(['auth'])->group(function () {
    Route::get('/dashboard', function (Request $request) {
        $role = (string) ($request->user()?->role ?? '');

        return match ($role) {
            'admin' => redirect()->route('admin.reports'),
            'agendador' => redirect()->route('women-clinic.agendador'),
            'recepcao_clinica' => redirect()->route('women-clinic.recepcao'),
            'medico_clinica' => redirect()->route('women-clinic.medico'),
            'recepcao_farmacia' => redirect()->route('central-pharmacy.recepcao'),
            'atendimento_farmacia' => redirect()->route('central-pharmacy.atendimento'),
            default => abort(403, 'Perfil sem area operacional configurada.'),
        };
    })->name('dashboard');

    Route::get('/profile', function () {
        return redirect()->route('dashboard');
    })->name('profile.edit');

    Route::get('/clinica-mulher/agendador', [WomenClinicController::class, 'agendadorArea'])
        ->middleware('role:agendador')
        ->name('women-clinic.agendador');

    Route::post('/clinica-mulher/agendamentos/iniciar', [WomenClinicController::class, 'startScheduleFlow'])
        ->middleware('role:agendador')
        ->name('women-clinic.schedule.start');

    Route::post('/clinica-mulher/agendamentos/verificar-identidade', [WomenClinicController::class, 'verifyScheduleIdentity'])
        ->middleware('role:agendador')
        ->name('women-clinic.schedule.verify-identity');

    Route::get('/clinica-mulher/recepcao', [WomenClinicController::class, 'recepcaoArea'])
        ->middleware('role:recepcao_clinica')
        ->name('women-clinic.recepcao');

    Route::get('/clinica-mulher/medico', [WomenClinicController::class, 'medicoArea'])
        ->middleware('role:medico_clinica')
        ->name('women-clinic.medico');

    Route::post('/clinica-mulher/agendamentos', [WomenClinicController::class, 'schedule'])
        ->middleware(['role:agendador', 'permission:women_clinic.schedule'])
        ->name('women-clinic.schedule');

    Route::post('/clinica-mulher/agendamentos/{womenClinicAppointment}/check-in', [WomenClinicController::class, 'checkIn'])
        ->middleware(['role:recepcao_clinica', 'permission:women_clinic.checkin'])
        ->name('women-clinic.check-in');

    Route::post('/clinica-mulher/agendamentos/{womenClinicAppointment}/check-out', [WomenClinicController::class, 'checkOut'])
        ->middleware(['role:medico_clinica', 'permission:women_clinic.checkout'])
        ->name('women-clinic.check-out');

    Route::get('/farmacia-central/recepcao', [CentralPharmacyController::class, 'recepcaoArea'])
        ->middleware('role:recepcao_farmacia')
        ->name('central-pharmacy.recepcao');

    Route::post('/farmacia-central/solicitacoes/iniciar', [CentralPharmacyController::class, 'startReceptionFlow'])
        ->middleware('role:recepcao_farmacia')
        ->name('central-pharmacy.reception.start');

    Route::post('/farmacia-central/solicitacoes/verificar-identidade', [CentralPharmacyController::class, 'verifyReceptionIdentity'])
        ->middleware('role:recepcao_farmacia')
        ->name('central-pharmacy.reception.verify-identity');

    Route::get('/farmacia-central/atendimento', [CentralPharmacyController::class, 'atendimentoArea'])
        ->middleware('role:atendimento_farmacia')
        ->name('central-pharmacy.atendimento');

    Route::post('/farmacia-central/solicitacoes', [CentralPharmacyController::class, 'registerReception'])
        ->middleware(['role:recepcao_farmacia', 'permission:central_pharmacy.reception'])
        ->name('central-pharmacy.register-reception');

    Route::post('/farmacia-central/solicitacoes/{centralPharmacyRequest}/dispensar', [CentralPharmacyController::class, 'dispense'])
        ->middleware(['role:atendimento_farmacia', 'permission:central_pharmacy.dispense'])
        ->name('central-pharmacy.dispense');

    Route::get('/admin/usuarios', [AdminManagementController::class, 'usersArea'])
        ->middleware('role:admin')
        ->name('admin.users');

    Route::post('/admin/usuarios', [AdminManagementController::class, 'createUser'])
        ->middleware('role:admin')
        ->name('admin.users.create');

    Route::post('/admin/usuarios/{user}/permissoes', [AdminManagementController::class, 'updatePermissions'])
        ->middleware('role:admin')
        ->name('admin.users.update-permissions');

    Route::delete('/admin/usuarios/{user}', [AdminManagementController::class, 'removeUser'])
        ->middleware('role:admin')
        ->name('admin.users.remove');

    Route::get('/admin/relatorios', [AdminManagementController::class, 'reportsArea'])
        ->middleware('role:admin')
        ->name('admin.reports');

    Route::fallback(function () {
        return redirect()->route('dashboard');
    });
});

require __DIR__ . '/auth.php';

Route::post('/prescriptions/medications', [PrescriptionMedicationController::class, 'store'])
    ->middleware('auth')
    ->name('prescriptions.medications.store');

Route::get('/painel/{unit}', [CallController::class, 'panel'])
    ->name('calls.panel');

Route::post('/calls/{attendance}', [CallController::class, 'call'])
    ->middleware('auth');

