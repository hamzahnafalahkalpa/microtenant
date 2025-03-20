<?php

namespace Zahzah\MicroTenant\Models\Feature;

use Zahzah\MicroTenant\Concerns\Models\CentralConnection;
use Zahzah\LaravelFeature\Models\Feature\MasterFeature as FeatureMasterFeature;

class MasterFeature extends FeatureMasterFeature{
    use CentralConnection;
}