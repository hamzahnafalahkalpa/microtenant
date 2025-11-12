<?php

namespace Hanafalah\MicroTenant;

use Hanafalah\MicroTenant\Facades\MicroTenant as FacadesMicroTenant;
use Hanafalah\MicroTenant\MicroTenant;

class MicroTenantServiceProvider extends MicroServiceProvider
{
    public function register()
    {
        $this->registerMainClass(MicroTenant::class)
            ->registerCommandService(Providers\CommandServiceProvider::class)
            ->registerConfig(function () {
                $this->mergeConfigWith('tenancy', 'tenancy')
                     ->setLocalConfig('micro-tenant');
            })->registers([
                '*',
                'Provider' => function () {
                    $this->validProviders([
                        \app_path('Providers/MicroTenantServiceProvider.php') => 'App\Providers\MicroTenantServiceProvider',
                    ]);
                },
                'Namespace' => function () {
                    $this->publishes([
                        $this->getAssetPath('database/seeders/Installation') => \database_path('seeders/Installation'),
                    ], 'seeders');

                    $this->publishes([
                        $this->getAssetPath('stubs/MicroTenantServiceProvider.php.stub') => app_path('Providers/MicroTenantServiceProvider.php'),
                    ], 'providers');
                }
            ]);
        $this->registerEnvironment();
    }

    public function boot()
    {
        $this->registerDatabase()->registerModel();
        $this->overrideTenantConfig()
            ->overrideLaravelSupportConfig()
            ->overrideMergePackageConfig()
            ->overrideAuthConfig();
        // Sanctum::usePersonalAccessTokenModel($this->PersonalAccessTokenModelInstance());
        // $this->app->booted(function () {
            // try {
            //     config(['api-helper.expiration' => null]);
            //     if (config('micro-tenant.monolith')){
            //         if (isset($_SESSION['tenant'])){
            //             FacadesMicroTenant::tenantImpersonate($_SESSION['tenant']);
            //             $tenant = $_SESSION['tenant'];
            //         }
            //         if (isset($_SESSION['user'])) Auth::setUser($_SESSION['user']);
            //     }else{
            //         $microtenant = FacadesMicroTenant::getMicroTenant();
            //         if (isset($microtenant)) $tenant = $microtenant->tenant->model;
            //     }
            //     if (isset($tenant)) {
            //         tenancy()->end();
            //         tenancy()->initialize($tenant);
            //     }
            // } catch (\Exception $e) {
            //     abort(401);
            // }
        // });

        // try {
            // && config('micro-tenant.direct_provider_access')
            // if (request()->headers->has('appcode')) {
            //     try {
            //         FacadesMicroTenant::accessOnLogin();
            //         // FacadesApiAccess::init()->accessOnLogin(function ($api_access) {
            //         //     Auth::setUser($api_access->getUser());
            //         // });
            //     } catch (\Exception $e) {
            //         dd($e->getMessage());
            //     }
            // } else {
            //     if (config('micro-tenant.direct_provider_access')) {
            //         $cache       = FacadesMicroTenant::getCacheData('impersonate');
            //         $impersonate = cache()->tags($cache['tags'])->get($cache['name']);

            //         $impersonate_model = $impersonate?->tenant ?? $impersonate?->group ?? $impersonate?->project;
            //         if (isset($impersonate_model)) {
            //             $model = $impersonate_model->model;
            //             FacadesMicroTenant::tenantImpersonate($model);
            //         }
            //     } 
            //     // else {
            //     //     $login_schema = config('micro-tenant.login_schema');
            //     //     if (isset($login_schema) && \class_exists($login_schema)) {
            //     //         app($login_schema)->authenticate();
            //     //     }
            //     // }
            // }
        // } catch (\Exception $e) {
        //     dd('e');
        //     dd($e->getMessage());
        // }
    }
}
