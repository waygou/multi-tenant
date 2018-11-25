<?php

namespace Waygou\MultiTenant\Generators\Database;

use Hyn\Tenancy\Contracts\Database\PasswordGenerator;
use Hyn\Tenancy\Contracts\Website;
use Illuminate\Contracts\Foundation\Application;

class PleskPasswordGenerator implements PasswordGenerator
{
    protected $app;

    public function __construct(Application $app)
    {
        $this->app = $app;
    }

    public function generate(Website $website) : string
    {
        info('Returning password ' . md5(env('PLESK_TENANT_DB_PASSWORD')));
        // Generate a new password per hyn/multi-tenant defaul algorithm.
        return md5(env('PLESK_TENANT_DB_PASSWORD'));
    }
}
