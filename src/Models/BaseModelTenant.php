<?php

namespace Hanafalah\MicroTenant\Models;

use Stancl\Tenancy\{
    Contracts\TenantWithDatabase,
    Database\Models\Tenant,
    Database\TenantCollection,
    Events as TenancyEvents
};
use Hanafalah\LaravelHasProps\{
    Models\Scopes\HasCurrentScope,
    Concerns as PropsConcerns
};
use Hanafalah\LaravelSupport\{
    Concerns\Support as SupportConcern,
    Concerns\DatabaseConfiguration\HasModelConfiguration
};
use Illuminate\Support\Str;
use Stancl\Tenancy\Database\Concerns\HasDatabase;

class BaseModelTenant extends Tenant implements TenantWithDatabase
{
    use HasDatabase;
    use HasModelConfiguration;
    use SupportConcern\HasDatabase;
    use SupportConcern\HasConfigDatabase;
    use SupportConcern\HasRepository;

    const STATUS_ACTIVE     = 1;
    const STATUS_DELETED    = 0;

    public $incrementing    = true;
    public $timestamps      = true;
    public $scopeLists      = [];
    public $lengthId        = 26; //PURPOSE STRING ONLY
    protected $primaryKey   = 'id';
    protected $keyType      = "int";
    protected $list         = [];
    protected $show         = [];

    protected static function booted(): void
    {
        static::setConfigBaseModel('database.models');
        parent::booted();

        static::addGlobalScope(new HasCurrentScope);
        static::creating(function ($query) {
            PropsConcerns\HasCurrent::currentChecking($query);

            if (static::isSetUuid($query) && !isset($query->{$query->getUuidName()})) {
                $query->uuid = Str::orderedUuid();
            }
        });
        static::created(function ($query) {
            PropsConcerns\HasCurrent::setOld($query);
        });
    }

    public function shouldGenerateId(): bool{
        return false;
    }

    //MUTATOR SECTION
    public static function getTableName(){
        return with(new static)->getTable();
    }

    public function callCustomMethod()
    {
        return ['Model'];
    }

    protected function validatingHistory($query)
    {
        $validation = $query->getModel() <> $this->LogHistoryModel()::class;
        if ($query->getConnectionName() == "tenant" && microtenant() === null) $validation = false;
        return $validation;
    }

    public function getTenantKeyName(): string
    {
        return 'id';
    }

    public function getTenantKey()
    {
        return $this->getAttribute($this->getTenantKeyName());
    }

    public function newCollection(array $models = []): TenantCollection
    {
        return new TenantCollection($models);
    }
    //END METHOD SECTION

    //EIGER SECTION
    public function activity()
    {
        return $this->morphOneModel('Activity', 'reference');
    }
    public function activities()
    {
        return $this->morphManyModel('Activity', 'reference');
    }
    public function logHistories()
    {
        return $this->morphMany($this->LogHistoryModel(), "reference");
    }
    //END EIGER SECTION

    protected $dispatchesEvents = [
        'saving' => TenancyEvents\SavingTenant::class,
        'saved' => TenancyEvents\TenantSaved::class,
        'creating' => TenancyEvents\CreatingTenant::class,
        'created' => TenancyEvents\TenantCreated::class,
        'updating' => TenancyEvents\UpdatingTenant::class,
        'updated' => TenancyEvents\TenantUpdated::class,
        'deleting' => TenancyEvents\DeletingTenant::class,
        'deleted' => TenancyEvents\TenantDeleted::class,
    ];
}
