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

    #[MapInputName('name')]
    #[MapName('name')]
    public string $name;

    #[MapInputName('props')]
    #[MapName('props')]
    public ?array $props = null;

}