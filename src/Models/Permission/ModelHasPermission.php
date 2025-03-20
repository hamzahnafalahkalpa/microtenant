<?php

namespace Zahzah\MicroTenant\Models\Permission;

use Zahzah\LaravelPermission\Models\Permission\ModelHasPermission as PermissionModelHasPermission;
use Zahzah\MicroTenant\Concerns\Models\CentralAppConnection;

class ModelHasPermission extends PermissionModelHasPermission
{
    use CentralAppConnection;
}

