<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Services\PRNumberGenerator;
use App\Services\RFQNumberGenerator;
use App\Services\PONumberGenerator;
use App\Services\ReceiptNumberGenerator;
use App\Services\StockService;

class PurchasingServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton(PRNumberGenerator::class, function ($app) {
            return new PRNumberGenerator();
        });
        
        $this->app->singleton(RFQNumberGenerator::class, function ($app) {
            return new RFQNumberGenerator();
        });
        
        $this->app->singleton(PONumberGenerator::class, function ($app) {
            return new PONumberGenerator();
        });
        
        $this->app->singleton(ReceiptNumberGenerator::class, function ($app) {
            return new ReceiptNumberGenerator();
        });
        
        $this->app->singleton(StockService::class, function ($app) {
            return new StockService();
        });
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        //
    }
}