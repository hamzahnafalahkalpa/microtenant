<?php

namespace Hanafalah\MicroTenant\Commands\Impersonate;

use Hanafalah\LaravelSupport\Concerns\Support\HasArray;
use Hanafalah\LaravelSupport\Concerns\Support\HasCache;
use Hanafalah\MicroTenant\Facades\MicroTenant;
use Hanafalah\MicroTenant\Commands\EnvironmentCommand;
use Hanafalah\MicroTenant\Models\Application\App;
use Illuminate\Support\Str;

class ImpersonateCommand extends EnvironmentCommand
{
    use HasCache, HasArray;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'impersonate:cache 
                                {--forget : Forgets the current cache}
                                {--app_id= : The id of the application}
                                {--group_id= : The id of the group}
                                {--tenant_id= : The id of the tenant}
                            ';

    protected $__cache_data;

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'This command is used to impersonate as a certain tenant application.';

    protected $__impersonate = [];

    private $__application, $__group, $__tenant;
    private array $__select = ['id', 'parent_id', 'name', 'props'];
    private string $__tenant_path;

    private function findApplication(callable $callback): self
    {
        $application = $this->AppModel()->with('tenant')->select($this->__select);
        if ($app_id = $this->option('app_id')) {
            $application  = $application->find($app_id);
        } else {
            $applications = $application->orderBy('name')->get();
            $choose_app   = $this->choice('Choose an application', $applications->pluck('name')->toArray());
            $application  = $applications->firstWhere('name', $choose_app);
        }
        $this->__application = $application;
        $this->info('Used Application: ' . $application->name);
        $callback($application);
        return $this;
    }

    private function findGroup($application, callable $callback)
    {
        $group = $this->TenantModel()->central()->where('parent_id', $application->tenant->getKey())->select($this->__select);
        if ($group_id = $this->option('group_id')) {
            $group = $group->find($group_id);
        } else {
            $groups = $group->orderBy('name')->get();
        }
        if (isset($group_id) || count($groups) > 0) {
            if (!isset($group_id)) {
                $choose_group = $this->choice('Choose a group', $groups->pluck('name')->toArray());
                $group        = $groups->firstWhere('name', $choose_group);
            }
            $this->__group = $group;
            $this->info('Used Group: ' . $group->name);

            $callback($group);
        } else {
            $this->info('No groups found in central tenant.');
        }
    }

    private function findTenant($group)
    {
        $tenant = $this->TenantModel()->select($this->__select)->addSelect('flag')->parentId($group->getKey());
        if ($tenant_id = $this->option('tenant_id')) {
            $tenant = $tenant->find($tenant_id);
        } else {
            $tenants = $tenant->orderBy('name')->get();
        }
        if (isset($tenant_id) || count($tenants) > 0) {
            if (!isset($tenant_id)) {
                $choose_tenant = $this->choice('Choose a tenant', $tenants->pluck('name')->toArray());
                $tenant        = $tenants->firstWhere('name', $choose_tenant);
            }
            $this->__tenant = $tenant;
            tenancy()->initialize($this->__tenant);
            $this->info('Used Tenant: ' . $tenant->name);
        } else {
            $this->info('No tenants found in group.');
        }
    }

    private function setImpersonateNamespace()
    {
        $this->__impersonate['app']['namespace']     = config('module-version.application.namespace') . '\\' . \class_name_builder($this->__application->name);
        $this->__impersonate['group']['namespace']   = \class_name_builder($this->__application->name) . '\\' . \class_name_builder($this->__group->name);
        $this->__impersonate['tenant']['namespace']  = \class_name_builder($this->__group->name) . '\\' . \class_name_builder($this->__tenant->name);
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->__cache_data = MicroTenant::getCacheData('impersonate');

        $forget = $this->option('forget');
        if ($forget) {
            $this->forgetTags($this->__cache_data['tags']);
            $this->info('Cache cleared.');
        } else {

            $data = $this->setCache($this->__cache_data, function () {
                $this->findApplication(function ($app) {
                    $this->findGroup($app, function ($group) {
                        $this->findTenant($group);
                        $this->impersonateConfig([
                            "app"    => $this->__application,
                            "group"  => $this->__group,
                            "tenant" => $this->__tenant
                        ]);
                        $this->__tenant_path = tenant_path($this->__tenant->name);
                        $this->setImpersonateNamespace();
                    });
                });
                $this->pathGenerator('tenant')
                    ->pathGenerator('group', Str::lower($this->__impersonate['group']['namespace']))
                    ->pathGenerator('app', Str::lower($this->__impersonate['app']['namespace']));

                $this->__impersonate['tenant']['migration_path'] = Str::replace('\\', '/', $this->__impersonate['tenant']['paths']['installed'] . '/' . $this->__impersonate['tenant']['libs']['migration']);
                $this->__impersonate['group']['migration_path']  = Str::replace('\\', '/', $this->__impersonate['group']['paths']['installed'] . '/' . $this->__impersonate['group']['libs']['migration']);
                $this->__impersonate['app']['migration_path']    = Str::replace('\\', '/', $this->__impersonate['app']['paths']['installed'] . '/' . $this->__impersonate['app']['libs']['migration']);

                $data = [
                    'application' => (object) [
                        'config' => $this->__impersonate['app'],
                        'model'  => $this->__application
                    ],
                    'group' => (object) [
                        'config' => $this->__impersonate['group'],
                        'model'  => $this->__group
                    ],
                    'tenant' => (object) [
                        'config' => $this->__impersonate['tenant'],
                        'model'  => $this->__tenant
                    ]
                ];
                return (object) $data;
            }, false);

            $this->info('Impersonate config: ' . json_encode($data, JSON_PRETTY_PRINT));
        }
    }

    private function pathGenerator(string $module_path, string $name = ''): self
    {
        $base   = $this->__tenant_path;
        $config = &$this->__impersonate[$module_path];
        $config['paths']['installed']   = [$base];
        $config['paths']['installed'][] = $module_path == 'tenant' ? '' : 'vendor';
        $config['paths']['installed'][] = $name;
        $config['paths']['installed'][] = $config['with-source'] ? 'src' : '';
        $config['paths']['installed']   = implode('/', $config['paths']['installed']);
        $config['paths']['installed']   = preg_replace('#/+#', '/', $config['paths']['installed']);
        return $this;
    }

    protected function impersonateConfig(array $config_path): self
    {
        foreach ($config_path as $key => $config) {
            if (isset($config)) {
                if ($config instanceof App) $config = $config->tenant;
                $path         = $config->path;
                $path        .= $config->config_path;
                $config       = base_path($path);
                $config       = include($config);
                $this->__impersonate[$key] = $config;
            }
        }

        return $this;
    }
}
