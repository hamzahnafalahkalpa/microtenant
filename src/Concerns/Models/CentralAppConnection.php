<?php

declare(strict_types=1);

namespace Zahzah\MicroTenant\Concerns\Models;

use Zahzah\MicroTenant\Facades\MicroTenant;

trait CentralAppConnection
{
    use Connection;
    
    public function initializeCentralAppConnection(){        
        $this->connection = config('database.connection_central_app_name');
    }

    public function getConnectionName()
    {
        return $this->connection;
    }
}
