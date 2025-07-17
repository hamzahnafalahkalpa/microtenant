<?php

declare(strict_types=1);

namespace Hanafalah\MicroTenant\Concerns\Models;

use Stancl\Tenancy\Database\Concerns\CentralConnection as ConcernsCentralConnection;

trait CentralConnection
{
    use Connection, ConcernsCentralConnection;

    public function initializeCentralConnection()
    {
        $this->connection = config('tenancy.database.central_connection');
    }

    public function getConnectionName()
    {
        return $this->connection;
    }
}
