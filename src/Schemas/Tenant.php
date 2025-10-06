<?php

namespace Hanafalah\MicroTenant\Schemas;

use Hanafalah\LaravelSupport\Supports\PackageManagement;
use Illuminate\Database\Eloquent\Model;
use Hanafalah\MicroTenant\Contracts\Schemas\Tenant as ContractsTenant;
use Hanafalah\MicroTenant\Contracts\Data\TenantData;
use Illuminate\Support\Str;

class Tenant extends PackageManagement implements ContractsTenant
{
    protected string $__entity = 'Tenant';
    public $tenant_model;
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
                'domain' => $domain->domain
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
            $add['uuid'] ??= Str::orderedUuid();
            $create = [$add];
        }
        $tenant = $this->usingEntity()->updateOrCreate(...$create);
        $tenant->refresh();
        if (isset($domain)){
            $domain->tenant_id = $tenant->getKey();
            $domain->save();
        }
        if (isset($tenant_dto->childs) && count($tenant_dto->childs) > 0) {
            $childs = &$tenant_dto->childs;
            foreach ($childs as $child) {
                $child->parent_id = $tenant->getKey();
                if ($child->flag == $tenant::FLAG_CLUSTER) $child->name .= ' - ' . $tenant->getKey();
                $this->prepareStoreTenant($child);
            }
        }
        $this->fillingProps($tenant,$tenant_dto->props);
        $tenant->save();
        return $this->tenant_model = $tenant;
    }
}