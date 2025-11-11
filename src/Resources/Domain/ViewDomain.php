<?php

namespace Hanafalah\MicroTenant\Resources\Domain;

use Illuminate\Http\Request;
use Hanafalah\LaravelSupport\Resources\ApiResource;

class ViewDomain extends ApiResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'domain' => $this->domain,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at
        ];
    }
}