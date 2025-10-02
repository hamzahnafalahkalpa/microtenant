<?php

namespace Hanafalah\MicroTenant\Data;

use Hanafalah\LaravelSupport\Supports\Data;
use Hanafalah\MicroTenant\Contracts\Data\DomainData as DataDomainData;
use Spatie\LaravelData\Attributes\MapInputName;
use Spatie\LaravelData\Attributes\MapName;

class DomainData extends Data implements DataDomainData{
    #[MapInputName('id')]
    #[MapName('id')]
    public mixed $id = null;

    #[MapInputName('domain')]
    #[MapName('domain')]
    public string $domain;

    #[MapInputName('tenant_id')]
    #[MapName('tenant_id')]
    public mixed $tenant_id = null;

    #[MapInputName('props')]
    #[MapName('props')]
    public ?array $props = null;

}