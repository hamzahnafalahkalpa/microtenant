<?php

namespace Hanafalah\MicroTenant\Commands\Impersonate;

use Hanafalah\LaravelSupport\Concerns\Support\HasArray;
use Hanafalah\LaravelSupport\Concerns\Support\HasCache;
use Hanafalah\MicroTenant\Facades\MicroTenant;
use Hanafalah\MicroTenant\Commands\EnvironmentCommand;
use Hanafalah\MicroTenant\Commands\Impersonate\Concerns\HasImpersonate;
use Illuminate\Support\Str;

class ImpersonateCacheCommand extends EnvironmentCommand
{
    use HasCache, HasArray, HasImpersonate;

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
        }else{
            $data = $this->setCache($this->__cache_data, function () {
                $this->findApplication(function($project){
                    $this->findGroup($project,function($group){
                        $this->findTenant($group);
                        $this->impersonateConfig([
                            "project"    => $this->__application,
                            "group"      => $this->__group,
                            "tenant"     => $this->__tenant
                        ]);
                        $this->__tenant_path = tenant_path($this->__tenant->name);
                        $this->setImpersonateNamespace();
                    });
                });                 
                $this->pathGenerator('tenant')
                     ->pathGenerator('group', Str::lower($this->__impersonate['group']['namespace']))
                     ->pathGenerator('project', Str::lower($this->__impersonate['project']['namespace']));

                $this->__impersonate['tenant']['migration_path']  = Str::replace('\\','/',$this->__impersonate['tenant']['paths']['installed'].'/'.$this->__impersonate['tenant']['libs']['migration']);
                $this->__impersonate['group']['migration_path']   = Str::replace('\\','/',$this->__impersonate['group']['paths']['installed'].'/'.$this->__impersonate['group']['libs']['migration']);
                $this->__impersonate['project']['migration_path'] = Str::replace('\\','/',$this->__impersonate['project']['paths']['installed'].'/'.$this->__impersonate['project']['libs']['migration']);

                $data = [
                    'project' => (Object) [
                        'config' => $this->__impersonate['project'],
                        'model'  => $this->__application
                    ],
                    'group' => (Object) [
                        'config' => $this->__impersonate['group'],
                        'model'  => $this->__group
                    ],
                    'tenant' => (Object) [
                        'config' => $this->__impersonate['tenant'],
                        'model'  => $this->__tenant
                    ]
                ];
                return (Object) $data;  
            },false);
            $this->info('Impersonate config: '.json_encode($data, JSON_PRETTY_PRINT));
            $tenant = $data?->tenant?->model ?? $this->__tenant;
            MicroTenant::tenantImpersonate($tenant);
            tenancy()->initialize($tenant);
        }
    }

    private function pathGenerator(string $module_path, string $name = ''): self{
        $base   = $this->__tenant_path;
        $config = &$this->__impersonate[$module_path];
        $config['paths']['installed']   = [$base];
        $config['paths']['installed'][] = $module_path == 'tenant' ? '' : 'vendor';
        $config['paths']['installed'][] = $name;
        $config['paths']['installed'][] = 'src';
        $config['paths']['installed']   = implode('/', $config['paths']['installed']);
        $config['paths']['installed']   = preg_replace('#/+#', '/', $config['paths']['installed']);
        return $this;
    }
}
