<?php

namespace Hanafalah\MicroTenant\Schemas;

use Hanafalah\LaravelSupport\Supports\PackageManagement;
use Illuminate\Database\Eloquent\Model;
use Hanafalah\MicroTenant\Contracts\Schemas\Tenant as ContractsTenant;
use Hanafalah\MicroTenant\Contracts\Data\TenantData;

class Tenant extends PackageManagement implements ContractsTenant
{
    protected string $__entity = 'Tenant';
    public static $tenant_model;
    protected mixed $__order_by_created_at = false; //asc, desc, false

    protected array $__cache = [
        'index' => [
            'name'     => 'tenant',
            'tags'     => ['tenant', 'tenant-index'],
            'forever'  => true
        ]
    ];

    public function prepareStoreTenant(TenantData $tenant_dto): Model{
        if (isset($tenant_dto->domain)){
            $domain = $this->schemaContract('domain')->prepareStoreDomain($tenant_dto->domain);
            $tenant_dto->domain_id = $domain->getKey();
            $tenant_dto->props['prop_domain'] = [
                'id'   => $domain->getKey(),
                'name' => $domain->name
            ];
        }

        $add   = [
            'parent_id'      => $tenant_dto->parent_id,
            'name'           => $tenant_dto->name,
            'flag'           => $tenant_dto->flag,
            'reference_id'   => $tenant_dto->reference_id,
            'reference_type' => $tenant_dto->reference_type,
            'domain_id'      => $tenant_dto->domain_id
        ];
        if (isset($tenant_dto->id)){
            $guard = ['id' => $tenant_dto->id];
            $create = [$guard,$add];
        }else{
            $create = [$add];
        }
        $tenant = $this->TenantModel()->updateOrCreate(...$create);
        $this->fillingProps($tenant,$tenant_dto->props);
        $tenant->save();
        static::$tenant_model = $tenant;
        return $tenant;
    }
}