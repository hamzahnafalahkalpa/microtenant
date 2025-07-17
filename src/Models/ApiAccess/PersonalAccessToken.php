<?php

namespace Hanafalah\MicroTenant\Models\ApiAccess;

use Hanafalah\MicroTenant\Concerns\Models\CentralConnection;
use Hanafalah\ApiHelper\Models\PersonalAccessToken as ModulePersonalAccessToken;

class PersonalAccessToken extends ModulePersonalAccessToken
{
    use CentralConnection;
}
