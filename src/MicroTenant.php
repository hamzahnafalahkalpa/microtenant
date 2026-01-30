<?php

namespace Hanafalah\MicroTenant;

use Hanafalah\ApiHelper\Contracts\ModuleApiAccess;
use Hanafalah\ApiHelper\Facades\ApiAccess;
use Hanafalah\LaravelSupport\Supports\PackageManagement;
use Hanafalah\MicroTenant\Concerns\HasDatabaseDriver;
use Hanafalah\MicroTenant\Concerns\Providers\HasImpersonate;
use Hanafalah\MicroTenant\Concerns\Providers\HasOverrider;
use Hanafalah\MicroTenant\Contracts\MicroTenant as ContractsMicroTenant;
use Hanafalah\MicroTenant\Models\Tenant\Tenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Hanafalah\LaravelSupport\Concerns\Support\HasRegisterConfig;

class MicroTenant extends PackageManagement implements ContractsMicroTenant
{
    use HasOverrider, HasImpersonate, HasRegisterConfig, HasDatabaseDriver;

    protected array $__micro_tenant_config = [];
    protected string $__entity = 'Tenant';
    protected $__exception;

    /** @var Tenant|int|string|null */
    public static $microtenant;

    public static $with_database_name = false;

    public $tenant, $api_access;

    protected $__cache_data = [
        'impersonate' => [
            'name'    => 'microtenant-impersonate',
            'tags'    => ['impersonate','microtenant-impersonate'],
            'forever' => true
        ]
    ];

    public function __construct(){
        $this->initConfig()->setConfig('micro-tenant',$this->__micro_tenant_config);
        parent::__construct();
    }

    public function getCacheData(? string $segment = null){
        $cache_data = $this->__cache_data;
        return $this->__impersonate = (isset($segment))
            ? $cache_data[$segment]
            : $cache_data;
    }

    public function impersonate(Model $tenant, ?bool $recurse = true): self{
        try {
            $path = $tenant->path.DIRECTORY_SEPARATOR.Str::kebab($tenant->name);
            $this->basePathResolver($path);
            if (file_exists($path.'/vendor/autoload.php')){
                require_once $path.'/vendor/autoload.php';
            }
            if ($recurse && isset($tenant->parent)){
                $this->impersonate($tenant->parent);
            }
            $provider = $tenant->provider;
            if (class_exists($provider)){
                $provider = $this->replacement($provider);
                app()->register($provider);
                $config_name = Str::kebab(Str::before(class_basename($provider),'ServiceProvider'));
                $own_models = config($config_name.'.database.models',[]);
                $this->processRegisterProvider($config_name,$tenant?->packages ?? []);
                $base_path = rtrim(config($config_name.'.paths.base_path'),DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR;
                $this->processRegisterConfig($config_name, $base_path.config($config_name.'.libs.config'));
                $models = config('database.models',[]);
                $models = array_merge($models, $own_models);
                config(['database.models' => $models]);
            }
            tenancy()->end();
            tenancy()->initialize($tenant);
            $this->overrideTenantConfig($tenant); 
        } catch (\Throwable $th) {
            throw $th;
        }
        return $this;
    }

    /**
     * Impersonate a tenant by setting the path and database.
     *
     * @param Tenant|int|string $tenant The tenant to impersonate.
     *
     * @return self
     */
    public function tenantImpersonate($tenant = null): self{
        // Initialize tenant
        $tenant = $this->resolveTenant($tenant);
        $this->getCacheData('impersonate');
        $this->initialize($tenant);

        // Setup paths
        $tenant_folder = Str::kebab($tenant->name);
        $path = tenant_path($tenant_folder);
        $this->basePathResolver($path);
        $this->impersonate($tenant);

        // Get configuration
        $database = config('micro-tenant.database');
        $db_tenant_name = $database['database_tenant_name'];
        $clusters = config('database.clusters', []);
        $current_year = request()->header('cluster') ?? date('Y');

        // Process tenant database and clusters
        $generate_db = false;
        $clusters_to_generate = [];

        if (!empty($clusters)) {
            $result = $this->processClusters($clusters, $db_tenant_name, $current_year, $tenant);
            $generate_db = $result['generate_db'];
            $clusters_to_generate = $result['clusters_to_generate'];
        }

        // Setup migration path and initialize tenancy
        $tenant_config = config($tenant_folder.'.libs.migration');
        $migration_path = tenant_path($tenant_folder.'/src/'.$tenant_config);
        $this->setMicroTenant($tenant)->overrideDatabasePath($migration_path);
        tenancy()->initialize($tenant);

        // Handle database generation and cluster schema generation
        if ($generate_db) {
            $this->runImpersonateMigration($tenant);
        }

        // Dispatch job to generate missing cluster schemas asynchronously
        if (!empty($clusters_to_generate) && $this->isClusterJobEnabled()) {
            $this->dispatchClusterGenerationJob($tenant, $current_year, $clusters_to_generate);
        }

        return $this;
    }

    /**
     * Resolve tenant instance
     *
     * @param Tenant|int|string|null $tenant
     * @return Model
     */
    protected function resolveTenant($tenant = null): Model
    {
        $tenant ??= $this->tenant;

        if (isset($tenant) && !$tenant instanceof Model) {
            $tenant = $this->tenant = $this->TenantModel()->findOrFail($tenant);
        }

        return $tenant;
    }

    /**
     * Process clusters for tenant database
     *
     * @param array $clusters
     * @param array $db_tenant_name
     * @param string $current_year
     * @param Model $tenant
     * @return array
     */
    protected function processClusters(array $clusters, array $db_tenant_name, string $current_year, Model $tenant): array
    {
        $generate_db = false;
        $clusters_to_generate = [];
        $tenant_model = $this->TenantModel();
        $connection_name = $tenant_model->getConnectionName();

        foreach ($clusters as $key => $cluster) {
            // Skip if connection driver is not configured
            if (config('database.connections.'.$key.'.driver') === null) {
                continue;
            }

            // Skip if tenant model connection is not configured
            if (config('database.connections.'.$connection_name.'.driver') === null) {
                continue;
            }

            // Configure tenancy for cluster
            config([
                'tenancy.database.prefix' => $cluster['search_path'],
                'tenancy.database.suffix' => null,
                'tenancy.database.central_connection' => $key
            ]);

            // Check and create database if not exists
            try {
                $manager = $tenant_model->database()->manager();
                if (!$manager->databaseExists($tenant_model->database()->getName())) {
                    $manager->createDatabase($tenant_model);
                    $generate_db = true;
                }
            } catch (\Throwable $th) {
                throw $th;
            }

            // Restore tenancy configuration
            config([
                'tenancy.database.prefix' => $db_tenant_name['prefix'],
                'tenancy.database.suffix' => $db_tenant_name['suffix'],
                'tenancy.database.central_connection' => 'central'
            ]);

            // Check if cluster schema needs to be generated
            if (!$generate_db && $this->shouldGenerateClusterSchema($cluster, $current_year, $tenant)) {
                $clusters_to_generate[$key] = $cluster;
            }
        }

        return [
            'generate_db' => $generate_db,
            'clusters_to_generate' => $clusters_to_generate
        ];
    }

    /**
     * Run impersonate migration command
     *
     * @param Model $tenant
     * @return void
     */
    protected function runImpersonateMigration(Model $tenant): void
    {
        try {
            $data = $this->prepareMigrationData($tenant);
            Artisan::call(config('micro-tenant.impersonate_command'), $data);
        } catch (\Throwable $th) {
            throw $th;
        }
    }

    /**
     * Prepare migration data based on tenant flag
     *
     * @param Model $tenant
     * @return array
     */
    protected function prepareMigrationData(Model $tenant): array
    {
        if ($tenant->flag == 'TENANT') {
            $parent = $tenant->parent;
            return [
                '--app'       => true,
                '--app_id'    => $parent->parent_id,
                '--group_id'  => $tenant->parent_id,
                '--tenant_id' => $tenant->getKey(),
            ];
        }

        return [
            '--app'    => true,
            '--app_id' => $tenant->getKey(),
        ];
    }

    /**
     * Check if cluster schema should be generated for the given year
     *
     * @param array $cluster
     * @param string $year
     * @param Model $tenant
     * @return bool
     */
    protected function shouldGenerateClusterSchema(array $cluster, string $year, Model $tenant): bool
    {
        try {
            $schemaName = $cluster['search_path'];
            $driver = $this->getDatabaseDriver('tenant');

            return !$this->schemaExists($schemaName, $driver, 'tenant');
        } catch (\Throwable $th) {
            \Illuminate\Support\Facades\Log::warning("Could not check cluster schema existence: " . $th->getMessage());
            return false;
        }
    }

    /**
     * Check if cluster job is enabled
     *
     * @return bool
     */
    protected function isClusterJobEnabled(): bool
    {
        return config('micro-tenant.jobs.cluster_generation.enabled', true);
    }

    /**
     * Dispatch cluster generation job
     *
     * @param Model $tenant
     * @param string $year
     * @param array $clusters
     * @return void
     */
    protected function dispatchClusterGenerationJob(Model $tenant, string $year, array $clusters): void
    {
        try {
            $jobClass = config('micro-tenant.jobs.cluster_generation.job_class',
                              \Hanafalah\MicroTenant\Jobs\GenerateClusterSchemas::class);
            $connection = config('micro-tenant.jobs.cluster_generation.connection', 'rabbitmq');

            $jobClass::dispatch($tenant, $year, $clusters)
                ->onConnection($connection);

            \Illuminate\Support\Facades\Log::info("Cluster generation job dispatched for tenant: {$tenant->id}, year: {$year}");
        } catch (\Throwable $th) {
            \Illuminate\Support\Facades\Log::error("Failed to dispatch cluster generation job: " . $th->getMessage());
            // Don't throw exception, allow the request to continue
        }
    }

    /**
     * Set the micro tenant based on the cached data of the impersonate feature.
     *
     * @return self
     */
    public function setMicroTenant(?Model $tenant = null): self{
        $impersonate = $this->getCacheData('impersonate');
        $tenant      ??= $this->tenant;
        if (!isset($tenant->flag)) $tenant->refresh();
        $cache = $this->getCache($impersonate['name'], $impersonate['tags']);
        if (isset($cache)) {
            static::$microtenant = $cache;
        }else{            
            switch ($tenant->flag) {
                case 'TENANT':
                    $options = [
                        '--tenant_id' => $tenant->getKey(),
                        '--group_id'  => $tenant->parent_id,
                        '--app_id'    => $tenant->parent->parent_id
                    ];
                break;
                case 'CENTRAL_TENANT':
                    $options = [
                        '--group_id'  => $tenant->getKey(),
                        '--app_id'    => $tenant->parent_id,
                        '--skip' => true
                    ];
                break;
                case 'APP':
                    $options = [
                        '--app_id'    => $tenant->getKey(),
                        '--skip' => true
                    ];
                break;
            }
            if (isset($options)) Artisan::call('impersonate:cache',$options);
            $cache = $this->getCache($impersonate['name'], $impersonate['tags']);
            static::$microtenant = $cache;
        }
        return $this;
    }

    public function getMicroTenant(){
        return static::$microtenant;
    }

    /**
     * Sets the database migration path to the given path.
     *
     * @param string $migration_path The path to set as the database migration path.
     *
     * @return self
     */
    public function overrideDatabasePath(string $migration_path): self
    {
        App::useDatabasePath($migration_path);
        return $this;
    }

    public function accessOnLogin(?string $token = null){
        if (request()->headers->has('AppCode')) {
            try {
                ApiAccess::init($token ?? null)->accessOnLogin(function ($api_access) {
                    $this->onLogin($api_access);
                    // Auth::setUser($api_access->getUser());
                    app(config('laravel-support.service_cache'))->handle();
                });
            } catch (\Throwable $th) {
                throw $th;
            }
        }
    }

    public function onLogin(ModuleApiAccess $api_access){
        $this->api_access  = $api_access;
        // $current_reference = $this->api_access->getUser()->userReference;
        $current_reference = auth()->user()->userReference;
        $tenant            = $current_reference->tenant;
        if (isset($current_reference) && isset($tenant)){
            $this->tenant = $tenant;
            (isset($this->tenant))
                ? $this->tenantImpersonate($this->tenant)
                : throw new \Exception('Tenant not found');
        }else{
            throw new \Exception('User Invalid');
        }
        return $this->getMicroTenant();
    }

    public function onLogout(?callable $callback){
        static::$microtenant = null;
        $impersonate = $this->getCacheData('impersonate');
        $this->forgetTags($impersonate['tags']);
        if (isset($callback)){
            $callback();
        }
    }

        /**
     * Replaces multiple backslashes with a single backslash in the given string.
     *
     * @param string $value The string to process.
     * @return string The processed string with reduced backslashes.
     */
    private function replacement(string $value){
        return preg_replace('/\\\\+/', '\\', $value);
    }

    public function setException($exception){
        $this->__exception = $exception;
    }

    public function getException(){
        return $this->__exception;
    }
}


