<?php

namespace Hanafalah\MicroTenant\Resources\Tenant;

use Illuminate\Http\Request;
use Hanafalah\LaravelSupport\Resources\ApiResource;

class ViewTenant extends ApiResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'parent_id' => $this->parent_id,
            'name' => $this->name,
            'flag' => $this->flag,
            'domain_id' => $this->domain_id
        ];
    }
}