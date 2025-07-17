<?php

namespace Hanafalah\MicroTenant\Concerns\Providers;

trait HasProviderInjection
{
    private $__authorization;

    protected function registerTenant(): self
    {
        $this->setAuthorization();

        $tenant_id = request()->tenant_name ?? 1;
        // $this->app->register('Tenants\\Tenant'.$tenant_id.'\\Providers\Tenant'.$tenant_id.'ServiceProvider');
        return $this;
    }

    private function setAuthorization(): self
    {
        $authorization = $this->app['request']->header('Authorization');
        if (empty($authorization)) {
            $authorization = $this->app['request']->header('authorization');
            $this->app['request']->headers->set('Authorization', $authorization);
        }

        $this->__authorization = $authorization;
        return $this;
    }
}
