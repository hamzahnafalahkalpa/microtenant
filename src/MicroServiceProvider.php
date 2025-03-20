<?php

declare(strict_types=1);

namespace Hanafalah\MicroTenant;

use Hanafalah\LaravelSupport;
use Hanafalah\LaravelSupport\Concerns\ServiceProvider\HasMultipleEnvironment;
use Hanafalah\MicroTenant\Facades\MicroTenant;

abstract class MicroServiceProvider extends LaravelSupport\Providers\BaseServiceProvider
{
    use Concerns\Providers\HasProviderInjection;
    use HasMultipleEnvironment;

    /**
     * Get the base path of the package.
     *
     * @return string
     */
    protected function dir(): string
    {
        return __DIR__ . '/';
    }

    protected function overrideTenantConfig(): self
    {
        MicroTenant::overrideTenantConfig();
        return $this;
    }

    protected function overrideLaravelSupportConfig(): self
    {
        $micro_tenant       = $this->__config['micro-tenant'];
        $payload_monitoring = $micro_tenant['payload_monitoring'];
        config()->set('laravel-support.payload-monitoring', $payload_monitoring);
        return $this;
    }

    protected function overrideModuleVersionConfig(): self
    {
        $microservices = $this->__config['micro-tenant']['microservices'];
        $app_version   = $microservices['app_version'];
        config()->set('module-version.application', $app_version);
        return $this;
    }

    protected function overrideAuthConfig(): self
    {
        $user = $this->UserModelInstance();
        config([
            'auth.guards.api.provider' => $user,
            'auth.providers.users.model' => $user
        ]);
        return $this;
    }
}
