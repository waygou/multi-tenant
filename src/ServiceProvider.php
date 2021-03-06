<?php

namespace Waygou\MultiTenant;

use Illuminate\Support\ServiceProvider as BaseServiceProvider;

class ServiceProvider extends BaseServiceProvider
{
    public function boot()
    {
        $this->publishFiles();

        $this->registerCommands();

        $this->loadRoutes();
    }

    public function publishFiles()
    {
        $this->publishes([
            __DIR__.'/Http/Kernel.php.stub'        => base_path('app/Http/Kernel.php'),
            __DIR__.'/../config/tenancy.php.stub'  => config_path('tenancy.php'),
        ], 'waygou-multi-tenant-overrides');
    }

    protected function registerCommands()
    {
        // Command example.
        $this->commands([
            \Waygou\MultiTenant\Console\Commands\CreateTenant::class,
            \Waygou\MultiTenant\Console\Commands\DeleteTenant::class,
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
