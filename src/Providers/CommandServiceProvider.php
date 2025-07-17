<?php

declare(strict_types=1);

namespace Hanafalah\MicroTenant\Providers;

use Illuminate\Support\ServiceProvider;
use Hanafalah\MicroTenant\Commands as Commands;

class CommandServiceProvider extends ServiceProvider
{
    private $commands = [
        Commands\Impersonate\ImpersonateCacheCommand::class,
        Commands\Impersonate\ImpersonateMigrateCommand::class,
        Commands\InstallMakeCommand::class,
        Commands\AddTenantCommand::class,
        Commands\AddPackageCommand::class
    ];


    public function register()
    {
        $this->commands(config('micro-tenant.commands', $this->commands));
    }
    /**
     * Get the services provided by the provider.
     *
     * @return array
     */

    public function provides()
    {
        return $this->commands;
    }
}
