<?php

namespace Zahzah\MicroTenant\Models\ApiAccess;

use Zahzah\MicroTenant\Concerns\Models\CentralConnection;
use Zahzah\ApiHelper\Models\PersonalAccessToken as ModulePersonalAccessToken;

class PersonalAccessToken extends ModulePersonalAccessToken 
{
    use CentralConnection;
}
