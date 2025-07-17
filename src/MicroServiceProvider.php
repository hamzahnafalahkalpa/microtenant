<?php

declare(strict_types=1);

namespace Hanafalah\MicroTenant;

use Hanafalah\LaravelSupport;
use Hanafalah\LaravelSupport\Concerns\ServiceProvider\HasMultipleEnvironment;
use Illuminate\Support\Str;
use Hanafalah\MicroTenant\Facades\MicroTenant;

abstract class MicroServiceProvider extends LaravelSupport\Providers\BaseServiceProvider
{
    use Concerns\Providers\HasProviderInjection;
    use HasMultipleEnvironment;

    // protected string $__migration_base_path   = 'database/migrations';

    protected function dir(): string{
        return __DIR__ . '/';
    }

    protected function overrideTenantConfig(): self
    {
        MicroTenant::overrideTenantConfig();
        return $this;
    }

    protected function overrideLaravelSupportConfig(): self{
        $micro_tenant       = $this->__config['micro-tenant'];
        $payload_monitoring = $micro_tenant['payload_monitoring'];
        config()->set('laravel-support.payload-monitoring', $payload_monitoring);
        return $this;
    }

    protected function overrideMergePackageConfig(): self{
        $micro_tenant = $this->__config['micro-tenant'];
        if ($micro_tenant['enabled']){
            $laravel_package = $micro_tenant['laravel-package-generator'];
            $this->overrideConfig('laravel-package-generator', $laravel_package);
        }
        // $config_patterns = config('laravel-package-generator.patterns');
        // config()->set('laravel-package-generator.patterns', array_merge($config_patterns, $patterns));
        return $this;
    }

    // protected function overrideModuleVersionConfig(): self
    // {
    //     $microservices = $this->__config['micro-tenant']['microservices'];
    //     $app_version   = $microservices['app_version'];
    //     config()->set('module-version.application', $app_version);
    //     return $this;
    // }

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
