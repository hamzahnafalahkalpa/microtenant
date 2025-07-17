<?php

declare(strict_types=1);

namespace Hanafalah\MicroTenant\Concerns\Models;

use Hanafalah\MicroTenant\Facades\MicroTenant;

trait CentralAppConnection
{
    use Connection;

    public function initializeCentralAppConnection()
    {
        $this->connection = config('database.connection_central_app_name');
    }

    public function getConnectionName()
    {
        return $this->connection;
    }
}
