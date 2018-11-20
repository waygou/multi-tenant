<?php

namespace Waygou\MultiTenant\Services;

use PleskX\Api\Client;
use Waygou\MultiTenant\Models\Tenant;

class TenantProvision
{
    public static $error = '';

    public static function configureForAutoDbProvisioning()
    {
        config(['tenancy.db.auto-create-tenant-database' => true]);
        config(['tenancy.db.auto-create-tenant-database-user' => true]);
    }

    public static function configureForManualDbProvisioning()
    {
        config(['tenancy.db.auto-create-tenant-database' => false]);
        config(['tenancy.db.auto-create-tenant-database-user' => false]);
        config(['tenancy.db.password-generator' => Waygou\MultiTenant\Generators\Database\PleskPasswordGenerator::class]);
    }

    public static function createPleskDatabase($name)
    {
        // Create a Plesk database and a Plesk username based on the fqdn.
        $username = env('PLESK_ADMIN_USERNAME');
        $password = env('PLESK_ADMIN_PASSWORD');

        $client = new Client('plesk.waygou.com');
        $client->setCredentials($username, $password);

        // Create Tenant database (name=fqdn).
        $database = $client->database()->create([
            'webspace-id'  => env('PLESK_WEBSPACE_ID'), // xheetah.com
            'name'         => $name,
            'type'         => 'mysql',
            'db-server-id' => 1,
        ]);

        return $database;
    }

    public static function createPleskDatabaseUser($name, $databaseId = null)
    {
        // Create a Plesk database and a Plesk username based on the fqdn.
        $username = env('PLESK_ADMIN_USERNAME');
        $password = env('PLESK_ADMIN_PASSWORD');

        $client = new Client('plesk.waygou.com');
        $client->setCredentials($username, $password);

        // Database user creation for the current Tenant database.
        $user = $client->database()->createUser([
            'db-id'    => $databaseId,
            'login'    => $name,
            'password' => md5(env('PLESK_TENANT_DB_PASSWORD')),
            'role'     => 'readWrite',
        ]);
    }

    public static function createTenant($subdomain, $autoDbCreation, $forceHttps = true)
    {
        $fqdn = $subdomain.'.'.config('app.url_base');

        if (Tenant::exists($fqdn)) {
            static::$error = 'Error! Subdomain already exists!';
            return;
        }

        if ($autoDbCreation) {
            // DB + User created automatically by the hyn/multi-tenant.
            // Configure tenancy configuration file for default database provisioning.
            static::configureForAutoDbProvisioning();
        } else {
            // DB + User manually created using the Plesk XML RPC Api.
            // Configure tenancy configuration file for manual database provisioning.
            static::configureForManualDbProvisioning();

            // Create a Plesk database and a Plesk username based on the fqdn.
            $database = static::createPleskDatabase($subdomain);
            static::createPleskDatabaseUser($subdomain, $database->id);
        }

        $website = Tenant::register($subdomain, false, $forceHttps, false, $fqdn);

        return true;
    }
}
