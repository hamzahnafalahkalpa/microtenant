<?php

namespace Hanafalah\MicroTenant\Concerns\Providers;

trait HasOverrider
{
    protected array $__impersonate;

    protected $__cache_data = [
        'impersonate' => [
            'name'    => 'microtenant-impersonate',
            'tags'    => ['impersonate','microtenant-impersonate'],
            'forever' => true
        ]
    ];

    public function overrideTenantConfig(){
        $microtenant   = config('micro-tenant');
        $database      = $microtenant['database'];
        $connection    = $database['connections'];
        $model         = config('database.models',[]);
        $dbname        = $database['database_tenant_name'];
        config([
            'tenancy'                                     => $this->__config['tenancy'],
            'tenancy.filesystem.asset_helper_tenancy'     => false,
            'tenancy.tenant_model'                        => $model['Tenant'] ?? null,
            'tenancy.id_generator'                        => null,
            'tenancy.domain_model'                        => $model['Domain'],
            'tenancy.central_domains'                     => $microtenant['domains']['central_domains'],
            'tenancy.database.central_connection'         => 'central',
            'tenancy.database.template_tenant_connection' => null,
            'tenancy.database.prefix'                     => $dbname['prefix'],
            'tenancy.database.suffix'                     => $dbname['suffix'],
            'tenancy.database.managers'                   => $database['managers'],
            'database.connection_central_name'            => 'central',
            'database.connection_central_tenant_name'     => 'central_tenant',
            'database.connection_central_app_name'        => 'central_app',
            'database.connections.central'                => $connection['central_connection'],
            'database.connections.central_tenant'         => $connection['central_connection'],
            'database.connections.central_app'            => $connection['central_connection']
        ]);
    }
}