<?php

namespace App\Providers;

use App\Models\Attendance;
use App\Models\Delivery;
use App\Models\Prescription;
use App\Policies\AttendancePolicy;
use App\Policies\DeliveryPolicy;
use App\Policies\PrescriptionPolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

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
        Gate::policy(Attendance::class, AttendancePolicy::class);
        Gate::policy(Prescription::class, PrescriptionPolicy::class);
        Gate::policy(Delivery::class, DeliveryPolicy::class);
    }
}
