<?php

namespace Hanafalah\MicroTenant\Models\Role;

use Hanafalah\LaravelPermission\Models\Role\Role as BaseRole;
use Hanafalah\MicroTenant\Concerns\Models\CentralAppConnection;

class Role extends BaseRole
{
    use CentralAppConnection;
}
