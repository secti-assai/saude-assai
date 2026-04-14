<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Models\WomenClinicAppointment;
use App\Models\CentralPharmacyRequest;
use App\Observers\WomenClinicAppointmentObserver;
use App\Observers\CentralPharmacyRequestObserver;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        WomenClinicAppointment::observe(WomenClinicAppointmentObserver::class);
        CentralPharmacyRequest::observe(CentralPharmacyRequestObserver::class);
    }
}
