<?php

namespace Zahzah\MicroTenant\Models\Role;

use Zahzah\LaravelPermission\Models\Role\Role as BaseRole;
use Zahzah\MicroTenant\Concerns\Models\CentralAppConnection;

class Role extends BaseRole{
    use CentralAppConnection;
}