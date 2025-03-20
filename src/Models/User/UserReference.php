<?php

namespace Zahzah\MicroTenant\Models\User;

use Zahzah\LaravelPermission\Concerns\HasRole;
use Zahzah\MicroTenant\Concerns\Models\CentralConnection;
use Zahzah\ModuleUser\Models\User\UserReference as ModelsUserReference;
use Zahzah\MicroTenant\Concerns\Tenant;

class UserReference extends ModelsUserReference{
    use CentralConnection, Tenant\HasTenant, Tenant\HasCentralTenant, HasRole;
}