<?php

namespace Hanafalah\MicroTenant\Models\User;

use Hanafalah\LaravelPermission\Concerns\HasRole;
use Hanafalah\MicroTenant\Concerns\Models\CentralConnection;
use Hanafalah\ModuleUser\Models\User\UserReference as ModelsUserReference;
use Hanafalah\MicroTenant\Concerns\Tenant;

class UserReference extends ModelsUserReference
{
    use CentralConnection, Tenant\HasTenant, Tenant\HasCentralTenant, HasRole;
}
