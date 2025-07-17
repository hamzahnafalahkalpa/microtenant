<?php

namespace Hanafalah\MicroTenant\Commands;

use Hanafalah\LaravelSupport\{
    Commands\EnvironmentCommand as CommandsEnvironmentCommand
};

class EnvironmentCommand extends CommandsEnvironmentCommand
{
    public array $__micro_tenant_config = [];

    protected function init(): self
    {
        //INITIALIZE SECTION
        $this->initConfig()
            ->setConfig('micro-tenant', $this->__micro_tenant_config)
            ->setServices();
        return $this;
    }

    /**
     * Retrieves the path of the package.
     *
     * @return string
     */
    protected function dir(): string
    {
        return __DIR__ . '/../';
    }

    public function callCustomMethod(): array
    {
        return ['Model', 'GeneratorPath'];
    }
}
