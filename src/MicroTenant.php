<?php

namespace Zahzah\MicroTenant;

use Illuminate\Container\Container;
use Illuminate\Support\Facades\Artisan;
use Zahzah\ApiHelper\ApiAccess;
use Zahzah\LaravelSupport\Supports\PackageManagement;
use Zahzah\MicroTenant\Contracts\Models\Tenant;
use Illuminate\Support\Str;

class MicroTenant extends PackageManagement{
    /** @var array */
    protected $__microtenant_config = [];
    protected string $__entity = 'Tenant';

    /** @var Tenant|int|string|null */
    public static $microtenant;

    public static $with_database_name = false;

    public $tenant,$api_access;

    protected $__impersonate;
    protected $__cache_data = [
        'impersonate' => [
            'name'    => 'microtenant-impersonate',
            'tags'    => ['impersonate','microtenant-impersonate'],
            'forever' => true
        ]
    ];

    public static function getWithDatabaseName(){
        return static::$with_database_name;
    }

    public static function setWithDatabaseName(bool $status){
        static::$with_database_name = $status;
    }

    /**
     * Constructs a new instance of the MicroTenant class.
     *
     * @param Container $app The application container.
     */
    public function __construct(){
        $this->initConfig()->setConfig('micro-tenant',$this->__microtenant_config);
        parent::__construct();
    }


    public function addPackage(object $model, string $alias,? string $namespace = null){
        if (!isset($namespace)){
            $namespace = explode('/',$alias);
            $namespace[0] = Str::studly($namespace[0]);
            $namespace[1] = Str::studly($namespace[1]);
            $namespace[2] = $namespace[1].'ServiceProvider';
            $namespace = implode('\\',$namespace);
        }
        if (!isset($model->packages)) {
            $model->packages = [];
        }
        $packages = $model->packages;
        $namespace = addslashes($namespace);
        $packages[$alias] = [
            "provider" => $namespace
        ];
        $model->setAttribute('packages',$packages);            
        $model->save();

        if (isset($model->path)){
            $base          = base_path($model->path);
            $base_path     = $base.'/'.($model->with_soruce ? 'src' : '');
            $composer_path = $base_path.'/composer.json';
            $composerData  = json_decode(file_get_contents($composer_path), true);
            $requires      = $composerData['require'];
            $exists        = $requires;
            if (!isset($requires[$alias])) $exists[$alias] = '1.x';
            $composerData['require'] = $exists;
            file_put_contents($composer_path, json_encode($composerData, JSON_UNESCAPED_SLASHES));

            //REMOVE COMPOSER LOCK
            $lock_path = $base_path.'/composer.lock';
            if (file_exists($lock_path)){
                unlink($lock_path);
            }
            //DO COMPOSER INSTALL
            // $process = new Process(['composer', 'install', '--no-interaction', '--no-progress', '--no-scripts'], $base);
            // $process->run();
        }
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
        $this->initialize($tenant);
        $this->getCacheData('impersonate');
        $this->impersonate($tenant->path);
        if (isset($this->__impersonate)){
            $this->setMicroTenant()
                 ->overrideDatabasePath();
            $this->overrideStoragePath($tenant->name);
        }

        return $this;
    }

    public function overrideStoragePath(string $path): self{
        $path = tenant_path(\class_name_builder($path)).'/storage';
        app()->useStoragePath($path);
        return $this;
    }

    /**
     * Gets the current micro tenant.
     *
     * @return Tenant|int|string|null The current micro tenant.
     */
    public function getMicroTenant(){
        return static::$microtenant;
    }

    public function onLogin(ApiAccess $api_access){
        $this->api_access = $api_access;
        $current_reference = $this->api_access->getUser()->userReference;
        if (isset($current_reference)){
            $this->tenant = $current_reference->tenant;
            tenancy()->initialize($this->tenant);
            if (isset($this->tenant)){
                $this->tenantImpersonate();
            }else{
                throw new \Exception('Tenant not found');
            }
        }else{
            throw new \Exception('User Invalid');
        }
    }

    /**
     * Set the micro tenant based on the cached data of the impersonate feature.
     *
     * @return self
     */
    public function setMicroTenant(): self{
        $impersonate = $this->getCacheData('impersonate');
        $tenant = $this->tenant;
        $this->reconfigDatabase($tenant);
        if (isset($tenant->parent)){
            $this->reconfigDatabase($tenant->parent);
        }
        $cache  = cache();
        $cache  = $cache->tags($impersonate['tags']);
        $cache  = $cache->get($impersonate['name'],null);
        if (isset($cache)){
            static::$microtenant = $cache;
        }
        return $this;
    }

    public function reconfigDatabase($tenant): self{
        $connection_path = "database.connections.".$tenant->getConnectionFlagName();
        config([
            "$connection_path.database" => $tenant->tenancy_db_name,
            "$connection_path.username" => $tenant->tenancy_db_username,
            "$connection_path.password" => $tenant->tenancy_db_password
        ]);
        return $this;
    }

    public function impersonate($path): self{
        $path          = base_path($path);
        $loader        = require base_path().'/vendor/autoload.php';
        if (\file_exists($path.'/vendor/autoload.php')){
            $active_loader = require $path.'/vendor/autoload.php';
    
            // $activeKeys    = $active_loader->getPrefixesPsr4();
            // $psr4          = $this->diff($this->keys($activeKeys), $this->keys($loader->getPrefixesPsr4()));
            // foreach ($psr4 as $psr) $loader->addPsr4($psr, $activeKeys[$psr]);
            // $loader->register();
            foreach ([
                $this->tenant->app['provider'],
                $this->tenant->group['provider'],
                $this->tenant->provider
            ] as $provider) {
                app()->register($this->replacement($provider));
            }
            $cache = $this->getMicroTenantCache();
            // if (!isset($cache)){
            //     Artisan::call('impersonate:cache',[
            //         '--tenant_id' => $this->tenant->getKey(),
            //         '--group_id'  => $this->tenant->parent_id,
            //         '--app_id'    => $this->api_access->getApiAccess()->reference_id
            //     ]);
            // }
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

    // public function providerFinder(string $path) : array {
    //     $composerJsonPath = $path . '/composer.json';
    //     if (file_exists($composerJsonPath)) {
    //         $composerData = json_decode(file_get_contents($composerJsonPath), true);
    //         return $composerData['extra']['laravel']['providers'] ?? [];
    //     }

    //     return [];
    // }

    public function getCacheData(? string $segment = null){
        $cache_data = $this->__cache_data;
        return $this->__impersonate = (isset($segment))
            ? $cache_data[$segment]
            : $cache_data;
    }

    public function overrideTenantConfig(){
        $microtenant   = $this->__microtenant_config;
        $database      = $microtenant['database'];
        $connection    = $database['connections'];
        $model         = $database['models'] ?? [];
        $dbname        = $database['database_tenant_name'];
        config([
            'tenancy'                                     => $this->__config['tenancy'],
            'tenancy.filesystem.asset_helper_tenancy'     => false,
            'tenancy.tenant_model'                        => $model['Tenant'] ?? null,
            'tenancy.id_generator'                        => null,
            'tenancy.domain_model'                        => $model['Domain'],
            'tenancy.central_domains'                     => $microtenant['domains']['central_domains'],
            'tenancy.database.central_connection'         => 'central',
            'tenancy.database.template_tenant_connection' => null,
            'tenancy.database.prefix'                     => $dbname['prefix'],
            'tenancy.database.suffix'                     => $dbname['suffix'],
            'tenancy.database.managers'                   => $database['managers'],
            'database.connection_central_name'            => 'central',
            'database.connection_central_tenant_name'     => 'central_tenant',
            'database.connection_central_app_name'        => 'central_app',
            'database.connections.central'                => $connection['central_connection'],
            'database.connections.central_tenant'         => $connection['central_connection'],
            'database.connections.central_app'            => $connection['central_connection']
        ]); 

    }

    public function forgetCache(){
        $this->forgetTags($this->__cache_data['impersonate']['tags']);
    }

    public function getMicroTenantCache(): mixed{
        $cache       = $this->getCacheData('impersonate');
        return cache()->tags($cache['tags'])->get($cache['name']);
    }

    public function cacheTenantImperonate(){
        $impersonate = $this->getMicroTenantCache();
        if (isset($impersonate->tenant->model)) {
            $this->tenantImpersonate($impersonate->tenant->model);
        }
    }

    
}