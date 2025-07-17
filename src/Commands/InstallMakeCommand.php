<?php

namespace Hanafalah\MicroTenant\Commands;

class InstallMakeCommand extends EnvironmentCommand
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'micro:install {--skip-generate}';

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
        $provider = 'Hanafalah\MicroTenant\MicroTenantServiceProvider';

        $this->comment('Installing Microtenant...');
        $this->callSilent('vendor:publish', [
            '--provider' => $provider,
            '--tag'      => 'config'
        ]);
        $this->info('✔️  Created config/microtenant.php');

        $this->callSilent('vendor:publish', [
            '--provider' => $provider,
            '--tag'      => 'migrations',
        ]);

        $this->call('migrate');

        $this->call('support:install');
        $this->call('module-user:install');
        $this->call('module-workspace:install');
        $this->call('laravel-permission:install');
        $this->call('generator:install');
        $this->call('helper:install',[
            '--skip-generate' => $this->option('skip-generate') ?? false
        ]);

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

        $this->info('✔️  Created migrations');

        $this->comment('hanafalah/microtenant installed successfully.');
    }

    public function callCustomMethod(): array
    {
        return ['Model'];
    }
}
