<?php

declare(strict_types=1);

namespace Zahzah\MicroTenant\Models\Tenant;

use Illuminate\Support\Str;
use Zahzah\MicroTenant\Concerns\Models\CentralTenantConnection;

class CentralTenant extends Tenant{
    use CentralTenantConnection;

    protected static function booted(): void{
        parent::booted();
        static::creating(function($query){
            if (!isset($query->uuid)) $query->uuid = Str::orderedUuid();
            if (!isset($query->flag)) $query->flag = parent::FLAG_CENTRAL_TENANT;
        });
    }

}