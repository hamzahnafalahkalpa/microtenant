<?php

namespace Hanafalah\MicroTenant\Concerns\Tenant;

trait HasTenant
{
    /**
     * Initialize the trait.
     *
     * This method bootstraps the initial state of the model, and is
     * called when the model is instantiated.
     *
     * @return void
     */
    public function initializeHasProps()
    {
        $this->mergeFillable([
            $this->TenantModel()->getForeignKey()
        ]);
    }

    //EIGER SECTION
    public function tenant()
    {
        return $this->belongsToModel('Tenant');
    }
    //END EIGER SECTION
}
