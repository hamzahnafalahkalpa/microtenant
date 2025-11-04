<?php

namespace Hanafalah\MicroTenant\Commands\Impersonate\Concerns;

use function Laravel\Prompts\select;

use Illuminate\Support\Str;

trait HasImpersonate{
    
    protected $__impersonate = [];

    protected $__application, $__group, $__tenant;
    protected array $__select = ['id','parent_id','name', 'flag','props'];
    protected string $__tenant_path;


    protected function findApplication(callable $callback): self{
        $application = $this->TenantModel()->whereNull('parent_id')->select($this->__select);
    
        if ($app_id = $this->option('app_id')) {
            $application = $application->find($app_id);
        } else {
            $applications = $application->orderBy('name')->get();
            $choose_app = select(
                label: 'Choose a project',
                options: $applications->pluck('name')->toArray()
            );
            $application = $applications->firstWhere('name', $choose_app);
        }
    
        $this->__application = $application;
        $this->info('Used Project: ' . $application->name);
        $callback($application);
        return $this;
    }
    
    protected function findGroup(mixed $application = null, ?callable $callback = null){
        if (isset($application) && $application->has_group){
            $group_model = $this->TenantModel()->central()->where('parent_id', $application->getKey())->select($this->__select);
        
            if ($group_id = $this->option('group_id')) {
                $group = $group_model->find($group_id);
            } else {
                $groups = $group_model->orderBy('name')->get();
            }
        
            if (isset($group_id) || count($groups) > 0) {
                if (!isset($group_id) && !$this->__skip) {
                    $choose_group = select(
                        label: 'Choose a group',
                        options: $groups->pluck('name')->toArray()
                    );
                    $group = $group_model->firstWhere('name', $choose_group);
                }
                if (isset($group)){
                    $this->__group = $group;
                    $this->info('Used Group: ' . $group->name);
                }
                if (isset($callback)) $callback($group ?? null);
            } else {
                $this->info('No groups found in central tenant.');
            }
        }else{
            if (isset($callback)) $callback(null);
        }
    }
    
    protected function findTenant(mixed $group = null){
        if (isset($group) && $group->has_tenant){
            $tenant_model = $this->TenantModel()->select($this->__select)->addSelect('flag')->parentId($group->getKey());
            if ($tenant_id = $this->option('tenant_id')) {
                $tenant = $tenant_model->find($tenant_id);
            } else {
                $tenants = $tenant_model->orderBy('name')->get();
            }
        
            if (isset($tenant_id) || count($tenants) > 0) {
                if (!isset($tenant_id) && !$this->__skip) {
                    $choose_tenant = select(
                        label: 'Choose a tenant',
                        options: $tenants->pluck('name')->toArray()
                    );
                    $tenant = $tenant_model->firstWhere('name', $choose_tenant);
                }
                if (isset($tenant)){
                    $this->__tenant = $tenant;
                    $this->__tenant_path = tenant_path($this->__tenant->name);
                    $this->info('Used Tenant: ' . $tenant->name);
                }
            } else {
                $this->info('No tenants found in group.');
            }
        }
    }

    protected function setImpersonateNamespace(){
        $this->__impersonate['project']['namespace'] = 'Projects\\'.\class_name_builder($this->__application->name);
        if (isset($this->__group)) $this->__impersonate['group']['namespace']   = \class_name_builder($this->__application->name).'\\'.\class_name_builder($this->__group->name);
        if (isset($this->__tenant)) $this->__impersonate['tenant']['namespace']  = \class_name_builder($this->__group->name).'\\'.\class_name_builder($this->__tenant->name);
    }

    protected function impersonateConfig(array $config_path) : self{
        foreach($config_path as $key => $config) {
            if(isset($config)) {
                $path         = $config->path.DIRECTORY_SEPARATOR.Str::kebab($config->name).'/src/'.$config['config']['generates']['config']['path'];
                $config       = $path.DIRECTORY_SEPARATOR.'config.php';
                if (is_file($config)){
                    $this->basePathResolver($config);
                    $config       = include($config);
                    $this->__impersonate[$key] = $config;
                }
            }
        }

        return $this;
    }
}