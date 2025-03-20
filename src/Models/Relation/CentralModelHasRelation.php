<?php

namespace Zahzah\MicroTenant\Models\Relation;

use Zahzah\MicroTenant\Concerns\Models\CentralConnection;
use Zahzah\LaravelSupport\Models\Relation\ModelHasRelation;

class CentralModelHasRelation extends ModelHasRelation{
    use CentralConnection;

    protected $table = 'model_has_relations';

}