<?php

namespace Hanafalah\MicroTenant\Models\Role;

use Hanafalah\LaravelPermission\Models\Role\RoleHasPermission as BaseRoleHasPermission;
use Hanafalah\MicroTenant\Concerns\Models\CentralAppConnection;

class RoleHasPermission extends BaseRoleHasPermission
{
    use CentralAppConnection;
}
