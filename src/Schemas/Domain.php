<?php

namespace Hanafalah\MicroTenant\Schemas;

use Hanafalah\LaravelSupport\Supports\PackageManagement;
use Illuminate\Database\Eloquent\Model;
use Hanafalah\MicroTenant\Contracts\Schemas\Domain as ContractsDomain;
use Hanafalah\MicroTenant\Contracts\Data\DomainData;

class Domain extends PackageManagement implements ContractsDomain
{
    protected string $__entity = 'Domain';
    public $domain_model;
    protected mixed $__order_by_created_at = false; //asc, desc, false

    protected array $__cache = [
        'index' => [
            'name'     => 'domain',
            'tags'     => ['domain', 'domain-index'],
            'duration'  => 24*60
        ]
    ];

    public function prepareStoreDomain(DomainData $domain_dto): Model{
        $add   = [
            'domain'           => $domain_dto->domain,
        ];
        if (isset($domain_dto->id)){
            $guard  = ['id' => $domain_dto->id];
            $create = [$guard,$add];
        }else{
            $create = [$add];
        }
        $domain = $this->usingEntity()->updateOrCreate(...$create);
        $domain->save();
        return $this->domain_model = $domain;
    }
}