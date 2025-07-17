<?php

namespace Hanafalah\MicroTenant\Concerns\Tenant;

trait HasCentralTenant
{
    protected static string $__central_foreign_id = 'central_tenant_id';

    /**
     * Initialize the trait.
     *
     * This method bootstraps the initial state of the model, and is
     * called when the model is instantiated.
     *
     * @return void
     */
    public function initializeHasCentralTenant()
    {
        $this->mergeFillable([
            static::$__central_foreign_id,
        ]);
    }

    //EIGER SECTION
    public function centralTenant()
    {
        return $this->belongsToModel('Tenant')->central();
    }
    //END EIGER SECTION
}
