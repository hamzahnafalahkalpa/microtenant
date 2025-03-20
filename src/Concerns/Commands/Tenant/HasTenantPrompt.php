<?php

namespace Zahzah\MicroTenant\Concerns\Commands\Tenant;

trait HasTenantPrompt
{
    protected $__ask_package_name;
    protected $__ask_central_tenant;
    protected $__ask_tenant_name;
    protected string $__app_namespace;
    protected string $__central_namespace;
    protected string $__tenant_namespace;

    protected $__app_config, $__tenant_config;

    protected function askTenantName()
    {
        $existed = false;
        do {
            $package_name = $this->ask('Enter tenant name ?');
        } while (!isset($package_name) || $existed);
        $this->__ask_tenant_name = $package_name;
        $package_name = \class_name_builder($package_name);
        $this->info('Used Service Name: ' . $package_name);
        return $this->__ask_package_name = $package_name;
    }

    protected function askCentralTenant()
    {
        $tenants = $this->TenantModel()->CFlagIn('FLAG_CENTRAL_TENANT')
                        ->where('parent_id',$this->__app_tenant->getKey())
                        ->central()->pluck('name');
        $tenants = collect(['new'])->merge($tenants);
        $choice = $this->choice('Choose central tenant ?', $tenants->toArray(), null);
        if ($choice == 'new') {
            $this->__ask_central_tenant = $this->TenantModel()->create([
                'name'       => $this->ask('Enter central tenant name ?'),
                'flag'       => $this->TenantModel()::FLAG_CENTRAL_TENANT,
                'parent_id'  => $this->__app_tenant->getKey()
            ]);
            $this->askWannaMakeRepo($this->__ask_central_tenant->name);
            if (isset($this->__slug)) $this->__ask_central_tenant->slug_bitbucket = $this->__slug;
        } else {
            $this->__ask_central_tenant = $this->TenantModel()->central()->where('name', $choice)->first();
        }
        $this->__central_namespace = \class_name_builder($this->__ask_central_tenant->name);
        $fields = [
            'path'         => $this->__app_config['path'].'/'.$this->__ask_app->name.'/'.$this->__central_namespace,
            'config_path'  => $this->withSource().'/'.$this->__tenant_config['generate']['config']['path'].'/config.php',
            'provider'     => $this->__app_namespace.'\\'.$this->__central_namespace.'\\'.$this->__central_namespace.'ServiceProvider',
            'with_source'  => $this->isNeedSource(),
            'app'          => [
                'id'       => $this->__ask_app->id,
                'name'     => $this->__ask_app->name,
                'provider' => $this->__ask_app->provider
            ]
        ];
        foreach ($fields as $key => $field) $this->__ask_central_tenant->{$key} = $field; 
        $this->__ask_central_tenant->save();
        
        $this->info('Used Central Tenant: ' . $choice);
        return $this->__ask_central_tenant;
    }

    protected function getTenantConfig(): self{
        $this->__tenant_config = static::$__services['tenant'];
        return $this;
    }

    protected function getAppConfig(): self{
        $this->__app_config = static::$__services['app_version'];
        return $this;
    }
}
