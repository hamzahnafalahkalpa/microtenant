<?php

namespace Hanafalah\MicroTenant;

use Hanafalah\LaravelSupport\Supports\PackageManagement;
use Hanafalah\MicroTenant\Concerns\Providers\HasImpersonate;
use Hanafalah\MicroTenant\Concerns\Providers\HasOverrider;
use Hanafalah\MicroTenant\Contracts\MicroTenant as ContractsMicroTenant;
use Hanafalah\ApiHelper\Schemas\ApiAccess;

class MicroTenant extends PackageManagement implements ContractsMicroTenant
{
    use HasOverrider, HasImpersonate;

    protected array $__micro_tenant_config = [];
    protected string $__entity = 'Tenant';

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

    public function impersonate($path): self{
        $path = base_path($path);
        require base_path().'/vendor/autoload.php';
        if (\file_exists($path.'/vendor/autoload.php')){
            require $path.'/vendor/autoload.php';
            foreach ([
                $this->tenant->app['provider'],
                $this->tenant->group['provider'],
                $this->tenant->provider
            ] as $provider) {
                app()->register($this->replacement($provider));
            }
            // $cache = $this->getMicroTenantCache();
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
}
