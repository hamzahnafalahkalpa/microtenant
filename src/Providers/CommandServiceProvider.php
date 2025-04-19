<?php

declare(strict_types=1);

namespace Hanafalah\MicroTenant\Providers;

use Illuminate\Support\ServiceProvider;
use Hanafalah\MicroTenant\Commands as Commands;

class CommandServiceProvider extends ServiceProvider
{
    private $commands = [
        Commands\InstallMakeCommand::class,
        Commands\AddTenantCommand::class,
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
