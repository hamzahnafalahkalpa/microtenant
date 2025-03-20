<?php

namespace Zahzah\MicroTenant\Models;

use Zahzah\LaravelSupport\{
    Concerns\Support as SupportConcern, Models as SupportModels    
};
use Illuminate\Support\Str;
use Zahzah\LaravelSupport\Concerns\DatabaseConfiguration\HasModelConfiguration;
use Zahzah\MicroTenant\Scopes\UseTenantValidation;

class BaseModel extends SupportModels\SupportBaseModel
{    
    use HasModelConfiguration;
    use SupportConcern\HasDatabase;
    use SupportConcern\HasConfigDatabase;

    //BOOTED SECTION
    protected static function booted(): void{
        static::setConfigBaseModel('database.models');
        parent::booted();
    }
    //END BOOTED SECTION

    public function callCustomMethod(){
        return ['Model'];
    }

    public function getTable(){
        $table   = $this->table ?? Str::snake(Str::pluralStudly(class_basename($this)));
        $db_name = config('database.connections.'.($this->getConnectionName() ?? config('database.default')).'.database');
        $table   = \explode('.',$table);
        $table   = end($table);
        $table   = $db_name.'.'.$table;
        return $table;
    }

    protected function validatingHistory($query){
        $validation = $query->getModel() <> $this->LogHistoryModel()::class;
        if ($query->getConnectionName() == "tenant" && microtenant() === null) $validation = false;
        return $validation;
    }

    public function scopeWithoutTenant($builder,$callback,$tenant_id = null){
        $builder->withoutGlobalScope(UseTenantValidation::class);

        $current_tenant_id = tenancy()->tenant->getKey();
        tenancy()->initialize($tenant_id ?? $builder->tenant_id);
        $builder = $builder->when(true,function($query) use ($callback){
            return $callback($query);
        });
        tenancy()->initialize($current_tenant_id);
        return $builder;
    }
    //END METHOD SECTION
}