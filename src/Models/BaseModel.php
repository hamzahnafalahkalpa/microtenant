<?php

namespace Hanafalah\MicroTenant\Models;

use Hanafalah\LaravelSupport\{
    Concerns\Support as SupportConcern,
    Models as SupportModels
};
use Illuminate\Support\Str;
use Hanafalah\LaravelSupport\Concerns\DatabaseConfiguration\HasModelConfiguration;
use Hanafalah\MicroTenant\Scopes\UseTenantValidation;

class BaseModel extends SupportModels\SupportBaseModel
{
    use HasModelConfiguration;
    use SupportConcern\HasDatabase;
    use SupportConcern\HasConfigDatabase;

    public function initializeHasConfigDatabase()
    {
        parent::initializeHasConfigDatabase();
        $model_connections = config('micro-tenant.database.model_connections');
        if (isset($model_connections) && count($model_connections) > 0){
            if (isset($model_connections['central']) && in_array($this->getMorphClass(),$model_connections['central'])) {
                $this->connection = 'central';
            }
            if (isset($model_connections['central_tenant']) && in_array($this->getMorphClass(),$model_connections['central_tenant'])) {
                $this->connection = 'central_tenant';
            }
        }
    }

    //BOOTED SECTION
    protected static function booted(): void
    {
        static::setConfigBaseModel('database.models');
        parent::booted();
    }
    //END BOOTED SECTION

    public function callCustomMethod(): array
    {
        return ['Model'];
    }

    public function getTable()
    {
        $table   = $this->table ?? Str::snake(Str::pluralStudly(class_basename($this)));
        $db_name = config('database.connections.' . ($this->getConnectionName() ?? config('database.default')) . '.database');
        $table   = \explode('.', $table);
        $table   = end($table);
        $table   = $db_name . '.' . $table;
        return $table;
    }

    protected function validatingHistory($query)
    {
        $validation = $query->getModel() <> $this->LogHistoryModel()::class;
        if ($query->getConnectionName() == "tenant" && microtenant() === null) $validation = false;
        return $validation;
    }

    public function scopeWithoutTenant($builder, $callback, $tenant_id = null)
    {
        $builder->withoutGlobalScope(UseTenantValidation::class);

        $current_tenant_id = tenancy()->tenant->getKey();
        tenancy()->initialize($tenant_id ?? $builder->tenant_id);
        $builder = $builder->when(true, function ($query) use ($callback) {
            return $callback($query);
        });
        tenancy()->initialize($current_tenant_id);
        return $builder;
    }
    //END METHOD SECTION
}
