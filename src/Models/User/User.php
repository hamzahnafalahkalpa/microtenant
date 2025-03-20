<?php

namespace Zahzah\MicroTenant\Models\User;

use Zahzah\ApiHelper\Concerns\Token\HasApiTokens;
use Zahzah\MicroTenant\Concerns\Models\CentralConnection;
use Zahzah\ModuleUser\Models\User\User as ModuleUser;

class User extends ModuleUser{
    use HasApiTokens, CentralConnection;
}
