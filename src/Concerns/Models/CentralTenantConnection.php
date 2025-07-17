<?php

declare(strict_types=1);

namespace Hanafalah\MicroTenant\Concerns\Models;

trait CentralTenantConnection
{
    use Connection;

    public function initializeCentralTenantConnection()
    {
        $this->connection = config('database.connection_central_tenant_name');
    }

    public function getConnectionName()
    {
        return $this->connection;
    }
}
