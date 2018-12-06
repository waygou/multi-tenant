<?php

namespace Waygou\MultiTenant\Console\Commands;

use Illuminate\Console\Command;
use Waygou\Xheetah\Models\User;
use Waygou\MultiTenant\Models\Tenant;
use Waygou\MultiTenant\Services\TenantProvision;

class CreateTenant extends Command
{
    protected $signature = 'tenant:create {subdomain}
                                          {--autodb : Meaning the database will be automatically created by the hyn/multi-tenant package. }
                                          {--forcehttps : Should force https. }';

    protected $description = 'Create a new tenant subdomain straight away.';

    public function __construct()
    {
        parent::__construct();
    }

    public function handle()
    {
        $this->lineSpace();
        $this->info('-----------------------');
        $this->info('--- Tenant creation ---');
        $this->info('-----------------------');
        $this->lineSpace();

        $subdomain = $this->argument('subdomain');
        $fqdn = $subdomain.'.'.config('app.url_base');
        $autoDbCreation = $this->option('autodb');
        $forceHttps = $this->option('forcehttps');

        $this->line("Subdomain: $fqdn");
        $this->line('DB auto-creation: '.bool_str($autoDbCreation));

        $this->lineSpace();

        $this->line('Creating tenant ...');
        $website = TenantProvision::createTenant($subdomain, $autoDbCreation, $forceHttps);

        if (! $website) {
            $this->error(TenantProvision::$error);

            return;
        }

        /*
        $this->line('Creating Xheetah admin user (admin@live.com) ...');
        $environment = app()->make(\Hyn\Tenancy\Environment::class);
        $environment->tenant($website);

        $admin = User::create(['name' => "Admin ($subdomain)",
                               'email' => 'admin@live.com',
                               'password' => bcrypt('Password1#!')]);
        */
        $this->info("All done! Tenant database and user created. You can try it using the url: $fqdn");
        $this->lineSpace();
    }

    private function lineSpace($num = 3)
    {
        for ($i = 0; $i < $num; $i++) {
            $this->info('');
        }
    }
}
