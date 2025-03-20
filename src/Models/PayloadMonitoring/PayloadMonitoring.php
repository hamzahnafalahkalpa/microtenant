<?php

namespace Hanafalah\MicroTenant\Models\PayloadMonitoring;

use Hanafalah\LaravelSupport\Models\PayloadMonitoring\PayloadMonitoring as SupportPayloadMonitoring;
use Hanafalah\MicroTenant\Concerns\Models\CentralTenantConnection;

class PayloadMonitoring extends SupportPayloadMonitoring
{
    use CentralTenantConnection;
}
