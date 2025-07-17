<?php

namespace Hanafalah\MicroTenant\Commands\Impersonate;

use Hanafalah\LaravelSupport\Concerns\Support\HasArray;
use Hanafalah\LaravelSupport\Concerns\Support\HasCache;
use Hanafalah\LaravelSupport\Supports\Data;
use Hanafalah\MicroTenant\Commands\EnvironmentCommand;
use Hanafalah\MicroTenant\Commands\Impersonate\Concerns\HasImpersonate;
use Hanafalah\MicroTenant\Facades\MicroTenant;
use Illuminate\Support\Str;
use function Laravel\Prompts\select;

class ImpersonateMigrateCommand extends EnvironmentCommand
{
    use HasCache, HasArray, HasImpersonate;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'impersonate:migrate 
                                {--app= : The type of the application}
                                {--group= : The type of the group}
                                {--tenant= : The type of the tenant}
                                {--app_id= : The id of the application}
                                {--group_id= : The id of the group}
                                {--tenant_id= : The id of the tenant}
                            ';

    protected $__cache_data;
    protected $__choosed_impersonate;

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'This command is used to impersonate as a certain tenant application.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        config(['micro-tenant.installing' => true]);
        $this->findApplication(function($project){
            $this->findGroup($project,function($group){
                $this->findTenant($group);
                $this->impersonateConfig([
                    "project"    => $this->__application,
                    "group"      => $this->__group,
                    "tenant"     => $this->__tenant
                ]);
                MicroTenant::tenantImpersonate($this->__tenant);
                $this->__tenant_path = tenant_path($this->__tenant->name);
                $this->setImpersonateNamespace();

                if ($this->option('app'))    $field = 'project';
                if ($this->option('group'))  $field = 'group';
                if ($this->option('tenant')) $field = 'tenant';
                if (!isset($field)) $field = select('Choose impersonate', ['project', 'group', 'tenant']);
                $this->__choosed_impersonate = $field ?? 'tenant';

                $impersonate     = $this->__impersonate[$field];
                $tenant_path     = $impersonate['paths']['base_path'];
                $migration_path  = $tenant_path.$impersonate['libs']['migration'];
                switch ($field) {
                    case 'project':
                        $this->overrideCaller($this->__application,[$migration_path,$migration_path.DIRECTORY_SEPARATOR.'changes']);
                        
                        $allGroup = $this->getAllGroupWithApp($this->__application->id);
                        if (isset($allGroup) && count($allGroup) > 0) {
                            $path        = $migration_path.DIRECTORY_SEPARATOR.'centrals';
                            $tenant_path = $migration_path.DIRECTORY_SEPARATOR.'tenants';
                            foreach($allGroup as $group) {
                                $this->overrideCaller($group,[$path,$path.DIRECTORY_SEPARATOR.'changes']);
                                $allTenants  = $this->getAllTenantWithGroup($group->id);
                                if(isset($allTenants) && count($allTenants) > 0) {
                                    foreach($allTenants as $tenant) {
                                        $this->overrideCaller($tenant,[$tenant_path,$tenant_path.DIRECTORY_SEPARATOR.'changes']);
                                    }
                                }
                            }
                        }
                    break;
                    case 'group':
                        $this->overrideCaller($this->__group,[$migration_path,$migration_path.DIRECTORY_SEPARATOR.'changes']);
        
                        $allTenants  = $this->getAllTenantWithGroup($this->__group->id);
                        $tenant_path = $migration_path.DIRECTORY_SEPARATOR."tenants";
                        foreach($allTenants as $tenant) {
                            $this->overrideCaller($tenant,[$tenant_path,$tenant_path.DIRECTORY_SEPARATOR.'changes']);
                        }
                    break;
                    default:
                        $this->overrideCaller($this->__tenant,[$migration_path,$migration_path.DIRECTORY_SEPARATOR.'changes']);
                    break;
                }
            });
        });    
    }

    private function getAllGroupWithApp($id) {
        return $this->TenantModel()->central()->where('parent_id',$id)->select('id','name','props')->orderBy('id')->get();
    }

    private function getAllTenantWithGroup($id) {
        return$this->TenantModel()->select('id','name','props')->parentId($id)->orderBy('id')->get();
    }

    private function overrideConfig(mixed $path) {
        $path = Str::replace('\\',DIRECTORY_SEPARATOR,$path);
        if (is_array($path)){
            foreach ($path as &$value) {
                $value = Str::replace('\\',DIRECTORY_SEPARATOR,$value);
            }
        }
        config(['tenancy.migration_parameters.--path' => $path]);
    }

    private function overrideCaller($tenant,mixed $paths) {
        $paths = $this->mustArray($paths);
        foreach ($paths as $path) {
            $this->overrideConfig($path);
            $this->caller($tenant);
        }
    }

    private function caller($tenant) {
        $this->call("tenants:migrate", [    
            '--path'    => config('tenancy.migration_parameters.--path'),
            '--tenants' => $tenant->id
        ]);
    }
}
