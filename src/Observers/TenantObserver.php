<?php

declare(strict_types=1);

namespace Zahzah\MicroTenant\Observers;

use Zahzah\MicroTenant\Models\Tenant\Tenant;

class TenantObserver
{
    public function creating(Tenant $tenant)
    {
        //CREATE DATABASE
    }
}