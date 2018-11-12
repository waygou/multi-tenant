<?php

namespace Waygou\MultiTenant\Console\Commands;

use Hyn\Tenancy\Database\Connection;
use Illuminate\Console\Command;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Hash;
use PleskX\Api\Client;
use Waygou\MultiTenant\Models\Tenant;
use Waygou\MultiTenant\Notifications\TenantCreated;

class __CreateTenant extends Command
{
    use Notifiable;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'tenant:create';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Easily create new tenant with redirect, https, maintenance options. Also with an administrator account.';

    /**
     * Application base URL.
     *
     * @var string
     */
    private $baseURL;

    /**
     * Database connection.
     *
     * @var string
     */
    private $connection;

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();

        $this->baseURL = env('APP_URL_BASE');

        $this->connection = app(Connection::class);
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $this->info('Provide information to create new tenant.');

        $fqdn = $this->fqdn();
        $redirect = $this->redirect();
        $https = $this->forceHttps();
        $maintenance = $this->underMaintenance();
        $name = $this->value('administrator name');
        $email = $this->value('administrator email');

        if (!$this->confirmData($fqdn, $redirect, $https, $maintenance, $name, $email)) {
            $this->error('Process terminated.');

            return false;
        }

        $this->info('Creating tenant, please wait...');
        $this->output->progressStart(2);
        $subdomain = $fqdn.'.'.$this->baseURL;

        // Create a Plesk database and a Plesk username based on the fqdn.
        $username = env('PLESK_ADMIN_USERNAME');
        $password = env('PLESK_ADMIN_PASSWORD');

        $client = new Client('plesk.waygou.com');
        $client->setCredentials($username, $password);

        // Create Tenant database (name=fqdn).
        $database = $client->database()->create([
            'webspace-id'  => 31, // Xheetah.com
            'name'         => $fqdn,
            'type'         => 'mysql',
            'db-server-id' => 1,
        ]);

        // Load the new tenant password in session.
        $dbUserPassword = Hash::make(str_random(8));
        @session_start();
        $_SESSION['tenant_db_user_password'] = $dbUserPassword;

        // Database user creation for the current Tenant database.
        $user = $client->database()->createUser([
            'db-id'    => $database->id,
            'login'    => $fqdn,
            'password' => $dbUserPassword,
            'role'     => 'readWrite',
        ]);

        $website = Tenant::registerTenant($subdomain, $redirect, $https, $maintenance, $fqdn);

        $this->connection->set($website);
        $this->output->progressAdvance();

        // For testing purposes.
        $adminPassword = 'password';

        Tenant::registerAdmin($name, $adminPassword, $email)->notify(new TenantCreated($subdomain));
        $this->output->progressFinish();

        $this->info('Tenant created!');
        $this->info("Tenant address: {$fqdn}.{$this->baseURL}");
        $this->info("Administrator {$email} can sign in, using password: {$adminPassword}");
        $this->info("Admin {$email} has been invited!");
    }

    /**
     * @return string
     */
    private function fqdn()
    {
        $value = $this->ask('Please enter tenant name');

        if (Tenant::tenantExists($value)) {
            $this->error("Tenant '{$value}.{$this->baseURL}' already exists, please choose another name.");

            return $this->fqdn();
        }

        if (empty($value)) {
            $this->error('Tenant name cannot be empty.');

            return $this->fqdn();
        }

        return $value;
    }

    /**
     * @return bool|string
     */
    private function redirect()
    {
        if ($this->confirm('You do want to redirect this tenant?')) {
            $value = $this->ask('Please enter where you want to redirect (ex.: http://tenant.example.com)', false);

            return $value;
        }

        return false;
    }

    /**
     * @return bool
     */
    private function forceHttps()
    {
        if ($this->confirm('You do want to force https?')) {
            return true;
        }

        return false;
    }

    /**
     * @return bool|string
     */
    private function underMaintenance()
    {
        if ($this->confirm('Is tenant under maintenance?')) {
            $value = $this->ask('Since when? Please provide full date and time (ex.: 2018-01-01 12:00:00)');

            return $value;
        }

        return false;
    }

    /**
     * @param $name
     *
     * @return string
     */
    private function value($name)
    {
        $value = $this->ask("Please enter {$name}");

        if (empty($value)) {
            $this->error("{$name} cannot be empty.");

            return $this->value($name);
        }

        return $value;
    }

    /**
     * @param $fqdn
     * @param $redirect
     * @param $https
     * @param $maintenance
     * @param $name
     * @param $email
     */
    private function confirmData($fqdn, $redirect, $https, $maintenance, $name, $email)
    {
        $this->info('Tenant information');
        $this->info('------------');
        $this->info("Tenant FQDN: {$fqdn}.{$this->baseURL}");
        if ($redirect) {
            $this->info("Redirect: {$redirect}");
        }
        if ($https) {
            $this->info("Force HTTPS: {$https}");
        }
        if ($maintenance) {
            $this->info("Under maintenance: {$maintenance}");
        }
        $this->info('');
        $this->info("Administrator name: {$name}");
        $this->info("Administrator email: {$email}");

        if ($this->confirm('Do you want to create a tenant with this data?')) {
            return true;
        }

        return false;
    }
}
