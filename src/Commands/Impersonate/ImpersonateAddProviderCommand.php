<?php

namespace Hanafalah\MicroTenant\Commands\Impersonate;

use stdClass;
use Hanafalah\MicroTenant\Commands\Impersonate\Concern\generatorHelperPath;
use Illuminate\Support\Str;
use Hanafalah\MicroTenant\Facades\MicroTenant;
use Hanafalah\MicroTenant\Models\Application\App;

class ImpersonateAddProviderCommand extends EnvironmentCommand
{
    use generatorHelperPath;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'impersonate:add-provider {--app} {--group} 
                                {--package= : The name of the provider e.g. hanafalah/module-user}
                                {--app_id= : The id of the app}
                                {--group_id= : The id of the group}
                                {--tenant_id= : The id of the tenant}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'command to add provider into specific module level';


    /**
     * Execute the console command.
     */
    public function handle()
    {
        // CHECKING EXISTING IMPERSONATE APP
        $impersonate = $this->getImpersonate();
        $model       = ($impersonate->model instanceof App)
            ? $impersonate->model->tenant
            : $impersonate->model;
        if (($package = $this->option('package')) == null) {
            $alias = $this->ask('Add new package alias e.g. hanafalah/module-user !');
            $namespace = $this->ask('Add new provider namespace, blank it for using default!');
        }
        if (isset($alias)) {
            MicroTenant::addPackage($model, $alias, $namespace);
            $this->recache();
        }
    }
}
