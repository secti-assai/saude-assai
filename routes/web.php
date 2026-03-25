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
Route::get('/noticias', [PortalController::class, 'newsIndex'])->name('portal.news.index');
Route::get('/noticia/{id}', [PortalController::class, 'showNews'])->name('portal.news.show');
Route::get('/unidades', [PortalController::class, 'units'])->name('portal.units');
Route::get('/remedio-em-casa', [PortalController::class, 'index'])->name('portal.delivery');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/2fa/setup', [\App\Http\Controllers\TwoFactorController::class, 'setup'])->name('2fa.setup');
    Route::post('/2fa/enable', [\App\Http\Controllers\TwoFactorController::class, 'enable'])->name('2fa.enable');
});

Route::middleware(['auth', 'verified', '2fa'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::middleware('role:admin_secti,gestor,auditor')->group(function () {
        Route::get('/relatorios/conformidade', [ReportController::class, 'conformity'])->name('reports.conformity');
        Route::get('/relatorios/conformidade.csv', [ReportController::class, 'conformityCsv'])->name('reports.conformity.csv');
    });

    Route::middleware('role:admin_secti')->group(function () {
        Route::resource('admin/health-units', App\Http\Controllers\Admin\HealthUnitController::class, ['as' => 'admin']);
        Route::get('/admin/usuarios', [AdminController::class, 'users'])->name('admin.users');
        Route::post('/admin/usuarios', [AdminController::class, 'storeUser'])->name('admin.users.store');
        Route::post('/admin/usuarios/{user}', [AdminController::class, 'destroy'])->name('admin.users.destroy');
        
        Route::get('/admin/conteudos', [PortalController::class, 'adminIndex'])->name('admin.portal');
        Route::post('/admin/conteudos', [PortalController::class, 'store'])->name('admin.portal.store');
        Route::get('/admin/conteudos/{content}/edit', [PortalController::class, 'edit'])->name('admin.portal.edit');
        Route::put('/admin/conteudos/{content}', [PortalController::class, 'update'])->name('admin.portal.update');
        Route::post('/admin/conteudos/{content}', [PortalController::class, 'destroy'])->name('admin.portal.destroy');
    });

    Route::middleware('role:recepcionista,admin_secti')->group(function () {
        Route::get('/recepcao', [ReceptionController::class, 'index'])->name('reception.index');
        Route::get('/recepcao/cidadaos/cpf/{cpf}', [ReceptionController::class, 'lookupCitizenByCpf'])->name('reception.citizens.lookup');
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
        Route::get('/hospital/cidadaos/cpf/{cpf}', [HospitalController::class, 'lookupCitizenByCpf'])->name('hospital.citizens.lookup');
        Route::post('/hospital', [HospitalController::class, 'store'])->name('hospital.store');
    });

    Route::middleware('role:entregador,admin_secti,farmaceutico')->group(function () {
        Route::get('/entregas', [DeliveryController::class, 'index'])->name('deliveries.index');
        Route::put('/entregas/{delivery}', [DeliveryController::class, 'updateStatus'])->name('deliveries.update');
    });
});

require __DIR__.'/auth.php';
