<?php

namespace Hanafalah\MicroTenant\Models\ApiAccess;

use Hanafalah\MicroTenant\Concerns\Models\CentralConnection;
use Hanafalah\ApiHelper\Models\ApiAccess as ModelsApiAccess;
use Hanafalah\MicroTenant\Models;

class ApiAccess extends ModelsApiAccess
{
    use CentralConnection;

    //EIGER SECTION
    public function modelHasRelation()
    {
        return $this->morphOneModel('ModelHasRelation', 'model');
    }
    public function reference()
    {
        return $this->morphTo();
    }
    //END EIGER SECTION
}
