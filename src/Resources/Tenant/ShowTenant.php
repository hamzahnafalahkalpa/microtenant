<?php

namespace Hanafalah\MicroTenant\Resources\Tenant;

use Illuminate\Http\Request;

class ShowTenant extends ViewTenant
{
    public function toArray(Request $request): array
    {
        $arr = [
        ];
        $arr = $this->mergeArray(parent::toArray($request), $arr);
        return $arr;
    }
}