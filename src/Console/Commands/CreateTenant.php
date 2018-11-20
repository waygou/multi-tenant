<?php

namespace Waygou\MultiTenant\Console\Commands;

use Illuminate\Console\Command;
use Waygou\MultiTenant\Models\Tenant;
use Waygou\MultiTenant\Services\TenantProvision;
use Waygou\Xheetah\Models\User;

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
        $result = TenantProvision::createTenant($subdomain, $autoDbCreation, $forceHttps);

        if (!$result) {
            $this->error(TenantProvision::$error);
            return;
        }

        /*

        // Verify if subdomain already exists.
        if (Tenant::exists($fqdn)) {
            $this->error('Error! Subdomain already exists! Aborting ...');

            return;
        }

        if ($autoDbCreation) {
            // DB + User created automatically by the hyn/multi-tenant.
            // Configure tenancy configuration file for default database provisioning.
            $this->line('Changing tenancy.php configuration for auto-db provisioning ...');
            TenantProvision::configureForAutoDbProvisioning();
        } else {
            // DB + User manually created using the Plesk XML RPC Api.
            // Configure tenancy configuration file for manual database provisioning.
            TenantProvision::configureForManualDbProvisioning();

            // Create a Plesk database and a Plesk username based on the fqdn.
            $this->line('Manual db provisioning selected using Plesk API ...');
            $database = TenantProvision::createPleskDatabase($subdomain);
            TenantProvision::createPleskDatabaseUser($subdomain, $database->id);
        }

        $this->line('Creating tenant ...');
        $website = Tenant::register($subdomain, false, $forceHttps, false, $fqdn);

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
