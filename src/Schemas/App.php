<?php

namespace Hanafalah\MicroTenant\Schemas;

use Hanafalah\LaravelSupport\Supports\PackageManagement;

class App extends PackageManagement
{
    protected array $__add = ['id', 'name', 'application_type_id', 'parent_id'];

    public function add(array $attributes = []): self
    {
        static::$__model = $this->AppModel()->firstOrCreate(...$this->createInit($this->__add, $attributes));
        return $this;
    }
}
