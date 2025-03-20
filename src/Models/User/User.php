<?php

namespace Hanafalah\MicroTenant\Models\User;

use Hanafalah\ApiHelper\Concerns\Token\HasApiTokens;
use Hanafalah\MicroTenant\Concerns\Models\CentralConnection;
use Hanafalah\ModuleUser\Models\User\User as ModuleUser;

class User extends ModuleUser
{
    use HasApiTokens, CentralConnection;
}
