<?php

namespace Waygou\MultiTenant\Models;

use Carbon\Carbon;
use Hyn\Tenancy\Models\Website;
use Hyn\Tenancy\Models\Hostname;
use Hyn\Tenancy\Contracts\Repositories\WebsiteRepository;
use Hyn\Tenancy\Contracts\Repositories\HostnameRepository;

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

    public static function delete($fqdn)
    {
        if ($hostname = Hostname::where('fqdn', $fqdn)->with(['website'])->firstOrFail()) {
            $website = $hostname->website->first();
            app(HostnameRepository::class)->delete($hostname, true);
            app(WebsiteRepository::class)->delete($website, true);
        }
    }

    public static function register($subdomain, $redirect, $https, $maintenance, $fqdn)
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

    public static function exists($fqdn)
    {
        return Hostname::where('fqdn', $fqdn)->exists();
    }
}
