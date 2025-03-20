<?php

namespace Zahzah\MicroTenant\Models\Permission;

use Zahzah\LaravelPermission\Models\Permission\Permission as ModulePermission;
use Zahzah\MicroTenant\Concerns\Models\CentralAppConnection;

class Permission extends ModulePermission{
    use CentralAppConnection;
}