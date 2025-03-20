<?php

namespace Zahzah\MicroTenant\Models\Schema;

use Zahzah\MicroTenant\Concerns\Models\CentralConnection;
use Zahzah\ModuleVersion\Models\Schema\Schema as SchemaSchema;

class Schema extends SchemaSchema{
    use CentralConnection;
    
    //EIGER SECTION
    public function modelHasFeature(){return $this->morphOneModel('ModelHasFeature','model');}
    public function modelHasFeatures(){return $this->morphManyModel('ModelHasFeature','model');}
    //END EIGER SECTION
}