<?php

namespace Zahzah\MicroTenant\Models\Role;

use Zahzah\LaravelPermission\Models\Role\ModelHasRole as RoleModelHasRole;
use Zahzah\MicroTenant\Concerns\Models\CentralAppConnection;

class ModelHasRole extends RoleModelHasRole{
    use CentralAppConnection;
}