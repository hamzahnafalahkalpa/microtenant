<?php

namespace Hanafalah\MicroTenant\Commands\Impersonate\Concerns;

use function Laravel\Prompts\select;

use Hanafalah\MicroTenant\Facades\MicroTenant;
use Illuminate\Support\Str;

trait HasImpersonate{
    
    protected $__impersonate = [];

    protected $__application, $__group, $__tenant;
    protected array $__select = ['id','parent_id','name','props'];
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
    
    protected function findGroup($application, callable $callback){
        $group = $this->TenantModel()->central()->where('parent_id', $application->getKey())->select($this->__select);
    
        if ($group_id = $this->option('group_id')) {
            $group = $group->find($group_id);
        } else {
            $groups = $group->orderBy('name')->get();
        }
    
        if (isset($group_id) || count($groups) > 0) {
            if (!isset($group_id)) {
                $choose_group = select(
                    label: 'Choose a group',
                    options: $groups->pluck('name')->toArray()
                );
                $group = $groups->firstWhere('name', $choose_group);
            }
    
            $this->__group = $group;
            $this->info('Used Group: ' . $group->name);
    
            $callback($group);
        } else {
            $this->info('No groups found in central tenant.');
        }
    }
    
    protected function findTenant($group){
        $tenant = $this->TenantModel()->select($this->__select)->addSelect('flag')->parentId($group->getKey());
        if ($tenant_id = $this->option('tenant_id')) {
            $tenant = $tenant->find($tenant_id);
        } else {
            $tenants = $tenant->orderBy('name')->get();
        }
    
        if (isset($tenant_id) || count($tenants) > 0) {
            if (!isset($tenant_id)) {
                $choose_tenant = select(
                    label: 'Choose a tenant',
                    options: $tenants->pluck('name')->toArray()
                );
                $tenant = $tenants->firstWhere('name', $choose_tenant);
            }
    
            $this->__tenant = $tenant;
            $this->info('Used Tenant: ' . $tenant->name);
        } else {
            $this->info('No tenants found in group.');
        }
    }

    protected function setImpersonateNamespace(){
        $this->__impersonate['project']['namespace'] = 'Projects\\'.\class_name_builder($this->__application->name);
        $this->__impersonate['group']['namespace']   = \class_name_builder($this->__application->name).'\\'.\class_name_builder($this->__group->name);
        $this->__impersonate['tenant']['namespace']  = \class_name_builder($this->__group->name).'\\'.\class_name_builder($this->__tenant->name);
    }

    protected function impersonateConfig(array $config_path,) : self{
        foreach($config_path as $key => $config) {
            if(isset($config)) {
                $path         = $config->path.DIRECTORY_SEPARATOR.Str::kebab($config->name).'/src/'.$config['config']['generates']['config']['path'];
                $config       = $path.DIRECTORY_SEPARATOR.'config.php';
                $config       = include($config);
                $this->__impersonate[$key] = $config;
            }
        }

        return $this;
    }
}