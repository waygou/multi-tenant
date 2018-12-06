<?php

namespace Waygou\MultiTenant\Generators\Database;

use Hyn\Tenancy\Contracts\Website;
use Illuminate\Contracts\Foundation\Application;
use Hyn\Tenancy\Contracts\Database\PasswordGenerator;

class PleskPasswordGenerator implements PasswordGenerator
{
    protected $app;

    public function __construct(Application $app)
    {
        $this->app = $app;
    }

    public function generate(Website $website) : string
    {
        // Generate a new password per hyn/multi-tenant defaul algorithm.
        return md5(env('PLESK_TENANT_DB_PASSWORD'));
    }
}
