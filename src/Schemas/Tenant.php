<?php

namespace Hanafalah\MicroTenant\Schemas;

use Hanafalah\LaravelSupport\Supports\PackageManagement;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Hanafalah\MicroTenant\Contracts\Schemas\Tenant as ContractsTenant;
use Hanafalah\MicroTenant\Contracts\Data\TenantData;

class Tenant extends PackageManagement implements ContractsTenant
{
    protected string $__entity = 'Tenant';
    public static $tenant_model;

    protected array $__cache = [
        'index' => [
            'name'     => 'tenant',
            'tags'     => ['tenant', 'tenant-index'],
            'forever'  => true
        ]
    ];

    public function prepareStoreTenant(TenantData $tenant_dto): Model{
        if (isset($tenant_dto->id)){
            $guard = ['id' => $tenant_dto->id];
            $add   = [
                'parent_id'      => $tenant_dto->parent_id ?? null,
                'name'           => $tenant_dto->name,
                'flag'           => $tenant_dto->flag,
                'reference_id'   => $tenant_dto->reference_id ?? null,
                'reference_type' => $tenant_dto->reference_type ?? null,
            ];
            $create = [$guard,$add];
        }else{
            $guard = [
                'name'           => $tenant_dto->name,
                'flag'           => $tenant_dto->flag,
                'parent_id'      => $tenant_dto->parent_id ?? null,
                'reference_id'   => $tenant_dto->reference_id ?? null,
                'reference_type' => $tenant_dto->reference_type ?? null,
            ];
            $create = [$guard];
        }
        $tenant = $this->TenantModel()->updateOrCreate(...$create);
        $this->fillingProps($tenant,$tenant_dto->props);
        $tenant->save();
        static::$tenant_model = $tenant;
        return $tenant;
    }

    public function storeTenant(?TenantData $tenant_dto = null): array{
        return $this->transaction(function() use ($tenant_dto){
            return $this->showTenant($this->prepareStoreTenant($tenant_dto ?? $this->requestDTO(TenantData::class)));
        });
    }

    public function tenant(mixed $conditionals = null): Builder{
        $this->booting();
        return $this->TenantModel()->withParameters()
                    ->conditionals($this->mergeCondition($conditionals ?? []))
                    ->orderBy('name', 'asc');
    }
}