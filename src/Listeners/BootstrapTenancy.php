<?php

declare(strict_types=1);

namespace Hanafalah\MicroTenant\Listeners;

use Hanafalah\MicroTenant\Contracts\Supports\ConnectionManager as SupportsConnectionManager;
use Hanafalah\MicroTenant\Supports\ConnectionManager;
use Stancl\Tenancy\Events\BootstrappingTenancy;
use Stancl\Tenancy\Events\TenancyBootstrapped;
use Stancl\Tenancy\Events\TenancyInitialized;

class BootstrapTenancy
{
    public function handle(TenancyInitialized $event)
    {
        $tenant = $event->tenancy->tenant;
        event(new BootstrappingTenancy($event->tenancy));

        foreach ($event->tenancy->getBootstrappers() as $bootstrapper) {
            $bootstrapper->bootstrap($event->tenancy->tenant);
        }

        event(new TenancyBootstrapped($event->tenancy));
    }
}
