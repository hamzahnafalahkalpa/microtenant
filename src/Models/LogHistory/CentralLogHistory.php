<?php

namespace Zahzah\MicroTenant\Models\LogHistory;

use Zahzah\MicroTenant\Concerns\Models\CentralConnection;
use Zahzah\LaravelSupport\Models\LogHistory\LogHistory;

class CentralLogHistory extends LogHistory{
    use CentralConnection;

    protected $table = 'log_histories';
}