<?php

namespace Hanafalah\MicroTenant\Supports;

use Hanafalah\MicroTenant\Contracts\Supports\ConnectionManager as SupportsConnectionManager;
use Illuminate\Database\Eloquent\Model;

class ConnectionManager implements SupportsConnectionManager{
     public function handle(Model $tenant): void{
        $connection_path = "database.connections.".$tenant->getConnectionFlagName();
        switch (env('DB_DRIVER',null)) {
            case 'mysql':
                config([
                    "$connection_path.database" => $tenant->tenancy_db_name,
                    "$connection_path.username" => $tenant->tenancy_db_username,
                    "$connection_path.password" => $tenant->tenancy_db_password
                ]);
            break;
            case 'pgsql': 
                config([
                    "$connection_path.database"    => env('DB_DATABASE', 'central'),
                    "$connection_path.search_path" => $tenant->tenancy_db_name ?? 'public',
                    "$connection_path.username"    => $tenant->tenancy_db_username ?? env('DB_USERNAME'),
                    "$connection_path.password"    => $tenant->tenancy_db_password ?? env('DB_PASSWORD')
                ]);
            break;
            case 'sqlite':
                config([
                    "$connection_path.database"    => $tenant->tenancy_db_name
                ]);
            break;
            default:
                throw new \Exception('Database driver not supported');
            break;
        }
    }   
}