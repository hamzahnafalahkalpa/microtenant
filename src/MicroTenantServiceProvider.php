<?php

declare(strict_types=1);

namespace Hanafalah\MicroTenant;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Http\Kernel;
use Illuminate\Support\Facades\Route;
use Laravel\Sanctum\Sanctum;
use Hanafalah\MicroTenant\MicroTenant;
use Hanafalah\ApiHelper\Facades\ApiAccess as FacadesApiAccess;
use Hanafalah\LaravelSupport\Middlewares\Middleware;
use Hanafalah\MicroTenant\Facades\MicroTenant as FacadesMicroTenant;

class MicroTenantServiceProvider extends MicroServiceProvider
{
    public function register()
    {
        $this->__is_multitenancy = true;
        $this->registerMainClass(MicroTenant::class)
            ->registerCommandService(Providers\CommandServiceProvider::class)
            ->registerEnvironment()
            ->registerConfig(function () {
                $this->mergeConfigWith('tenancy', 'tenancy')
                    ->setLocalConfig('micro-tenant');
            })
            ->registers([
                '*',
                'Provider' => function () {
                    $this->registerTenant();
                    $this->validProviders([
                        \app_path('Providers/MicroTenantServiceProvider.php') => 'App\Providers\MicroTenantServiceProvider',
                    ]);
                },
                'Namespace' => function () {
                    $this->publishes([
                        $this->getAssetPath('database/seeders/Installation') => \database_path('seeders/Installation'),
                    ], 'seeders');

                    $this->publishes([
                        $this->getAssetPath('stubs/provider-app.stub') => app_path('Providers/MicroTenantServiceProvider.php'),
                    ], 'providers');
                },
                'Services' => function () {
                    $this->binds([
                        Contracts\MicroTenant::class             => new MicroTenant(),
                        Contracts\Models\Tenant::class           => function ($app) {
                            return $app[MicroTenant::class]->tenant;
                        },
                        Contracts\FileRepositoryInterface::class => FileRepository::class
                    ]);
                }
            ], ['Database', 'Model']);
    }

    public function boot(Kernel $kernel)
    {
        $this->registerDatabase()->registerModel();
        $this->overrideTenantConfig()
            ->overrideLaravelSupportConfig()
            ->overrideModuleVersionConfig()
            ->overrideAuthConfig();
        $this->app->booted(function ($app) use ($kernel) {
            Sanctum::usePersonalAccessTokenModel($this->PersonalAccessTokenModelInstance());
        });

        if (request()->headers->has('AppCode')) {
            FacadesApiAccess::init()->accessOnLogin(function ($api_access) use ($kernel) {
                FacadesMicroTenant::onLogin($api_access);
            });
        } else {
            //FOR TESTING ONLY             
            if ((config('micro-tenant.dev_mode') && app()->environment('local')) || config('micro-tenant.superadmin')) {
                $cache       = FacadesMicroTenant::getCacheData('impersonate');
                $impersonate = cache()->tags($cache['tags'])->get($cache['name']);
                if (isset($impersonate->tenant->model)) {
                    FacadesMicroTenant::tenantImpersonate($impersonate->tenant->model);
                }
            } else {
                $login_schema = config('micro-tenant.login_schema');
                if (isset($login_schema) && \class_exists($login_schema)) {
                    app($login_schema)->authenticate();
                }
            }
        }
    }
}
