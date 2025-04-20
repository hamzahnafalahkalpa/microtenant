<?php

namespace Hanafalah\MicroTenant\Data;

use Hanafalah\LaravelSupport\Supports\Data;
use Hanafalah\MicroTenant\Contracts\Data\TenantData as DataTenantData;
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
    public ?string $domain_id = null;

    #[MapInputName('reference_id')]
    #[MapName('reference_id')]
    public ?string $reference_id = null;

    #[MapInputName('reference_type')]
    #[MapName(' ')]
    public ?string $reference_type = null;
    
    #[MapInputName('props')]
    #[MapName('props')]
    public ?array $props = null;

}