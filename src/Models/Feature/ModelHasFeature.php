<?php

namespace Hanafalah\MicroTenant\Models\Feature;

use Hanafalah\MicroTenant\Concerns\Models\CentralConnection;
use Hanafalah\LaravelFeature\Models\Feature\ModelHasFeature as FeatureModelHasFeature;

class ModelHasFeature extends FeatureModelHasFeature
{
    use CentralConnection;

    //EIGER SECTION
    public function tenant()
    {
        return $this->belongsToModel('Tenant', 'model_id', 'id')
            ->where('model_type', (new $this->getClassModel('Tenant'))->getMorphClass());
    }
    //END EIGER SECTION
}
