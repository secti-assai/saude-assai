<?php

use App\Http\Controllers\AdminManagementController;
use App\Http\Controllers\CallController;
use App\Http\Controllers\CentralPharmacyPublicController;
use App\Http\Controllers\CentralPharmacyController;
use App\Http\Controllers\CentralPharmacyReportController;
use App\Http\Controllers\CentralPharmacyUnifiedController;
use App\Http\Controllers\PoliclinicaController;
use App\Http\Controllers\PoliclinicaReportController;
use App\Http\Controllers\PrescriptionMedicationController;
use App\Http\Controllers\WomenClinicController;
use App\Http\Controllers\WomenClinicPublicController;
use App\Http\Controllers\WomenClinicReportController;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/login');

Route::match(['get', 'post'], '/clinica-mulher/publico/cancelamento/{womenClinicAppointment}', [WomenClinicPublicController::class, 'cancel'])
    ->middleware('signed:relative')
    ->name('women-clinic.public.cancel');

Route::match(['get', 'post'], '/clinica-mulher/publico/avaliacao/{womenClinicAppointment}', [WomenClinicPublicController::class, 'feedback'])
    ->middleware('signed:relative')
    ->name('women-clinic.public.feedback');

Route::match(['get', 'post'], '/farmacia-central/publico/avaliacao/{centralPharmacyRequest}', [CentralPharmacyPublicController::class, 'feedback'])
    ->middleware('signed:relative')
    ->name('central-pharmacy.public.feedback');

Route::middleware(['auth', 'module.context'])->group(function () {
    Route::get('/dashboard', function (Request $request) {
        $user = $request->user();

        if (! $user) {
            abort(403, 'Perfil sem area operacional configurada.');
        }

        if ($user->role === User::ROLE_ADMIN) {
            return redirect()->route('admin.reports');
        }

        if ($user->hasPermission(User::PERMISSION_WOMEN_CLINIC_SCHEDULE)) {
            return redirect()->route('clinic-scheduler.index');
        }

        if ($user->hasPermission(User::PERMISSION_WOMEN_CLINIC_REPORTS)) {
            return redirect()->route('women-clinic.reports');
        }

        if ($user->hasPermission(User::PERMISSION_POLICLINICA_REPORTS)) {
            return redirect()->route('policlinica.reports');
        }

        if ($user->hasPermission(User::PERMISSION_WOMEN_CLINIC_CHECKIN)) {
            return redirect()->route('women-clinic.recepcao');
        }

        if ($user->hasPermission(User::PERMISSION_POLICLINICA_CHECKIN)) {
            return redirect()->route('policlinica.recepcao');
        }

        if ($user->hasPermission(User::PERMISSION_WOMEN_CLINIC_CHECKOUT)) {
            return redirect()->route('women-clinic.medico');
        }

        if ($user->hasPermission(User::PERMISSION_POLICLINICA_CHECKOUT)) {
            return redirect()->route('policlinica.medico');
        }

        if ($user->hasPermission(User::PERMISSION_CENTRAL_PHARMACY)) {
            return redirect()->route('central-pharmacy.unified');
        }

        if ($user->hasPermission(User::PERMISSION_CENTRAL_PHARMACY_REPORTS)) {
            return redirect()->route('central-pharmacy.reports');
        }

        abort(403, 'Perfil sem area operacional configurada.');
    })->name('dashboard');

    Route::get('/profile', function () {
        return redirect()->route('dashboard');
    })->name('profile.edit');

    Route::get('/agendador', [WomenClinicController::class, 'agendadorArea'])
        ->middleware('permission:women_clinic.schedule')
        ->name('clinic-scheduler.index');

    Route::post('/agendamentos/iniciar', [WomenClinicController::class, 'startScheduleFlow'])
        ->middleware('permission:women_clinic.schedule')
        ->name('clinic-scheduler.schedule.start');

    Route::post('/agendamentos/cancelar', [WomenClinicController::class, 'cancelScheduleFlow'])
        ->middleware('permission:women_clinic.schedule')
        ->name('clinic-scheduler.schedule.cancel');

    Route::post('/agendamentos/verificar-identidade', [WomenClinicController::class, 'verifyScheduleIdentity'])
        ->middleware('permission:women_clinic.schedule')
        ->name('clinic-scheduler.schedule.verify-identity');

    Route::post('/agendamentos', [WomenClinicController::class, 'schedule'])
        ->middleware('permission:women_clinic.schedule')
        ->name('clinic-scheduler.schedule');

    Route::get('/clinica-mulher/agendador', [WomenClinicController::class, 'agendadorArea'])
        ->middleware('permission:women_clinic.schedule')
        ->name('women-clinic.agendador');

    Route::post('/clinica-mulher/agendamentos/iniciar', [WomenClinicController::class, 'startScheduleFlow'])
        ->middleware('permission:women_clinic.schedule')
        ->name('women-clinic.schedule.start');

    Route::post('/clinica-mulher/agendamentos/cancelar', [WomenClinicController::class, 'cancelScheduleFlow'])
        ->middleware('permission:women_clinic.schedule')
        ->name('women-clinic.schedule.cancel');

    Route::post('/clinica-mulher/agendamentos/verificar-identidade', [WomenClinicController::class, 'verifyScheduleIdentity'])
        ->middleware('permission:women_clinic.schedule')
        ->name('women-clinic.schedule.verify-identity');

    Route::get('/clinica-mulher/recepcao', [WomenClinicController::class, 'recepcaoArea'])
        ->middleware('permission:women_clinic.checkin')
        ->name('women-clinic.recepcao');

    Route::get('/clinica-mulher/recepcao/fila-dados', [WomenClinicController::class, 'recepcaoData'])
        ->middleware('permission:women_clinic.checkin')
        ->name('women-clinic.recepcao.data');

    Route::get('/clinica-mulher/medico', [WomenClinicController::class, 'medicoArea'])
        ->middleware('permission:women_clinic.checkout')
        ->name('women-clinic.medico');

    Route::get('/clinica-mulher/medico/fila-dados', [WomenClinicController::class, 'medicoData'])
        ->middleware('permission:women_clinic.checkout')
        ->name('women-clinic.medico.data');

    Route::post('/clinica-mulher/agendamentos', [WomenClinicController::class, 'schedule'])
        ->middleware('permission:women_clinic.schedule')
        ->name('women-clinic.schedule');

    Route::get('/policlinica/recepcao', [PoliclinicaController::class, 'recepcaoArea'])
        ->middleware('permission:policlinica.checkin')
        ->name('policlinica.recepcao');

    Route::get('/policlinica/recepcao/fila-dados', [PoliclinicaController::class, 'recepcaoData'])
        ->middleware('permission:policlinica.checkin')
        ->name('policlinica.recepcao.data');

    Route::get('/policlinica/medico', [PoliclinicaController::class, 'medicoArea'])
        ->middleware('permission:policlinica.checkout')
        ->name('policlinica.medico');

    Route::get('/policlinica/medico/fila-dados', [PoliclinicaController::class, 'medicoData'])
        ->middleware('permission:policlinica.checkout')
        ->name('policlinica.medico.data');

    Route::post('/policlinica/agendamentos/{womenClinicAppointment}/check-in', [PoliclinicaController::class, 'checkIn'])
        ->middleware('permission:policlinica.checkin')
        ->name('policlinica.check-in');

    Route::post('/policlinica/agendamentos/{womenClinicAppointment}/check-out', [PoliclinicaController::class, 'checkOut'])
        ->middleware('permission:policlinica.checkout')
        ->name('policlinica.check-out');

    Route::get('/policlinica/relatorios', [PoliclinicaReportController::class, 'index'])
        ->middleware('permission:policlinica.reports')
        ->name('policlinica.reports');

    Route::post('/clinica-mulher/agendamentos/{womenClinicAppointment}/check-in', [WomenClinicController::class, 'checkIn'])
        ->middleware('permission:women_clinic.checkin')
        ->name('women-clinic.check-in');

    Route::post('/clinica-mulher/agendamentos/{womenClinicAppointment}/check-out', [WomenClinicController::class, 'checkOut'])
        ->middleware('permission:women_clinic.checkout')
        ->name('women-clinic.check-out');

    Route::get('/clinica-mulher/relatorios', [WomenClinicReportController::class, 'index'])
        ->middleware('permission:women_clinic.reports')
        ->name('women-clinic.reports');

    // Legacy central pharmacy routes kept for backward compatibility with
    // existing flows/tests and profile redirects (recepcao_farmacia/atendimento_farmacia).
    Route::get('/farmacia-central/recepcao', [CentralPharmacyController::class, 'recepcaoArea'])
        ->middleware('role:recepcao_farmacia,farmacia')
        ->name('central-pharmacy.recepcao');

    Route::post('/farmacia-central/recepcao/iniciar', [CentralPharmacyController::class, 'startReceptionFlow'])
        ->middleware('role:recepcao_farmacia,farmacia')
        ->name('central-pharmacy.reception.start');

    Route::post('/farmacia-central/recepcao/verificar-identidade', [CentralPharmacyController::class, 'verifyReceptionIdentity'])
        ->middleware('role:recepcao_farmacia,farmacia')
        ->name('central-pharmacy.reception.verify-identity');

    Route::post('/farmacia-central/recepcao/cancelar', [CentralPharmacyController::class, 'cancelReceptionFlow'])
        ->middleware('role:recepcao_farmacia,farmacia')
        ->name('central-pharmacy.reception.cancel');

    Route::post('/farmacia-central/recepcao/cadastrar', [CentralPharmacyController::class, 'registerReception'])
        ->middleware('role:recepcao_farmacia,farmacia')
        ->name('central-pharmacy.register-reception');

    Route::get('/farmacia-central/atendimento', [CentralPharmacyController::class, 'atendimentoArea'])
        ->middleware('role:atendimento_farmacia,farmacia')
        ->name('central-pharmacy.atendimento');

    Route::get('/farmacia-central/atendimento/fila-dados', [CentralPharmacyController::class, 'atendimentoData'])
        ->middleware('role:atendimento_farmacia,farmacia')
        ->name('central-pharmacy.atendimento.data');

    Route::post('/farmacia-central/solicitacoes/{centralPharmacyRequest}/dispensar', [CentralPharmacyController::class, 'dispense'])
        ->middleware('role:atendimento_farmacia,farmacia')
        ->name('central-pharmacy.dispense');

    Route::post('/farmacia-central/solicitacoes/{centralPharmacyRequest}/recusar', [CentralPharmacyController::class, 'refuse'])
        ->middleware('role:atendimento_farmacia,farmacia')
        ->name('central-pharmacy.refuse');

    Route::post('/farmacia-central/solicitacoes/{centralPharmacyRequest}/dispensar-equivalente', [CentralPharmacyController::class, 'dispenseEquivalent'])
        ->middleware('role:atendimento_farmacia,farmacia')
        ->name('central-pharmacy.dispense-equivalent');

    Route::get('/farmacia-central', [CentralPharmacyUnifiedController::class, 'index'])
        ->middleware('permission:central_pharmacy.unified')
        ->name('central-pharmacy.unified');

    Route::post('/farmacia-central/buscar', [CentralPharmacyUnifiedController::class, 'search'])
        ->middleware('permission:central_pharmacy.unified')
        ->name('central-pharmacy.unified.search');

    Route::post('/farmacia-central/dispensar', [CentralPharmacyUnifiedController::class, 'dispense'])
        ->middleware('permission:central_pharmacy.unified')
        ->name('central-pharmacy.unified.dispense');

    Route::post('/farmacia-central/nao-dispensar-bloqueio', [CentralPharmacyUnifiedController::class, 'noDispenseBlocked'])
        ->middleware('permission:central_pharmacy.unified')
        ->name('central-pharmacy.unified.no-dispense-blocked');

    Route::get('/farmacia-central/relatorios', [CentralPharmacyReportController::class, 'index'])
        ->middleware('permission:central_pharmacy.reports')
        ->name('central-pharmacy.reports');

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
    ->middleware(['auth', 'module.context'])
    ->name('prescriptions.medications.store');

Route::get('/painel/{unit}', [CallController::class, 'panel'])
    ->name('calls.panel');

Route::post('/calls/{attendance}', [CallController::class, 'call'])
    ->middleware(['auth', 'module.context']);

