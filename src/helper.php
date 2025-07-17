<?php

use Hanafalah\MicroTenant\Contracts\MicroTenant;

if (! function_exists('microtenant')) {
    function microtenant()
    {
        return app()->make(MicroTenant::class);
    }
}

if (! function_exists('tenant_path')) {
    function tenant_path($path = ''){
        return config('micro-tenant.laravel-package-generator.patterns.tenant.published_at') . '/' . $path;
    }
}

if (! function_exists('repository_path')) {
    function repository_path($path = ''){
        return base_path('repositories');
    }
}

if (! function_exists('app_version_path')) {
    function app_version_path($path = '')
    {
        return base_path(config('laravel-package-generator.patterns.project.published_at') . '/' . $path);
    }
}
