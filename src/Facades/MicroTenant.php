<?php

namespace Hanafalah\MicroTenant\Facades;

use Illuminate\Support\Facades\Facade;
use Hanafalah\MicroTenant\Contracts\MicroTenant as ContractsMicroTenant;


/**
 * @property static $microtenant
 * @method static self impersonate(Tenant|string|int $tenant)
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
        return ContractsMicroTenant::class;
    }
}
