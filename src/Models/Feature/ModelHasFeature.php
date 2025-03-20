<?php

namespace Zahzah\MicroTenant\Models\Feature;

use Zahzah\MicroTenant\Concerns\Models\CentralConnection;
use Zahzah\LaravelFeature\Models\Feature\ModelHasFeature as FeatureModelHasFeature;

class ModelHasFeature extends FeatureModelHasFeature{
    use CentralConnection;

    //EIGER SECTION
    public function tenant(){
        return $this->belongsToModel('Tenant','model_id','id')
                    ->where('model_type', (new $this->getClassModel('Tenant'))->getMorphClass());
    }
    //END EIGER SECTION
}
