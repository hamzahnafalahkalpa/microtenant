<?php

namespace Hanafalah\MicroTenant\Facades;

use Illuminate\Support\Facades\Facade;
use Hanafalah\MicroTenant\Contracts\MicroTenant as ContractsMicroTenant;
use Hanafalah\MicroTenant\MicroTenant as MicroTenantMicroTenant;

/**
 * @property static $microtenant
 * @method static self impersonate(Tenant|string|int $tenant, ?bool $recurse = true)
 */
class MicroTenant extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return MicroTenantMicroTenant::class;
    }
}
