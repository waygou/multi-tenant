<?php

namespace Waygou\MultiTenant\Models;

use Carbon\Carbon;
use Hyn\Tenancy\Contracts\Repositories\HostnameRepository;
use Hyn\Tenancy\Contracts\Repositories\WebsiteRepository;
use Hyn\Tenancy\Models\Hostname;
use Hyn\Tenancy\Models\Website;

/**
 * @property Website website
 * @property Hostname hostname
 * @property User admin
 */
class Tenant
{
    public function __construct()
    {
    }

    public static function deleteTenant($fqdn)
    {
        if ($hostname = Hostname::where('fqdn', $fqdn)->with(['website'])->firstOrFail()) {
            $website = $hostname->website->first();
            app(HostnameRepository::class)->delete($hostname, true);
            app(WebsiteRepository::class)->delete($website, true);
        }
    }

    public static function registerTenant($subdomain, $redirect, $https, $maintenance, $fqdn)
    {
        $website = new Website();
        $website->uuid = $subdomain; // Easy to read from the website table.

        app(WebsiteRepository::class)->create($website);

        $hostname = new Hostname();
        $hostname->fqdn = $fqdn;
        if ($redirect) {
            $hostname->redirect_to = $redirect;
        }
        if ($https) {
            $hostname->force_https = $https;
        }
        if ($maintenance) {
            $hostname->under_maintenance_since = Carbon::parse($maintenance)->format('Y-m-d H:i:s');
        }

        $hostname->website()->associate($website);
        app(HostnameRepository::class)->attach($hostname, $website);

        return $website;
    }

    public static function registerAdmin($name, $password, $email)
    {
        $admin = User::create(['name' => $name, 'email' => $email, 'password' => bcrypt($password)]);

        return $admin;
    }

    public static function tenantExists($fqdn)
    {
        return Hostname::where('fqdn', $fqdn)->exists();
    }
}
