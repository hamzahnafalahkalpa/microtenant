<?php

declare(strict_types=1);

namespace Hanafalah\MicroTenant\Observers;

use Hanafalah\MicroTenant\Models\Tenant\Tenant;

class TenantObserver
{
    public function creating(Tenant $tenant)
    {
        //CREATE DATABASE
    }
}
