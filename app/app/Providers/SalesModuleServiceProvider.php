<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class SalesModuleServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        // Register any bindings or dependencies for the Sales Module
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        // Load routes, views, migrations, etc. for the Sales Module
        $this->loadRoutesFrom(base_path('routes/sales.php'));
        
        // Register middleware specific to the Sales Module if needed
        
        // Register sales module commands if needed
        if ($this->app->runningInConsole()) {
            $this->commands([
                // Add any console commands here
            ]);
        }
    }
}