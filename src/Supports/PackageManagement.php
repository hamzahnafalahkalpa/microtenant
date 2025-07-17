<?php

namespace Hanafalah\MicroTenant\Supports;

use Hanafalah\LaravelSupport\Concerns\ServiceProvider\HasConfiguration;

abstract class PackageManagement
{
    use HasConfiguration;

    protected $__microtenant_config;

    /**
     * Constructor method for initializing the PackageManagement class.
     *
     * @param Container $app The container instance for dependency injection
     */
    public function __construct(...$args)
    {
        $this->setConfig('micro-tenant', $this->__microtenant_config);
    }
}
