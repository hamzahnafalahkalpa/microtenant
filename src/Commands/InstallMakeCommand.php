<?php

namespace Hanafalah\MicroTenant\Commands;

use Database\Seeders\Installation\InstallationSeeder;
use Hanafalah\ModuleVersion\Concerns\Commands\Installing\AppInstallPrompt;

class InstallMakeCommand extends EnvironmentCommand
{
    use AppInstallPrompt;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'micro:install';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command ini digunakan untuk installing awal microtenant';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->call('support:install');
        $this->call('moduleversion:install');
        $this->call('feature:install');
        $this->call('stub:install');
        $this->call('module-user:install');
        $this->call('module-workspace:install');
        $this->call('laravel-permission:install');
        $provider = 'Hanafalah\MicroTenant\MicroTenantServiceProvider';

        $this->comment('Installing Microtenant...');
        $this->callSilent('vendor:publish', [
            '--provider' => $provider,
            '--tag'      => 'config'
        ]);
        $this->info('✔️  Created config/microtenant.php');

        $this->callSilent('vendor:publish', [
            '--provider' => $provider,
            '--tag'      => 'seeders'
        ]);
        $this->info('✔️  Seeders published');

        $this->callSilent('vendor:publish', [
            '--provider' => $provider,
            '--tag'      => 'stubs'
        ]);
        $this->info('✔️  Created Stubs/MicroTenantStubs');

        $this->callSilent('vendor:publish', [
            '--provider' => $provider,
            '--tag'      => 'providers'
        ]);
        $this->info('✔️  Created MicroTenantServiceProvider.php');

        $this->callSilent('vendor:publish', [
            '--provider' => $provider,
            '--tag'      => 'migrations',
        ]);

        $this->info('✔️  Created migrations');

        $migrations = $this->setMigrationBasePath(database_path('migrations'))->canMigrate();
        $this->callSilent('migrate', [
            '--path' => $migrations
        ]);

        $this->init();

        $this->call('micro:add-application');

        //RUN INSTALLATION SEEDER
        $this->call('db:seed', [
            '--class' => InstallationSeeder::class
        ]);

        $this->comment('hanafalah/microtenant installed successfully.');
    }

    public function callCustomMethod(): array
    {
        return ['Model'];
    }
}
