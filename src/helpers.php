<?php

use Hanafalah\MicroTenant\Contracts\MicroTenantInterface;

if (! function_exists('microtenant')) {
    function microtenant()
    {
        return app()->make(MicroTenantInterface::class);
    }
}

if (! function_exists('tenant_path')) {
    function tenant_path($path = '')
    {
        return base_path(config('micro-tenant.microservices.tenant.path') . '/' . $path);
    }
}

if (! function_exists('repository_path')) {
    function repository_path($path = '')
    {
        return base_path('repositories');
    }
}

if (! function_exists('app_version_path')) {
    function app_version_path($path = '')
    {
        return base_path(config('micro-tenant.microservices.app_version.path') . '/' . $path);
    }
}
