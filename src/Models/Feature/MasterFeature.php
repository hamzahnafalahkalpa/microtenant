<?php

namespace Hanafalah\MicroTenant\Models\Feature;

use Hanafalah\MicroTenant\Concerns\Models\CentralConnection;
use Hanafalah\LaravelFeature\Models\Feature\MasterFeature as FeatureMasterFeature;

class MasterFeature extends FeatureMasterFeature
{
    use CentralConnection;
}
