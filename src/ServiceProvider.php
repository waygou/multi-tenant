<?php

namespace Waygou\MultiTenant;

use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider as BaseServiceProvider;

class ServiceProvider extends BaseServiceProvider
{
    public function boot()
    {
        $this->overrideFiles();

        $this->registerCommands();

        $this->loadRoutes();
    }

    public function overrideFiles()
    {
        // config.database
        $this->publishes([
            __DIR__.'/../config/database.php.stub' => config_path('database.php'),
            __DIR__.'/../.env.stub' => base_path('.env'),
            __DIR__.'/Http/Kernel.php.stub' => base_path('app/Http/Kernel.php'),
            __DIR__.'/../database/migrations' => database_path('migrations'),
        ], 'waygou-multi-tenant-overrides');
    }

    protected function registerCommands()
    {
        // Command example.
        $this->commands([
            \Waygou\MultiTenant\Console\Commands\CreateTenant::class,
            \Waygou\MultiTenant\Console\Commands\DeleteTenant::class
        ]);
    }

    protected function loadRoutes()
    {
        // Load Routes example.
        $this->loadRoutesFrom(__DIR__.'/../routes/api.php');
    }

    public function register()
    {
        // Middleware alias example.
        app('router')->aliasMiddleware('sameip', \Waygou\MultiTenant\Middleware\SameIp::class);
    }
}
