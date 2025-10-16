<?php

namespace Hanafalah\MicroTenant;

use Laravel\Sanctum\Sanctum;
use Hanafalah\MicroTenant\MicroTenant;
use Hanafalah\ApiHelper\Facades\ApiAccess as FacadesApiAccess;
use Hanafalah\MicroTenant\Facades\MicroTenant as FacadesMicroTenant;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

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
        if (session_status() === PHP_SESSION_NONE) session_start();
        $this->app->booted(function () {
            try {
                Sanctum::usePersonalAccessTokenModel($this->PersonalAccessTokenModelInstance());
                config(['api-helper.expiration' => null]);
                if (config('micro-tenant.monolith')){
                    if (isset($_SESSION['tenant'])){
                        FacadesMicroTenant::tenantImpersonate($_SESSION['tenant']);
                        $tenant = $_SESSION['tenant'];
                    }
                    if (isset($_SESSION['user'])) Auth::setUser($_SESSION['user']);
                }else{
                    $microtenant = FacadesMicroTenant::getMicroTenant();
                    if (isset($microtenant)) $tenant = $microtenant->tenant->model;
                }
                if (isset($tenant)) tenancy()->initialize($tenant);
            } catch (\Exception $e) {
                abort(401);
            }
        });

        try {
            if (request()->headers->has('AppCode')) {
                try {
                    FacadesApiAccess::init()->accessOnLogin(function ($api_access) {
                        Auth::setUser($api_access->getUser());
                    });
                } catch (\Exception $e) {
                    abort(401);
                }
            } else {
                //FOR TESTING ONLY        
                if (config('micro-tenant.dev_mode') || config('micro-tenant.monolith')) {
                    $cache       = FacadesMicroTenant::getCacheData('impersonate');
                    $impersonate = cache()->tags($cache['tags'])->get($cache['name']);

                    if (isset($impersonate->tenant->model)) {
                        $model = $impersonate?->tenant?->model;
                        FacadesMicroTenant::tenantImpersonate($model);
                    }
                } else {
                    $login_schema = config('micro-tenant.login_schema');
                    if (isset($login_schema) && \class_exists($login_schema)) {
                        app($login_schema)->authenticate();
                    }
                }
            }
        } catch (\Exception $e) {
            abort(401);
            dd($e->getMessage());
        }
    }
}
