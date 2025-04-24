<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Route;

class AccountingServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        $this->registerRoutes();
    }

    /**
     * Register the accounting module routes.
     *
     * @return void
     */
    protected function registerRoutes()
    {
        Route::middleware('api')
            ->prefix('api')
            ->group(base_path('routes/api-accounting-routes.php'));
    }
}