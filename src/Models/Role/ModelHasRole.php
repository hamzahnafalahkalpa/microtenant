<?php

namespace Hanafalah\MicroTenant\Models\Role;

use Hanafalah\LaravelPermission\Models\Role\ModelHasRole as RoleModelHasRole;
use Hanafalah\MicroTenant\Concerns\Models\CentralAppConnection;

class ModelHasRole extends RoleModelHasRole
{
    use CentralAppConnection;
}
