<?php

namespace Zahzah\MicroTenant\Models\Regional;

use Zahzah\ModuleRegional\Models\Regional\Address as BaseAddress;
use Zahzah\MicroTenant\Concerns\Models\CentralConnection;

class Address extends BaseAddress
{
  use CentralConnection;
}

