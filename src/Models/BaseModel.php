<?php

namespace Hanafalah\MicroTenant\Models;

use Hanafalah\LaravelSupport\{
    Concerns\Support as SupportConcern,
    Models as SupportModels
};
use Illuminate\Support\Str;
use Hanafalah\LaravelSupport\Concerns\DatabaseConfiguration\HasModelConfiguration;
use Hanafalah\MicroTenant\Models\Tenant\Tenant;
use Hanafalah\MicroTenant\Scopes\UseTenantValidation;
use ReflectionClass;

class BaseModel extends SupportModels\SupportBaseModel
{
    use HasModelConfiguration;
    use SupportConcern\HasDatabase;
    use SupportConcern\HasConfigDatabase;

    protected array $__model_connections;

    public function initializeHasConfigDatabase()
    {
        parent::initializeHasConfigDatabase();
        $model_connections = config('micro-tenant.database.model_connections');
        if (isset($model_connections) && count($model_connections) > 0){
            $this->__model_connections = $model_connections;
            $keys = array_keys($model_connections);
            foreach ($keys as $key) $this->validateConnection($key);
            // if (!isset($this->connection) && isset(tenancy()->tenant) && tenancy()->tenant->flag == Tenant::FLAG_TENANT) {
            if (!isset($this->connection) && isset(tenancy()->tenant)) {
                $parent_class = new (get_parent_class($this));
                $this->connection = $parent_class?->connection ?? 'tenant';
            }
        }
    }

    private function validateConnection(string $connection): self{
        if (isset($this->__model_connections[$connection]) && isset($this->__model_connections[$connection]['models']) && in_array($this->getMorphClass(),$this->__model_connections[$connection]['models'])){
            $this->connection = $connection;
        }
        return $this;
    }

    //BOOTED SECTION
    protected static function booted(): void{
        static::setConfigBaseModel('database.models');
        parent::booted();
    }
    //END BOOTED SECTION

    public function callCustomMethod(): array{
        return ['Model'];
    }

    public function getTable(){
        $connection_name = $this->getConnectionName();
        $connection      = config('database.connections.' . ($connection_name ?? config('database.default')));
        if ($connection['driver'] == 'pgsql') {
            // $db_name = $connection['database'];
            // if (config('micro-tenant.use-db-name',true)){
            //     $db_name .= '.'.$connection['search_path'];
            // }else{
            //     return parent::getTable();
            // }
            if (config('micro-tenant.use-db-name',true)){
                $db_name = $connection['search_path'];
            }else{
                return parent::getTable();
            }
        }else{
            $db_name = $connection['database'];
        }

        $table           = $this->table ?? Str::snake(Str::pluralStudly(class_basename($this)));
        $table           = \explode('.', $table);
        $table           = end($table);
        return $db_name . '.' . $table;
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
