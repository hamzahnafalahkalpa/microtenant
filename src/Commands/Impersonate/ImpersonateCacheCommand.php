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
                                {--skip= : For skeip if other option not existed}
                            ';

    protected $__cache_data;
    protected $__data_cache;
    protected $__skip;

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
        $this->__skip = $this->option('skip') ?? false;
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
                        $this->setImpersonateNamespace();
                    });
                });      
                $this->__data_cache = [];
                $this->dataInit('tenant',$this->__tenant)
                     ->dataInit('group',$this->__group)
                     ->dataInit('project',$this->__application);
                $data = (Object) $this->__data_cache;  
                return $data;
            },false);
            $this->info('Impersonate Cache Completed');
            $tenant = $data?->tenant?->model ?? $this->__tenant ?? $data?->project->model;
            MicroTenant::tenantImpersonate($tenant);
            $this->info('Tenant Impersonate: '.$tenant->name.' with id '.$tenant->getKey());
        }
    }

    private function dataInit(string $type, ?object $model = null): self{
        if (isset($model)){
            $this->__tenant_path = $model->path.DIRECTORY_SEPARATOR.$model->name;
            $this->pathGenerator($type, Str::lower($this->__impersonate[$type]['namespace']));

            if (isset($this->__impersonate[$type]['libs'])) $this->__impersonate[$type]['migration_path']  = Str::replace('\\','/',$this->__impersonate[$type]['paths']['installed'].'/'.$this->__impersonate[$type]['libs']['migration'] ?? 'Migration');

            $this->__data_cache[$type] = (object) [
                'config' => $this->__impersonate[$type],
                'model'  => $model
            ];
        }
        return $this;
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
