<?php

namespace Hanafalah\MicroTenant\Commands;

use Aibnuhibban\BitbucketLaravel\Traits\BitbucketTrait;

class InstallImpersonateMakeCommand extends EnvironmentCommand
{
    use BitbucketTrait;
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'impersonate:install';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command ini digunakan untuk menambahkan skema aplikasi baru';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $applications = $this->AppModel()->select('id', 'name', 'props')
            ->orderBy('name')->get();
        $choose_app   = $this->choice('Choose an application', $applications->pluck('name')->toArray());
        $application  = $applications->firstWhere('name', $choose_app);
        $this->info('Used Application: ' . $choose_app);

        $needs_group = $this->confirm('Do you need to impersonate a group?', true);
        if ($needs_group) {
            $groups       = $this->TenantModel()->central()->whereHas('modelHasApp', function ($q) use ($application) {
                $q->where($application->getForeignKey(), $application->id);
            })->select('id', 'name', 'props')->orderBy('name')->get();

            if (count($groups) > 0) {
                $choose_group = $this->choice('Choose a group', $groups->pluck('name')->toArray());
                $group        = $groups->firstWhere('name', $choose_group);
                $this->info('Used Group: ' . $choose_group);

                $needs_tenant  = $this->confirm('Do you need to impersonate a tenant?', true);
                if ($needs_tenant) {
                    $tenants       = $this->TenantModel()->select('id', 'name', 'props')->parentId($group->getKey())->orderBy('name')->get();
                    if (count($tenants) > 0) {
                        $choose_tenant = $this->choice('Choose a tenant', $tenants->pluck('name')->toArray());
                        $tenant        = $tenants->firstWhere('name', $choose_tenant);
                        $this->info('Used Tenant: ' . $choose_tenant);
                    } else {
                        $this->info('No tenants found in group.');
                    }
                }
            } else {
                $this->info('No groups found in central tenant.');
            }
        }

        // Clone application from bitbucket
        $ask = $this->confirm('Do you need to clone the application?', true);
        if ($ask && !is_dir(base_path($application->tenant->path))) {
            $this->info('Cloning application from bitbucket');
            $this->cloneRepository($application->tenant->vcs_remote, $application->tenant->path);
        }

        // Clone Gorup
        if (isset($choose_group)) {
            $ask = $this->confirm('Do you need to clone the group?', true);
            if ($ask && !is_dir(base_path($group->path))) {
                $this->info('Cloning group from bitbucket');
                $this->cloneRepository($group->vcs_remote, $group->path);
            }
        }

        if (isset($choose_tenant)) {
            $ask = $this->confirm('Do you need to clone the tenant?', true);
            if ($ask && !is_dir(base_path($tenant->path))) {
                $this->info('Cloning tenant from bitbucket');
                $this->cloneRepository($tenant->vcs_remote, $tenant->path);
            }
        }
    }
}
