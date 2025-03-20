<?php

namespace Zahzah\MicroTenant\Models\ApiAccess;

use Zahzah\MicroTenant\Concerns\Models\CentralConnection;
use Zahzah\ApiHelper\Models\ApiAccess as ModelsApiAccess;
use Zahzah\MicroTenant\Models;

class ApiAccess extends ModelsApiAccess
{
    use CentralConnection;

    //EIGER SECTION
    public function modelHasRelation(){return $this->morphOneModel('ModelHasRelation','model');}
    public function reference(){return $this->morphTo();}
    //END EIGER SECTION
}
