<?php

namespace Hanafalah\MicroTenant\Jobs;

use Hanafalah\MicroTenant\Concerns\HasDatabaseDriver;
use Hanafalah\MicroTenant\Models\Tenant\Tenant;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class GenerateClusterSchemas implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels, HasDatabaseDriver;

    public $tenant;
    public $year;
    public $clusters;

    /**
     * Create a new job instance.
     *
     * @param Tenant $tenant
     * @param int|string $year
     * @param array $clusters
     */
    public function __construct(Tenant $tenant, $year, array $clusters = [])
    {
        $this->tenant = $tenant;
        $this->year = $year;
        $this->clusters = $clusters;

        // Set the queue name from config
        $this->onQueue(config('micro-tenant.jobs.cluster_generation.queue', 'installation'));
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        try {
            Log::info("Starting cluster schema generation for tenant: {$this->tenant->id}, year: {$this->year}");

            $driver = $this->getDatabaseDriver('tenant');

            foreach ($this->clusters as $key => $cluster) {
                $schemaName = $cluster['search_path'];

                Log::info("Checking schema: {$schemaName} for cluster: {$key} with driver: {$driver}");

                // Check if schema exists
                $schemaExists = $this->schemaExists($schemaName, $driver, 'tenant');

                if (!$schemaExists) {
                    Log::info("Schema {$schemaName} does not exist. Creating...");

                    // Create schema
                    $this->createSchema($schemaName, $driver, 'tenant');

                    // Run migrations for the new schema
                    $this->runMigrationsForSchema($schemaName, $key);

                    Log::info("Schema {$schemaName} created and migrated successfully");
                } else {
                    Log::info("Schema {$schemaName} already exists. Skipping...");
                }
            }

            Log::info("Cluster schema generation completed for tenant: {$this->tenant->id}");
        } catch (\Throwable $th) {
            Log::error("Error generating cluster schemas for tenant {$this->tenant->id}: " . $th->getMessage());
            Log::error($th->getTraceAsString());
            throw $th;
        }
    }

    /**
     * Run migrations for the schema
     *
     * @param string $schemaName
     * @param string $clusterKey
     * @return void
     */
    protected function runMigrationsForSchema($schemaName, $clusterKey): void
    {
        try {
            $driver = $this->getDatabaseDriver('tenant');

            // Set connection configuration based on driver
            $originalConfig = $this->setSchemaConnectionConfig($schemaName, $driver, 'tenant');

            // Reconnect with new configuration
            DB::purge('tenant');
            DB::reconnect('tenant');

            // Prepare migration data based on tenant flag
            $data = $this->prepareMigrationData();

            // Run the impersonate migrate command
            $command = config('micro-tenant.jobs.cluster_generation.migration_command',
                              config('micro-tenant.impersonate_command', 'wellmed-backbone:impersonate-migrate'));

            Artisan::call($command, $data);

            Log::info("Migrations completed for schema: {$schemaName}");

            // Restore original configuration
            $this->restoreSchemaConnectionConfig($originalConfig, $driver, 'tenant');
            DB::purge('tenant');
            DB::reconnect('tenant');

        } catch (\Throwable $th) {
            Log::error("Error running migrations for schema {$schemaName}: " . $th->getMessage());
            throw $th;
        }
    }

    /**
     * Prepare migration data based on tenant flag
     *
     * @return array
     */
    protected function prepareMigrationData(): array
    {
        if ($this->tenant->flag == 'TENANT') {
            $parent = $this->tenant->parent;
            return [
                '--app'       => true,
                '--app_id'    => $parent->parent_id,
                '--group_id'  => $this->tenant->parent_id,
                '--tenant_id' => $this->tenant->getKey(),
            ];
        }

        return [
            '--app'    => true,
            '--app_id' => $this->tenant->getKey(),
        ];
    }

    /**
     * Handle a job failure.
     *
     * @param \Throwable $exception
     * @return void
     */
    public function failed(\Throwable $exception)
    {
        Log::error("Job failed for tenant {$this->tenant->id}, year {$this->year}: " . $exception->getMessage());
    }
}
