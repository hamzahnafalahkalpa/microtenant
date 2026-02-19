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

    /**
     * Track registered providers to avoid re-registration
     */
    protected static array $registeredProviders = [];

    /**
     * Track registered autoloaders to avoid re-loading
     */
    protected static array $loadedAutoloaders = [];

    /**
     * Impersonate a tenant by loading its providers and config.
     *
     * @param Model $tenant The tenant to impersonate
     * @param bool $recurse Whether to recurse to parent tenants
     * @param bool $initTenancy Whether to initialize tenancy (false during recursion)
     * @return self
     */
    public function impersonate(Model $tenant, ?bool $recurse = true, ?bool $initTenancy = true): self{
        $profiling = config('micro-tenant.profiling.enabled', false);
        $timings = [];

        try {
            // Setup path
            $t = $profiling ? microtime(true) : 0;
            $path = $tenant->path.DIRECTORY_SEPARATOR.Str::kebab($tenant->name);
            $this->basePathResolver($path);

            // Load autoloader only once per path
            $autoloadPath = $path.'/vendor/autoload.php';
            if (!isset(static::$loadedAutoloaders[$autoloadPath]) && file_exists($autoloadPath)){
                require_once $autoloadPath;
                static::$loadedAutoloaders[$autoloadPath] = true;
            }
            if ($profiling) $timings['path_setup'] = round((microtime(true) - $t) * 1000, 2);

            // Recurse to parent WITHOUT tenancy init (skip redundant tenancy operations)
            if ($recurse && isset($tenant->parent)){
                $t = $profiling ? microtime(true) : 0;
                $this->impersonate($tenant->parent, true, false); // Pass false to skip tenancy init
                if ($profiling) $timings['parent_impersonate'] = round((microtime(true) - $t) * 1000, 2);
            }

            // Register service provider only if not already registered
            $t = $profiling ? microtime(true) : 0;
            $provider = $tenant->provider;
            if (class_exists($provider)){
                $provider = $this->replacement($provider);

                // Skip if already registered
                if (!isset(static::$registeredProviders[$provider])) {
                    app()->register($provider);
                    static::$registeredProviders[$provider] = true;

                    $config_name = Str::kebab(Str::before(class_basename($provider),'ServiceProvider'));
                    $own_models = config($config_name.'.database.models',[]);
                    $this->processRegisterProvider($config_name,$tenant?->packages ?? []);
                    $base_path = rtrim(config($config_name.'.paths.base_path'),DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR;
                    $this->processRegisterConfig($config_name, $base_path.config($config_name.'.libs.config'));
                    $models = config('database.models',[]);
                    $models = array_merge($models, $own_models);
                    config(['database.models' => $models]);
                }
            }
            if ($profiling) $timings['provider_registration'] = round((microtime(true) - $t) * 1000, 2);

            // Tenancy operations - ONLY for the final tenant (skip during recursion)
            if ($initTenancy) {
                $t = $profiling ? microtime(true) : 0;
                try {
                    // Skip if already initialized with the same tenant
                    $currentTenant = tenancy()->tenant;
                    $alreadyInitialized = $currentTenant &&
                                          $currentTenant->getKey() === $tenant->getKey();

                    if (!$alreadyInitialized) {
                        // Only end if there's an active tenant
                        if (tenancy()->initialized) {
                            tenancy()->end();
                        }
                        tenancy()->initialize($tenant);
                    }
                } catch (\Throwable $th) {
                    throw $th;
                }
                if ($profiling) $timings['tenancy_init'] = round((microtime(true) - $t) * 1000, 2);
            }

            // Config override - only for final tenant when initTenancy is true
            if ($initTenancy) {
                $t = $profiling ? microtime(true) : 0;
                $this->overrideTenantConfig($tenant);
                if ($profiling) $timings['override_config'] = round((microtime(true) - $t) * 1000, 2);
            }

            // Log profiling for this tenant level
            if ($profiling && !empty($timings)) {
                \Illuminate\Support\Facades\Log::debug("[MicroTenant::impersonate {$tenant->flag}]" . ($initTenancy ? "" : " (skip tenancy)") . " " . json_encode($timings));
            }
        } catch (\Throwable $th) {
            throw $th;
        }
        return $this;
    }

    /**
     * Reset static caches (for Octane worker recycling)
     */
    public static function flushStaticCaches(): void
    {
        static::$registeredProviders = [];
        static::$loadedAutoloaders = [];
        static::$microtenant = null;
    }

    /**
     * Impersonate a tenant by setting the path and database.
     *
     * @param Tenant|int|string $tenant The tenant to impersonate.
     *
     * @return self
     */
    public function tenantImpersonate($tenant = null): self{
        $profiling = config('micro-tenant.profiling.enabled', false);
        $timings = [];

        // Initialize tenant
        $t = $profiling ? microtime(true) : 0;
        $tenant = $this->resolveTenant($tenant);
        $this->getCacheData('impersonate');
        $this->initialize($tenant);
        if ($profiling) $timings['resolve_tenant'] = round((microtime(true) - $t) * 1000, 2);

        // Setup paths
        $t = $profiling ? microtime(true) : 0;
        $tenant_folder = Str::kebab($tenant->name);
        $path = tenant_path($tenant_folder);
        $this->basePathResolver($path);
        $this->impersonate($tenant);
        if ($profiling) $timings['impersonate'] = round((microtime(true) - $t) * 1000, 2);

        // Get configuration
        $database = config('micro-tenant.database');
        $db_tenant_name = $database['database_tenant_name'];
        $clusters = config('database.clusters', []);
        $current_year = request()->header('cluster') ?? date('Y');

        // Process tenant database and clusters
        $generate_db = false;
        $clusters_to_generate = [];

        if (!empty($clusters)) {
            $t = $profiling ? microtime(true) : 0;
            $result = $this->processClusters($clusters, $db_tenant_name, $current_year, $tenant);
            $generate_db = $result['generate_db'];
            $clusters_to_generate = $result['clusters_to_generate'];
            if ($profiling) $timings['process_clusters'] = round((microtime(true) - $t) * 1000, 2);
        }

        // Setup migration path (tenancy already initialized in impersonate())
        $t = $profiling ? microtime(true) : 0;
        $tenant_config = config($tenant_folder.'.libs.migration');
        $migration_path = tenant_path($tenant_folder.'/src/'.$tenant_config);
        $this->setMicroTenant($tenant)->overrideDatabasePath($migration_path);
        // NOTE: tenancy()->initialize() removed - already called in impersonate()
        if ($profiling) $timings['set_micro_tenant'] = round((microtime(true) - $t) * 1000, 2);

        // Handle database generation and cluster schema generation
        if ($generate_db) {
            $t = $profiling ? microtime(true) : 0;
            $this->runImpersonateMigration($tenant);
            if ($profiling) $timings['run_migration'] = round((microtime(true) - $t) * 1000, 2);
        }

        // Dispatch job to generate missing cluster schemas asynchronously
        if (!empty($clusters_to_generate) && $this->isClusterJobEnabled()) {
            $this->dispatchClusterGenerationJob($tenant, $current_year, $clusters_to_generate);
        }

        // Log profiling results
        if ($profiling && !empty($timings)) {
            \Illuminate\Support\Facades\Log::info('[MicroTenant::tenantImpersonate Profiling]', $timings);
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

        // Filter valid clusters first
        $validClusters = [];
        foreach ($clusters as $key => $cluster) {
            if (config('database.connections.'.$key.'.driver') !== null &&
                config('database.connections.'.$connection_name.'.driver') !== null) {
                $validClusters[$key] = $cluster;
            }
        }

        if (empty($validClusters)) {
            return ['generate_db' => false, 'clusters_to_generate' => []];
        }

        // Check database exists once (reuse result)
        $dbExists = null;
        $firstKey = array_key_first($validClusters);
        $firstCluster = $validClusters[$firstKey];

        // Configure tenancy once for initial check
        config([
            'tenancy.database.prefix' => $firstCluster['search_path'],
            'tenancy.database.suffix' => null,
            'tenancy.database.central_connection' => $firstKey
        ]);

        try {
            $manager = $tenant_model->database()->manager();
            $dbExists = $manager->databaseExists($tenant_model->database()->getName());

            if (!$dbExists) {
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

        // If database was just created, skip schema existence check
        if ($generate_db) {
            return ['generate_db' => true, 'clusters_to_generate' => []];
        }

        // Batch check all schema existence in a single query
        $schemaNames = array_column($validClusters, 'search_path');
        $driver = $this->getDatabaseDriver('tenant');
        $schemaExistence = $this->schemaExistsBatch($schemaNames, $driver, 'tenant');

        // Determine which clusters need generation
        foreach ($validClusters as $key => $cluster) {
            $schemaName = $cluster['search_path'];
            if (!($schemaExistence[$schemaName] ?? false)) {
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
            // Build options using already-loaded relations to avoid N+1 queries
            $options = $this->buildImpersonateOptions($tenant);
            if (isset($options)) Artisan::call('impersonate:cache',$options);
            $cache = $this->getCache($impersonate['name'], $impersonate['tags']);
            static::$microtenant = $cache;
        }
        return $this;
    }

    /**
     * Build impersonate cache command options from tenant.
     * Uses already-loaded parent relations to avoid additional queries.
     *
     * @param Model $tenant
     * @return array|null
     */
    protected function buildImpersonateOptions(Model $tenant): ?array
    {
        switch ($tenant->flag) {
            case 'TENANT':
                // Use loaded relation or fetch parent_id from already-loaded parent
                $parent = $tenant->relationLoaded('parent') ? $tenant->parent : null;
                $appId = $parent?->parent_id ?? $this->getAppIdForTenant($tenant);
                return [
                    '--tenant_id' => $tenant->getKey(),
                    '--group_id'  => $tenant->parent_id,
                    '--app_id'    => $appId
                ];

            case 'CENTRAL_TENANT':
                return [
                    '--group_id'  => $tenant->getKey(),
                    '--app_id'    => $tenant->parent_id,
                    '--skip' => true
                ];

            case 'APP':
                return [
                    '--app_id'    => $tenant->getKey(),
                    '--skip' => true
                ];

            default:
                return null;
        }
    }

    /**
     * Get app ID for tenant when parent relation is not loaded.
     * Uses single optimized query instead of triggering relation loading.
     *
     * @param Model $tenant
     * @return int|null
     */
    protected function getAppIdForTenant(Model $tenant): ?int
    {
        // If we have the parent loaded, use it
        if ($tenant->relationLoaded('parent') && $tenant->parent) {
            return $tenant->parent->parent_id;
        }

        // Otherwise do a single query to get the app_id
        $parent = $this->TenantModel()
            ->select('parent_id')
            ->where('id', $tenant->parent_id)
            ->first();

        return $parent?->parent_id;
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
            $profiling = config('micro-tenant.profiling.enabled', false);
            $timings = [];
            $totalStart = $profiling ? microtime(true) : 0;

            try {
                // Time ApiAccess::init() - JWT/token validation
                $t = $profiling ? microtime(true) : 0;
                $apiAccessInstance = ApiAccess::init($token ?? null);
                if ($profiling) $timings['api_access_init'] = round((microtime(true) - $t) * 1000, 2);

                // Time the accessOnLogin callback execution
                $t = $profiling ? microtime(true) : 0;
                $apiAccessInstance->accessOnLogin(function ($api_access) use ($profiling, &$timings) {
                    // Time onLogin (includes tenantImpersonate)
                    $t2 = $profiling ? microtime(true) : 0;
                    $this->onLogin($api_access);
                    if ($profiling) $timings['onLogin'] = round((microtime(true) - $t2) * 1000, 2);

                    // Time service cache handling
                    $t2 = $profiling ? microtime(true) : 0;
                    app(config('laravel-support.service_cache'))->handle();
                    if ($profiling) $timings['service_cache'] = round((microtime(true) - $t2) * 1000, 2);
                });
                if ($profiling) $timings['access_on_login_callback'] = round((microtime(true) - $t) * 1000, 2);

                // Log the breakdown
                if ($profiling) {
                    $timings['total'] = round((microtime(true) - $totalStart) * 1000, 2);
                    \Illuminate\Support\Facades\Log::info('[MicroTenant::accessOnLogin Breakdown]', $timings);
                }
            } catch (\Throwable $th) {
                throw $th;
            }
        }
    }

    public function onLogin(ModuleApiAccess $api_access){
        $profiling = config('micro-tenant.profiling.enabled', false);
        $timings = [];

        $this->api_access  = $api_access;

        // Time: get auth user
        $t = $profiling ? microtime(true) : 0;
        $user = auth()->user();
        if ($profiling) $timings['auth_user'] = round((microtime(true) - $t) * 1000, 2);

        // Time: load user relations if not loaded
        $t = $profiling ? microtime(true) : 0;
        if (!$user->relationLoaded('userReference')) {
            $user->load(['userReference.tenant.parent.parent']);
        }
        if ($profiling) $timings['load_user_relations'] = round((microtime(true) - $t) * 1000, 2);

        $current_reference = $user->userReference;
        if (!isset($current_reference)) {
            throw new \Exception('User Invalid');
        }

        $tenant = $current_reference->tenant;
        if (!isset($tenant)) {
            throw new \Exception('Tenant not found');
        }

        $this->tenant = $tenant;

        // Time: tenantImpersonate
        $t = $profiling ? microtime(true) : 0;
        $this->tenantImpersonate($this->tenant);
        if ($profiling) $timings['tenantImpersonate'] = round((microtime(true) - $t) * 1000, 2);

        // Log profiling
        if ($profiling && !empty($timings)) {
            \Illuminate\Support\Facades\Log::info('[MicroTenant::onLogin Breakdown]', $timings);
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


