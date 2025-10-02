<?php

namespace Hanafalah\MicroTenant\Data;

use Hanafalah\LaravelSupport\Supports\Data;
use Hanafalah\MicroTenant\Contracts\Data\DomainData;
use Hanafalah\MicroTenant\Contracts\Data\TenantData as DataTenantData;
use Spatie\LaravelData\Attributes\DataCollectionOf;
use Spatie\LaravelData\Attributes\MapInputName;
use Spatie\LaravelData\Attributes\MapName;

class TenantData extends Data implements DataTenantData{
    #[MapInputName('id')]
    #[MapName('id')]
    public mixed $id = null;

    #[MapInputName('parent_id')]
    #[MapName('parent_id')]
    public mixed $parent_id = null;

    #[MapInputName('name')]
    #[MapName('name')]
    public string $name;

    #[MapInputName('flag')]
    #[MapName('flag')]
    public string $flag;

    #[MapInputName('domain_id')]
    #[MapName('domain_id')]
    public mixed $domain_id = null;

    #[MapInputName('domain')]
    #[MapName('domain')]
    public ?DomainData $domain = null;

    #[MapInputName('reference_id')]
    #[MapName('reference_id')]
    public mixed $reference_id = null;

    #[MapInputName('reference_type')]
    #[MapName('reference_type')]
    public ?string $reference_type = null;

    #[MapInputName('childs')]
    #[MapName('childs')]
    #[DataCollectionOf(TenantData::class)]
    public ?array $childs = null;

    #[MapInputName('props')]
    #[MapName('props')]
    public ?array $props = null;

    public static function after(TenantData $data): TenantData{
        $data->props['prop_domain'] = [
            'id'   => $data->domain_id ?? null,
            'domain' => null
        ];
        $new = static::new();
        if (isset($data->domain_id)){
            $domain = $new->DomainModel()->findOrFail($data->domain_id);
            $data->props['prop_domain']['domain'] = $domain->name;
        }

        return $data;
    }

}