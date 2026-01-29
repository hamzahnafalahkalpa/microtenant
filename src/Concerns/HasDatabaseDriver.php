<?php

namespace Hanafalah\MicroTenant\Concerns;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

trait HasDatabaseDriver
{
    /**
     * Get database driver for tenant connection
     *
     * @param string $connection
     * @return string
     */
    protected function getDatabaseDriver(string $connection = 'tenant'): string
    {
        return config("database.connections.{$connection}.driver", 'pgsql');
    }

    /**
     * Check if schema/database exists based on driver type
     *
     * @param string $schemaName
     * @param string $driver
     * @param string $connection
     * @return bool
     */
    protected function schemaExists(string $schemaName, string $driver, string $connection = 'tenant'): bool
    {
        try {
            switch ($driver) {
                case 'pgsql':
                    return $this->postgresSchemaExists($schemaName, $connection);
                case 'mysql':
                case 'mariadb':
                    return $this->mysqlDatabaseExists($schemaName, $connection);
                default:
                    Log::warning("Unsupported database driver for schema check: {$driver}");
                    return false;
            }
        } catch (\Throwable $th) {
            Log::error("Error checking schema existence: " . $th->getMessage());
            return false;
        }
    }

    /**
     * Check if PostgreSQL schema exists
     *
     * @param string $schemaName
     * @param string $connection
     * @return bool
     */
    protected function postgresSchemaExists(string $schemaName, string $connection = 'tenant'): bool
    {
        $result = DB::connection($connection)
            ->select("SELECT schema_name FROM information_schema.schemata WHERE schema_name = ?", [$schemaName]);

        return count($result) > 0;
    }

    /**
     * Check if MySQL database exists
     *
     * @param string $databaseName
     * @param string $connection
     * @return bool
     */
    protected function mysqlDatabaseExists(string $databaseName, string $connection = 'tenant'): bool
    {
        $result = DB::connection($connection)
            ->select("SELECT SCHEMA_NAME FROM information_schema.SCHEMATA WHERE SCHEMA_NAME = ?", [$databaseName]);

        return count($result) > 0;
    }

    /**
     * Create schema/database based on driver type
     *
     * @param string $schemaName
     * @param string $driver
     * @param string $connection
     * @return void
     */
    protected function createSchema(string $schemaName, string $driver, string $connection = 'tenant'): void
    {
        try {
            switch ($driver) {
                case 'pgsql':
                    $this->createPostgresSchema($schemaName, $connection);
                    break;
                case 'mysql':
                case 'mariadb':
                    $this->createMysqlDatabase($schemaName, $connection);
                    break;
                default:
                    throw new \Exception("Unsupported database driver: {$driver}");
            }

            Log::info("Schema {$schemaName} created successfully for driver: {$driver}");
        } catch (\Throwable $th) {
            Log::error("Error creating schema {$schemaName}: " . $th->getMessage());
            throw $th;
        }
    }

    /**
     * Create PostgreSQL schema
     *
     * @param string $schemaName
     * @param string $connection
     * @return void
     */
    protected function createPostgresSchema(string $schemaName, string $connection = 'tenant'): void
    {
        DB::connection($connection)
            ->statement("CREATE SCHEMA IF NOT EXISTS \"{$schemaName}\"");
    }

    /**
     * Create MySQL database
     *
     * @param string $databaseName
     * @param string $connection
     * @return void
     */
    protected function createMysqlDatabase(string $databaseName, string $connection = 'tenant'): void
    {
        DB::connection($connection)
            ->statement("CREATE DATABASE IF NOT EXISTS `{$databaseName}` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    }

    /**
     * Set schema/database connection configuration based on driver
     *
     * @param string $schemaName
     * @param string $driver
     * @param string $connection
     * @return array Original configuration
     */
    protected function setSchemaConnectionConfig(string $schemaName, string $driver, string $connection = 'tenant'): array
    {
        switch ($driver) {
            case 'pgsql':
                $original = [
                    'search_path' => config("database.connections.{$connection}.search_path")
                ];
                config(["database.connections.{$connection}.search_path" => $schemaName]);
                break;

            case 'mysql':
            case 'mariadb':
                $original = [
                    'database' => config("database.connections.{$connection}.database")
                ];
                config(["database.connections.{$connection}.database" => $schemaName]);
                break;

            default:
                $original = [];
                break;
        }

        return $original;
    }

    /**
     * Restore schema/database connection configuration based on driver
     *
     * @param array $originalConfig
     * @param string $driver
     * @param string $connection
     * @return void
     */
    protected function restoreSchemaConnectionConfig(array $originalConfig, string $driver, string $connection = 'tenant'): void
    {
        switch ($driver) {
            case 'pgsql':
                if (isset($originalConfig['search_path'])) {
                    config(["database.connections.{$connection}.search_path" => $originalConfig['search_path']]);
                }
                break;

            case 'mysql':
            case 'mariadb':
                if (isset($originalConfig['database'])) {
                    config(["database.connections.{$connection}.database" => $originalConfig['database']]);
                }
                break;
        }
    }
}
