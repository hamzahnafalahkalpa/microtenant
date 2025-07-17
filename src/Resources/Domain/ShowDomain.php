<?php

namespace Hanafalah\Microtenant\Resources\Domain;

use Illuminate\Http\Request;

class ShowDomain extends ViewDomain
{
    public function toArray(Request $request): array
    {
        $arr = [
        ];
        $arr = $this->mergeArray(parent::toArray($request), $arr);
        return $arr;
    }
}