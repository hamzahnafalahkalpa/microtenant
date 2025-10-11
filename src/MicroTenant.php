<?php

namespace Hanafalah\MicroTenant;

use GroupInitialPuskesmas\TenantPuskesmas\TenantPuskesmas;
use Hanafalah\ApiHelper\Contracts\ModuleApiAccess;
use Hanafalah\ApiHelper\Facades\ApiAccess;
use Hanafalah\LaravelSupport\Supports\PackageManagement;
use Hanafalah\MicroTenant\Concerns\Providers\HasImpersonate;
use Hanafalah\MicroTenant\Concerns\Providers\HasOverrider;
use Hanafalah\MicroTenant\Contracts\Data\TenantData;
use Hanafalah\MicroTenant\Contracts\MicroTenant as ContractsMicroTenant;
use Hanafalah\MicroTenant\Models\Tenant\Tenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Str;

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
    public function tenantImpersonate($tenant = null): self{
        $tenant ??= $this->tenant;
        if (isset($tenant) && !$tenant instanceof Model){
            $tenant = $this->tenant = $this->TenantModel()->findOrFail($tenant);
        }
        $this->getCacheData('impersonate');
        $this->initialize($tenant);
        $tenant_folder = Str::kebab($tenant->name);
        $path          = tenant_path($tenant_folder);
        $this->basePathResolver($path);
        $this->impersonate($tenant);

        tenancy()->end();
        tenancy()->initialize($tenant);
        $this->overrideTenantConfig($tenant);            
        $database = config('micro-tenant.database');
        $db_tenant_name = $database['database_tenant_name'];
        foreach (config('database.clusters') as $key => $cluster) {                
            $this->setCache([
                'name' => $cluster['search_path'].'_'.$tenant->getKey(),
                'tags' => ['cluster','microtenant-cluster'],
                'forever' => true
            ],function() use ($key,$cluster,$db_tenant_name){
                config([
                    'tenancy.database.prefix' => $cluster['search_path'],
                    'tenancy.database.suffix' => null,
                    'tenancy.database.central_connection' => $key
                ]);
                $manager = $this->TenantModel()->database()->manager();
                if (!$manager->databaseExists($this->TenantModel()->database()->getName())){
                    $manager->createDatabase($this->TenantModel());
                }
                config([
                    'tenancy.database.prefix' => $db_tenant_name['prefix'],
                    'tenancy.database.suffix' => $db_tenant_name['suffix'],
                    'tenancy.database.central_connection' => 'central'
                ]);
                return true;
            });
        }
        $tenant_config = config($tenant_folder.'.libs.migration');
        $path = tenant_path($tenant_folder.'/src/'.$tenant_config);
        $this->setMicroTenant($tenant)->overrideDatabasePath($path);
        return $this;
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
        $cache  = cache();
        $cache  = $cache->tags($impersonate['tags']);
        $cache  = $cache->get($impersonate['name'],null);
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
            $cache  = cache();
            $cache  = $cache->tags($impersonate['tags']);
            $cache  = $cache->get($impersonate['name'],null);
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

    public function accessOnLogin(){
        Event::listen(\Laravel\Octane\Events\RequestReceived::class, function ($event) {
            $request = $event->request;

            if ($request->headers->has('AppCode')) {
                ApiAccess::init()->accessOnLogin(function ($api_access) {
                    $microtenant = MicroTenant::onLogin($api_access);
                    Auth::setUser($api_access->getUser());
                    tenancy()->initialize($microtenant?->tenant->model ?? $microtenant?->group->model ?? $microtenant?->project->model);
                });
            }
        });
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

    public function onLogout(?callable $callback){
        static::$microtenant = null;
        $impersonate = $this->getCacheData('impersonate');
        $this->forgetTags($impersonate['tags']);
        if (isset($callback)){
            $callback();
        }
    }

    public function impersonate(Model $tenant): self{
        try {
            $path = $tenant->path.DIRECTORY_SEPARATOR.Str::kebab($tenant->name);
            $this->basePathResolver($path);
            if (file_exists($path.'/vendor/autoload.php')){
                require_once $path.'/vendor/autoload.php';
            }
            if (isset($tenant->parent)){
                $this->impersonate($tenant->parent);
            }
            $provider = $tenant->provider;
            if (class_exists($provider)){
                $provider = $this->replacement($provider);
                app()->register($provider);
            }
        } catch (\Throwable $th) {
            throw $th;
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
