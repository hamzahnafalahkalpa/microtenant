<?php

namespace Hanafalah\MicroTenant\Models\Permission;

use Hanafalah\LaravelPermission\Models\Permission\ModelHasPermission as PermissionModelHasPermission;
use Hanafalah\MicroTenant\Concerns\Models\CentralAppConnection;

class ModelHasPermission extends PermissionModelHasPermission
{
    use CentralAppConnection;
}
