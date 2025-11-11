<?php

namespace Hanafalah\MicroTenant\Concerns\Providers;

use Illuminate\Database\Eloquent\Model;

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

    public function overrideTenantConfig(?Model $tenant = null){
        $microtenant   = config('micro-tenant');
        $database      = $microtenant['database'];
        $connection    = $database['connections'];
        $model_connections = $database['model_connections'];
        $model         = config('database.models',[]);
        $dbname        = $database['database_tenant_name'];
        config([
            'database.connections.central'                => config('micro-tenant.database.connections.central_connection'),
            'tenancy'                                     => $this->__config['tenancy'],
            'tenancy.filesystem.asset_helper_tenancy'     => false,
            'tenancy.tenant_model'                        => $model['Tenant'] ?? null,
            'tenancy.id_generator'                        => null,
            'tenancy.domain_model'                        => $model['Domain'] ?? 'Domain',
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
        $tenant_connection = config('database.connections.tenant');
        if ($tenant_connection === null && isset($tenant)){
            config([
                'database.connections.tenant' => $connection['central_connection'],
                'database.connections.tenant.database' => $tenant->db_name,
                'database.connections.tenant.search_path' => $tenant->tenancy_db_name == $tenant->db_name ? 'public' : $tenant->tenancy_db_name
            ]);
        }
        $database_connections = config('database.connections');
        $clusters = [];
        $header_cluster = request()->header('cluster') ?? date('Y');
        foreach ($model_connections as $key => $model_connection) {
            if (isset($model_connection['connection_as'])){
                $connection_as = config('database.connections.'.$model_connection['connection_as']);
                $model_connection['is_cluster'] ??= false;
                if (isset($connection_as) && $model_connection['is_cluster']){
                    $tenant_connection = config('database.connections.tenant');
                    if (isset($tenant_connection)){
                        $connection_as = $tenant_connection;
                    }
                    $connection_as['search_path'] = $key.'_'.$header_cluster;
                    $clusters[$key] = $connection_as;
                }
            }
            $connection_as ??= config('database.connections.'.$key) ?? $connection['central_connection'];
            if (isset($tenant)){
                $flag = $tenant->flag;
                if (
                    ($flag == 'APP' && $key == 'central_app') ||
                    ($flag == 'CENTRAL_TENANT' && $key == 'central_tenant') ||
                    ($flag == 'TENANT' && $key == 'tenant')
                ) {
                    $connection_as['database']     = $tenant->db_name;
                    $connection_as['search_path']  = $tenant->tenancy_db_name == $tenant->db_name ? 'public' : $tenant->tenancy_db_name;
                    $database_connections[$key] = $connection_as;
                // }elseif($key == 'central'){
                }else{
                    $database_connections[$key] = $connection_as;
                }
            }else{
                $database_connections[$key] = $connection_as;
            }
            $connection_as = null;
        }
        config([
            'database.connections' => $database_connections,
            'database.clusters' => $clusters
        ]);
    }
}