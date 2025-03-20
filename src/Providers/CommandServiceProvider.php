<?php

declare(strict_types=1);

namespace Hanafalah\MicroTenant\Providers;

use Illuminate\Support\ServiceProvider;
use Hanafalah\MicroTenant\Commands as Commands;

class CommandServiceProvider extends ServiceProvider
{
    private $commands = [
        Commands\AddApplicationMakeCommand::class,
        Commands\InstallMakeCommand::class,
        Commands\ApiHelperInstallMakeCommand::class,
        Commands\InterfaceMakeCommand::class,
        Commands\TenantMakeCommand::class,
        Commands\AddInstallationSchemaMakeCommand::class,
        Commands\AddModelSchemaMakeCommand::class,
        Commands\RunSchemaMakeCommand::class,
        Commands\ProviderMakeCommand::class,
        Commands\InstallImpersonateMakeCommand::class,
        Commands\AddSchemaMakeCommand::class,
        Commands\Impersonate\ImpersonateCommand::class,
        Commands\Impersonate\MigrationMakeCommand::class,
        Commands\Impersonate\ModelMakeCommand::class,
        Commands\Impersonate\PolicyMakeCommand::class,
        Commands\Impersonate\RequestMakeCommand::class,
        Commands\Impersonate\ResourceMakeCommand::class,
        Commands\Impersonate\ControllerMakeCommand::class,
        Commands\Impersonate\ConcernMakeCommand::class,
        Commands\Impersonate\MiddlewareMakeCommand::class,
        Commands\Impersonate\ImpersonatePublishCommand::class,
        Commands\Impersonate\ImpersonateAddProviderCommand::class,
        Commands\Impersonate\ImpersonateMigrateCommand::class,
        Commands\Impersonate\ImpersonateSeedCommand::class
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
