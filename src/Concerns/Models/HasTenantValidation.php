<?php

declare(strict_types=1);

namespace Hanafalah\MicroTenant\Concerns\Models;

use Hanafalah\MicroTenant\Scopes\UseTenantValidation;

trait HasTenantValidation
{
    public static function bootHasTenantValidation()
    {
        static::addGlobalScope(new UseTenantValidation);
        static::creating(function ($query) {
            $query->whenTenantCreation();
        });
    }

    public function whenTenantCreation(){
        if (!isset($this->tenant_id)) $this->tenant_id = \tenancy()->tenant->getKey();
    }

    public function initializeHasTenantValidation()
    {
        $this->mergeFillable([
            'tenant_id'
        ]);
    }
}
