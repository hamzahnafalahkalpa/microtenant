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
        $model_connections = $database['model_connections'];
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
        ]);
        $database_connections = config('database.connections');
        foreach ($model_connections as $key => $model_connection) {
            if (isset($model_connection['connection_as'])){
                $connection_as = config('database.connections.'.$model_connection['connection_as']);
                $model_connection['is_cluster'] ??= false;
                if ($model_connection['is_cluster']){
                    $header_cluster = request()->header('cluster');
                    $connection_as['search_path'] = $header_cluster ?? $key.'_'.($header_cluster ?? date('Y'));
                }
            }
            $connection_as ??= $connection['central_connection'];
            $database_connections[$key] = $connection_as;
            $connection_as = null;
        }
        config(['database.connections' => $database_connections]);
    }
}