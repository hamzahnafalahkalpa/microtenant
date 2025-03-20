<?php

namespace Hanafalah\MicroTenant\Models\Regional;

use Hanafalah\ModuleRegional\Models\Regional\Address as BaseAddress;
use Hanafalah\MicroTenant\Concerns\Models\CentralConnection;

class Address extends BaseAddress
{
  use CentralConnection;
}
