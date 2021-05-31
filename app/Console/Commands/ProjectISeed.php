<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class ProjectISeed extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'project:iseed';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'project iseed';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $tables = [
            'roles',
            'users',
            'model_has_roles',
            'oauth_access_tokens',
            'oauth_auth_codes',
            'oauth_clients',
            'menus',
            'model_has_permissions',
            'model_has_roles',
            'role_has_permissions',
            'permissions',
            'workers'
        ];
        $this->call('iseed', ['tables' => implode(',', $tables), '--force' => true]);
    }
}
