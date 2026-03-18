<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Gate;
use Laravel\Pulse\PulseServiceProvider as BasePulseServiceProvider;
use App\Models\User;

class PulseServiceProvider extends BasePulseServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

    /** 
     * Bootstrap services.
     */
    public function boot(): void
    {
        Gate::define('viewPulse', function (User $user) {
      		return $user->isAdmin();     // sesuaikan user
        });

    }
}
