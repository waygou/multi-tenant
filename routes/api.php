<?php

use Hyn\Tenancy\Database\Connection;
use Illuminate\Support\Facades\Config;
use PleskX\Api\Client;
use Waygou\MultiTenant\Models\Tenant;
use Waygou\MultiTenant\Notifications\TenantCreated;

//Route::middleware(['api', 'sameip:' . env('SERVER_IP_ADDRESS')])
Route::middleware(['api'])
     ->get('create-tenant/{fqdn}/{name}/{email}', function ($fqdn, $name, $email) {
        if (empty($fqdn) || empty($name) || empty($email)) {
            return response()->json(['status' => 'error', 'message' => 'Insufficient parameters.']);
        }

         $baseURL = env('APP_URL_BASE');
         $connection = app(Connection::class);

         $subdomain = "$fqdn.$baseURL";

         // Verify if sub domain already exists.
        if (Tenant::exists($subdomain)) {
            return response()->json(['status' => 'error', 'message' => 'Sub domain already exists.']);
        }

         // Local environment?
        if (config('app.env') == 'local') {
            // Change hyn/multi-tenant configuration  variables due to
            // local environment.
            config(['tenancy.db.auto-create-tenant-database' => true]);
            config(['tenancy.db.auto-create-tenant-database-user' => true]);
        } else {
            // Create a Plesk database and a Plesk username based on the fqdn.
            $username = env('PLESK_ADMIN_USERNAME');
            $password = env('PLESK_ADMIN_PASSWORD');

            $client = new Client('plesk.waygou.com');
            $client->setCredentials($username, $password);

            // Create Tenant database (name=fqdn).
            $database = $client->database()->create([
            'webspace-id'  => 31, // xheetah.com
            'name'         => $fqdn,
            'type'         => 'mysql',
            'db-server-id' => 1,
            ]);

            // Database user creation for the current Tenant database.
            $user = $client->database()->createUser([
               'db-id'    => $database->id,
               'login'    => $fqdn,
               'password' => md5('Password1#!'),
               'role'     => 'readWrite',
            ]);
        }

         $website = Tenant::register($subdomain, false, true, false, $fqdn);

         // Set the new Tenant identification manually.
         $environment = $this->app->make(\Hyn\Tenancy\Environment::class);
         $environment->tenant($website);

         // Create an admin user inside the new Tenant (waygou/xheetah package).
         // Add user.
         // Add profile to user.
         /*
         $adminPassword = 'password';
         Tenant::registerAdmin($name, $adminPassword, $email)->notify(new TenantCreated($subdomain));
         */

         return response()->json(['status' => 'ok']);
     });
