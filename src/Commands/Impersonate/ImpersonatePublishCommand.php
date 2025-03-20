<?php

namespace Hanafalah\MicroTenant\Commands\Impersonate;

use Hanafalah\LaravelSupport\Concerns\Support\HasArray;
use Hanafalah\LaravelSupport\Concerns\Support\HasCache;
use Hanafalah\MicroTenant\Commands\Impersonate\Concern\generatorHelperPath;
use Hanafalah\MicroTenant\Models\Application\App;

class ImpersonatePublishCommand extends EnvironmentCommand
{
    use HasCache, HasArray, generatorHelperPath;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'impersonate:publish {--app} {--group}
                            {--app_id= : The id of the app}
                            {--group_id= : The id of the group}
                            {--tenant_id= : The id of the tenant}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'command to publish all module requirements';


    /**
     * Execute the console command.
     */
    public function handle()
    {
        // CHECKING EXISTING IMPERSONATE APP
        $impersonate = $this->getImpersonate();
        if ($impersonate->model instanceof App) {
            $model = &$impersonate->model->tenant;
        } else {
            $model = &$impersonate->model;
        }
        $packages = $model->packages;
        if (isset($packages)) {
            foreach ($packages as $key => &$package) {
                $provider = $this->replacement($package['provider']);
                if (!isset($package['published'])) {
                    $this->info('Publishing ' . $provider . '...');
                    $this->call('vendor:publish', [
                        '--provider' => $provider,
                        '--tag'      => ['migrations', 'data']
                    ]);
                    $package['published'] = true;
                } else {
                    $this->info($provider . ' is already published...');
                }
            }
            $model->setAttribute('packages', $packages);
            $model->save();
            $this->recache();
        }
    }
}
