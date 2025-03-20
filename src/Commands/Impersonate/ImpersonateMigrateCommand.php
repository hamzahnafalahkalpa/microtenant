<?php

namespace Zahzah\MicroTenant\Commands\Impersonate;

use Zahzah\LaravelSupport\Concerns\Support\HasArray;
use Zahzah\LaravelSupport\Concerns\Support\HasCache;
use Zahzah\MicroTenant\Commands\Impersonate\Concern\generatorHelperPath;
use Zahzah\MicroTenant\Facades\MicroTenant;
use Illuminate\Support\Str;

class ImpersonateMigrateCommand extends EnvironmentCommand
{
    use HasCache, HasArray,generatorHelperPath;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'impersonate:migrate {--path : specify the path of the lib} 
                            {--app} {--group}
                            {--app_id= : The id of the app}
                            {--group_id= : The id of the group}
                            {--tenant_id= : The id of the tenant}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'This command is running migration in a application impersonate.';


    /**
     * Execute the console command.
     */
    public function handle()
    {
        MicroTenant::setWithDatabaseName(true);

        $this->isChenkingImpersonateApp();
        $impersonateMigrate = $this->getImpersonate();
        switch ($this->__choosed_impersonate) {
            case 'application':
                $tenant_path = $impersonateMigrate->config['migration_path'];
                $this->overrideConfig($tenant_path);
                $this->caller($impersonateMigrate->model->tenant);
                
                $allGroup = $this->getAllGroupWithApp($impersonateMigrate->model->tenant->id);
                if (isset($allGroup) && count($allGroup) > 0) {
                    $path        = $impersonateMigrate->config['migration_path'].'/centrals';
                    $tenant_path =  $impersonateMigrate->config['migration_path'].'/tenants';
                    foreach($allGroup as $group) {
                        $this->overrideConfig($path);
                        $this->caller($group);
                        $allTenants  = $this->getAllTenantWithGroup($group->id);
                        if(isset($allTenants) && count($allTenants) > 0) {
                            foreach($allTenants as $tenant) {
                                $this->overrideConfig($tenant_path);
                                $this->caller($tenant);
                            }
                        }
                    }
                }
            break;
            case 'group':
                $group_migration_path = $impersonateMigrate->config['migration_path'];

                $this->overrideConfig($group_migration_path);
                $this->caller($impersonateMigrate->model);

                $allTenants = $this->getAllTenantWithGroup($impersonateMigrate->model->id);
                $tenant_path = $impersonateMigrate->config['migration_path']."/tenants";
                foreach($allTenants as $tenant) {
                    $this->overrideConfig($tenant_path);
                    $this->caller($tenant);
                }
                
            break;
            default:
                $tenant_migration_path = $impersonateMigrate->config['migration_path'];
                $this->overrideConfig($tenant_migration_path);
                $this->caller($impersonateMigrate->model);
            break;
        }

        // foreach(static::$__impersonateCache as $key => $impersonateMigrate) {
        // }

    }

    private function getAllGroupWithApp($id) {
        return $this->TenantModel()->central()->where('parent_id',$id)->select('id','name','props')->orderBy('id')->get();
    }

    private function getAllTenantWithGroup($id) {
        return$this->TenantModel()->select('id','name','props')->parentId($id)->orderBy('id')->get();
    }

    private function overrideConfig(mixed $path) {
        $path = Str::replace('\\','/',$path);
        config([
            'tenancy.migration_parameters.--path' => $path
        ]);
    }

    private function caller($tenant) {
        $this->call("tenants:migrate", [    
            '--path'    => config('tenancy.migration_parameters.--path'),
            '--tenants' => $tenant->id
        ]);
    }
}

