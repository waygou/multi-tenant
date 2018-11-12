<?php

namespace Waygou\MultiTenant\Services;

use PleskX\Api\Client;

class TenantProvision
{
    public static function configureForAutoDbProvisioning()
    {
        config(['tenancy.db.auto-create-tenant-database' => true]);
        config(['tenancy.db.auto-create-tenant-database-user' => true]);
        config(['tenancy.db.password-generator' => Hyn\Tenancy\Generators\Database\DefaultPasswordGenerator::class]);
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
            'webspace-id' => env('PLESK_WEBSPACE_ID'), // xheetah.com
            'name' => $name,
            'type' => 'mysql',
            'db-server-id' => 1
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
            'db-id' => $databaseId,
            'login' => $name,
            'password' => md5(env('PLESK_TENANT_DB_PASSWORD')),
            'role' => 'readWrite'
        ]);
    }
}
