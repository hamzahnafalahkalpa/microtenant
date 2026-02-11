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
     * Batch check if PostgreSQL schemas exist (single query for multiple schemas)
     *
     * @param array $schemaNames
     * @param string $connection
     * @return array<string, bool> Map of schema name to existence status
     */
    protected function postgresSchemaExistsBatch(array $schemaNames, string $connection = 'tenant'): array
    {
        if (empty($schemaNames)) {
            return [];
        }

        try {
            $placeholders = implode(',', array_fill(0, count($schemaNames), '?'));
            $result = DB::connection($connection)
                ->select("SELECT schema_name FROM information_schema.schemata WHERE schema_name IN ($placeholders)", $schemaNames);

            $existingSchemas = array_column($result, 'schema_name');
            $existence = [];
            foreach ($schemaNames as $name) {
                $existence[$name] = in_array($name, $existingSchemas);
            }
            return $existence;
        } catch (\Throwable $th) {
            Log::error("Error batch checking schema existence: " . $th->getMessage());
            // Fallback to individual checks on error
            $existence = [];
            foreach ($schemaNames as $name) {
                $existence[$name] = $this->postgresSchemaExists($name, $connection);
            }
            return $existence;
        }
    }

    /**
     * Batch check if schemas exist based on driver type
     *
     * @param array $schemaNames
     * @param string $driver
     * @param string $connection
     * @return array<string, bool>
     */
    protected function schemaExistsBatch(array $schemaNames, string $driver, string $connection = 'tenant'): array
    {
        try {
            switch ($driver) {
                case 'pgsql':
                    return $this->postgresSchemaExistsBatch($schemaNames, $connection);
                case 'mysql':
                case 'mariadb':
                    return $this->mysqlDatabaseExistsBatch($schemaNames, $connection);
                default:
                    Log::warning("Unsupported database driver for batch schema check: {$driver}");
                    return array_fill_keys($schemaNames, false);
            }
        } catch (\Throwable $th) {
            Log::error("Error in batch schema check: " . $th->getMessage());
            return array_fill_keys($schemaNames, false);
        }
    }

    /**
     * Batch check if MySQL databases exist
     *
     * @param array $databaseNames
     * @param string $connection
     * @return array<string, bool>
     */
    protected function mysqlDatabaseExistsBatch(array $databaseNames, string $connection = 'tenant'): array
    {
        if (empty($databaseNames)) {
            return [];
        }

        try {
            $placeholders = implode(',', array_fill(0, count($databaseNames), '?'));
            $result = DB::connection($connection)
                ->select("SELECT SCHEMA_NAME FROM information_schema.SCHEMATA WHERE SCHEMA_NAME IN ($placeholders)", $databaseNames);

            $existingDbs = array_column($result, 'SCHEMA_NAME');
            $existence = [];
            foreach ($databaseNames as $name) {
                $existence[$name] = in_array($name, $existingDbs);
            }
            return $existence;
        } catch (\Throwable $th) {
            Log::error("Error batch checking MySQL database existence: " . $th->getMessage());
            $existence = [];
            foreach ($databaseNames as $name) {
                $existence[$name] = $this->mysqlDatabaseExists($name, $connection);
            }
            return $existence;
        }
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
