<?php

namespace Zahzah\MicroTenant\Commands;

use Symfony\Component\Process\Process;
use Zahzah\MicroTenant\Concerns\Commands as ConcernsCommand;
use Zahzah\MicroTenant\Concerns\Commands\Bitbucket\HasBitbucketPrompt;
use Zahzah\MicroTenant\Concerns\Commands\HasGeneratorAction;
use Zahzah\MicroTenant\Concerns\Commands\Tenant\HasService;

class TenantMakeCommand extends EnvironmentCommand
{
    use ConcernsCommand\Tenant\HasTenantPrompt;
    use HasGeneratorAction {
        HasGeneratorAction::askAppVersion insteadof HasService;
    }
    use HasService;
    use HasBitbucketPrompt;

    protected array $__local_paths = [], $__config_libs = [];

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'micro:add-tenant {--namespace= : Tenant Name}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command ini digunakan untuk menambahkan generate tenant baru';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        if ($this->notReady()) $this->init();
        $this->setNeedSource()->setServiceName('tenant')
             ->chooseApplication();
        
        $this->__app_namespace  = \class_name_builder($this->__ask_app->name);
        $this->getAppConfig()->getTenantConfig();
        $this->askCentralTenant();
        if (app()->environment() == 'local' && $this->__ask_central_tenant->wasRecentlyCreated) {
            $this->installingSchema($this->__ask_central_tenant, $this->__ask_app->name);
        }
        
        $this->askTenantName();
        $this->__tenant_model = $this->TenantModel()->updateOrCreate([
            'name'       => $this->__ask_tenant_name,
            'flag'       => $this->TenantModel()::FLAG_TENANT,
            'parent_id'  => $this->__ask_central_tenant->getKey()
        ]);

        $this->askWannaMakeRepo($this->__ask_tenant_name);
        if (isset($this->__slug)) $this->__tenant_model->slug_bitbucket = $this->__slug;

        $this->__tenant_namespace = class_name_builder($this->__tenant_model->name);
        $fields = [
            'path'         => $this->__tenant_config['path'].'/'.$this->__tenant_namespace,
            'with_source'  => $this->isNeedSource(),
            'provider'     => $this->__central_namespace.'\\'.$this->__tenant_namespace.'\\'.$this->__tenant_namespace.'ServiceProvider',
            'config_path'  => $this->withSource().'/'.$this->__tenant_config['generate']['config']['path'].'/config.php',
            'app'          => [
                'id'       => $this->__ask_app->getKey(),
                'name'     => $this->__ask_app->name,
                'provider' => $this->__ask_app->provider
            ],
            'group'      => [
                'id'       => $this->__ask_central_tenant->getKey(),
                'name'     => $this->__ask_central_tenant->name,
                'provider' => $this->__ask_central_tenant->provider
            ]
        ];
        foreach ($fields as $key => $field) $this->__tenant_model->{$key} = $field;
        $this->__tenant_model->save();
        $this->installingSchema($this->__tenant_model, $this->__ask_central_tenant->name);

        if (count($this->__repo) > 0) {
            $this->info('Creating repository in bitbucket');
            foreach ($this->__repo as $slug) {
                $workspace  = config('bitbucket.workspace');
                $repo       = $this->createRepository($workspace, $slug);
                $vcs_remote = $repo['links']['clone'][0]['href'];
                if ($slug == $this->__ask_central_tenant->slug_bitbucket) {
                    $this->__ask_central_tenant->vcs_remote = $vcs_remote;
                    $this->__ask_central_tenant->save();
                }
                if ($slug == $this->__tenant_model->slug_bitbucket) {
                    $this->__tenant_model->vcs_remote = $vcs_remote;
                    $this->__tenant_model->save();
                }
                $this->info('Creating Repository for ' . $slug);
            }
        }

        //DO PUBLISH VENDOR
        //DO TENANCY MIGRATE

        //DO COMPOSER INSTALL TO THAT PATH
        $tenant_config_path = $this->__tenant_config['path'].'/'.$this->__tenant_namespace;
        $process = new Process(['composer', 'install', '--no-interaction', '--no-progress', '--no-scripts'], $tenant_config_path);
        $process->run();
        $this->info('Composer install done at ' . $tenant_config_path);
    }

    private function installingSchema($model, $namespace)
    {
        config([
            'micro-tenant.microservices.tenant.namespace' => $namespace,
            'micro-tenant.microservices.tenant.path'      => dirname($model->path)
        ]);
        $this->__microtenant_config = config('micro-tenant');
        $this->setServiceName('tenant')->setChoosedService()->installing($model->name);
    }
}