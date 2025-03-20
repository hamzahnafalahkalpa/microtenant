<?php

declare(strict_types=1);

namespace Zahzah\MicroTenant\Concerns\Models;

use Zahzah\MicroTenant\Scopes\UseTenantValidation;

trait HasTenantValidation
{
    public static function bootHasTenantValidation(){
        static::addGlobalScope(new UseTenantValidation);
        static::creating(function($query){
            if (!isset($query->tenant_id)) $query->tenant_id = \tenancy()->tenant->getKey();
        });
    }

    public function initializeHasTenantValidation(){
        $this->mergeFillable([
            'tenant_id' 
        ]);
    }
}
