<?php

namespace Hanafalah\MicroTenant;

use GroupInitialPuskesmas\TenantPuskesmas\TenantPuskesmas;
use Hanafalah\ApiHelper\Contracts\ModuleApiAccess;
use Hanafalah\LaravelSupport\Supports\PackageManagement;
use Hanafalah\MicroTenant\Concerns\Providers\HasImpersonate;
use Hanafalah\MicroTenant\Concerns\Providers\HasOverrider;
use Hanafalah\MicroTenant\Contracts\MicroTenant as ContractsMicroTenant;
use Hanafalah\MicroTenant\Models\Tenant\Tenant;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use RuntimeException;

class MicroTenant extends PackageManagement implements ContractsMicroTenant
{
    use HasOverrider, HasImpersonate;

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
     * Impersonate a tenant by setting the path and database.
     *
     * @param Tenant|int|string $tenant The tenant to impersonate.
     *
     * @return self
     */
    public function tenantImpersonate($tenant = null, ? string $cek = null): self{
        $tenant ??= $this->tenant;
        $this->getCacheData('impersonate');
        $this->initialize($tenant);
        $tenant_folder = Str::kebab($tenant->name);
        $path          = tenant_path($tenant_folder);
        $this->reconfigDatabases($tenant);
        $this->impersonate($path);
        if (isset($this->__impersonate)){
            $tenant_config = config($tenant_folder.'.libs.migration');
            $path = tenant_path($tenant_folder.'/src/'.$tenant_config);
            $this->setMicroTenant()->overrideDatabasePath($path);
            $path = tenant_path($tenant_folder.'/storage');
            $this->overrideStoragePath($path);
        }
        return $this;
    }

    /**
     * Set the micro tenant based on the cached data of the impersonate feature.
     *
     * @return self
     */
    public function setMicroTenant(): self{
        $impersonate = $this->getCacheData('impersonate');
        $tenant      = $this->tenant;
        $this->reconfigDatabases($tenant);
        $cache  = cache();
        $cache  = $cache->tags($impersonate['tags']);
        $cache  = $cache->get($impersonate['name'],null);
        if (isset($cache)) {
            static::$microtenant = $cache;
        }else{
            if ($tenant->flag == Tenant::FLAG_TENANT){
                Artisan::call('impersonate:cache',[
                    '--tenant_id' => $tenant->getKey(),
                    '--group_id'  => $tenant->parent_id,
                    '--app_id'    => $tenant->parent->parent_id
                ]);
                $cache  = cache();
                $cache  = $cache->tags($impersonate['tags']);
                $cache  = $cache->get($impersonate['name'],null);
                static::$microtenant = $cache;
            }
        }
        return $this;
    }

    public function getMicroTenant(){
        return static::$microtenant;
    }

    public function reconfigDatabases($tenant): self{
        $this->reconfigDatabase($tenant);
        if (isset($tenant->parent)){
            $this->reconfigDatabases($tenant->parent);
        }
        return $this;
    }

    public function reconfigDatabase($tenant): self{
        $connection_path = "database.connections.".$tenant->getConnectionFlagName();
        if (env('DB_DRIVER','mysql') == 'mysql'){
            config([
                "$connection_path.database" => $tenant->tenancy_db_name,
                "$connection_path.username" => $tenant->tenancy_db_username,
                "$connection_path.password" => $tenant->tenancy_db_password
            ]);
        }else{
            config([
                "$connection_path.database"    => env('DB_DATABASE', 'central'),
                "$connection_path.search_path" => $tenant->tenancy_db_name,
                "$connection_path.username"    => $tenant->tenancy_db_username ?? env('DB_USERNAME'),
                "$connection_path.password"    => $tenant->tenancy_db_password ?? env('DB_PASSWORD')
            ]);
        }
        return $this;
    }

    public function overrideStoragePath(string $path): self{
        if (!is_dir($path)) {
            mkdir($path, 0777, true);
        }

        app()->useStoragePath($path);
        return $this;
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

    public function onLogin(ModuleApiAccess $api_access){
        $this->api_access  = $api_access;
        $current_reference = $this->api_access->getUser()->userReference;
        $tenant            = $current_reference->tenant;
        if (isset($current_reference) && isset($tenant)){
            $this->tenant = $tenant;
            (isset($this->tenant))
                ? $this->tenantImpersonate()
                : throw new \Exception('Tenant not found');
        }else{
            throw new \Exception('User Invalid');
        }
        return $this->getMicroTenant();
    }

    public function impersonate($path): self{
        require base_path().'/vendor/autoload.php';
        if (file_exists($path.'/vendor/autoload.php')){
            try {
                require $path.'/vendor/autoload.php';
                foreach ([
                    $this->tenant->app['provider'],
                    $this->tenant->group['provider'],
                    $this->tenant->provider
                ] as $provider) {
                    app()->register($this->replacement($provider));
                }
            } catch (\Throwable $th) {
                dd($th->getMessage());
                //throw $th;
            }
        }
        return $this;
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
