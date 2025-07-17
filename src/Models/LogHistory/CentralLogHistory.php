<?php

namespace Hanafalah\MicroTenant\Models\LogHistory;

use Hanafalah\MicroTenant\Concerns\Models\CentralConnection;
use Hanafalah\LaravelSupport\Models\LogHistory\LogHistory;

class CentralLogHistory extends LogHistory
{
    use CentralConnection;

    protected $table = 'log_histories';
}
