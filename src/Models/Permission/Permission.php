<?php

namespace Hanafalah\MicroTenant\Models\Permission;

use Hanafalah\LaravelPermission\Models\Permission\Permission as ModulePermission;
use Hanafalah\MicroTenant\Concerns\Models\CentralAppConnection;

class Permission extends ModulePermission
{
    use CentralAppConnection;
}
