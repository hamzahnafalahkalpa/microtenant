<?php

namespace Zahzah\MicroTenant\Models\PayloadMonitoring;

use Zahzah\LaravelSupport\Models\PayloadMonitoring\PayloadMonitoring as SupportPayloadMonitoring;
use Zahzah\MicroTenant\Concerns\Models\CentralTenantConnection;

class PayloadMonitoring extends SupportPayloadMonitoring
{
    use CentralTenantConnection;
}
