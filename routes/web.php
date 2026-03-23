<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DeliveryController;
use App\Http\Controllers\HospitalController;
use App\Http\Controllers\PharmacyController;
use App\Http\Controllers\PortalController;
use App\Http\Controllers\PrescriptionController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ReceptionController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\TriageController;
use Illuminate\Support\Facades\Route;

Route::get('/', [PortalController::class, 'index'])->name('portal.home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::middleware('role:admin_secti,gestor,auditor')->group(function () {
        Route::get('/relatorios/conformidade', [ReportController::class, 'conformity'])->name('reports.conformity');
        Route::get('/relatorios/conformidade.csv', [ReportController::class, 'conformityCsv'])->name('reports.conformity.csv');
    });

    Route::middleware('role:admin_secti')->group(function () {
        Route::get('/admin/usuarios', [AdminController::class, 'users'])->name('admin.users');
        Route::post('/admin/usuarios', [AdminController::class, 'storeUser'])->name('admin.users.store');
        Route::post('/admin/conteudos', [PortalController::class, 'store'])->name('admin.portal.store');
    });

    Route::middleware('role:recepcionista,admin_secti')->group(function () {
        Route::get('/recepcao', [ReceptionController::class, 'index'])->name('reception.index');
        Route::post('/recepcao', [ReceptionController::class, 'store'])->name('reception.store');
    });

    Route::middleware('role:enfermeiro,admin_secti')->group(function () {
        Route::get('/triagem', [TriageController::class, 'index'])->name('triage.index');
        Route::post('/triagem/{attendance}', [TriageController::class, 'store'])->name('triage.store');
    });

    Route::middleware('role:medico_ubs,medico_hospital,admin_secti')->group(function () {
        Route::get('/prescricoes', [PrescriptionController::class, 'index'])->name('prescriptions.index');
        Route::post('/prescricoes', [PrescriptionController::class, 'store'])->name('prescriptions.store');
    });

    Route::middleware('role:farmaceutico,admin_secti')->group(function () {
        Route::get('/farmacia', [PharmacyController::class, 'index'])->name('pharmacy.index');
        Route::post('/farmacia/dispensar/{prescription}', [PharmacyController::class, 'dispense'])->name('pharmacy.dispense');
    });

    Route::middleware('role:medico_hospital,enfermeiro,admin_secti')->group(function () {
        Route::get('/hospital', [HospitalController::class, 'index'])->name('hospital.index');
        Route::post('/hospital/{attendance}', [HospitalController::class, 'store'])->name('hospital.store');
    });

    Route::middleware('role:entregador,admin_secti,farmaceutico')->group(function () {
        Route::get('/entregas', [DeliveryController::class, 'index'])->name('deliveries.index');
        Route::post('/entregas/{delivery}', [DeliveryController::class, 'updateStatus'])->name('deliveries.update');
    });
});

require __DIR__.'/auth.php';
