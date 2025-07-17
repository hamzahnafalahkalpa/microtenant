<?php

namespace Hanafalah\MicroTenant\Models\Relation;

use Hanafalah\MicroTenant\Concerns\Models\CentralConnection;
use Hanafalah\LaravelSupport\Models\Relation\ModelHasRelation;

class CentralModelHasRelation extends ModelHasRelation
{
    use CentralConnection;

    protected $table = 'model_has_relations';
}
