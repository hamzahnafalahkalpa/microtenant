<?php

namespace Zahzah\MicroTenant\Models\Role;

use Zahzah\LaravelPermission\Models\Role\RoleHasPermission as BaseRoleHasPermission;
use Zahzah\MicroTenant\Concerns\Models\CentralAppConnection;

class RoleHasPermission extends BaseRoleHasPermission{
    use CentralAppConnection;
}