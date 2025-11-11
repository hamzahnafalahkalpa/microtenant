<?php

declare(strict_types=1);

namespace Hanafalah\MicroTenant\Models\Tenant;

use Illuminate\Support\Str;
use Stancl\Tenancy\Contracts\Tenant as ContractsTenant;
use Stancl\Tenancy\Contracts\TenantWithDatabase;
use Stancl\Tenancy\Database\Concerns\{
    HasDatabase,
    HasInternalKeys,
    InvalidatesResolverCache,
    TenantRun
};
use Stancl\Tenancy\Database\TenantCollection;
use Hanafalah\LaravelHasProps\Concerns\HasProps;

use Stancl\Tenancy\Events;
use Illuminate\Database\Eloquent\SoftDeletes;
use Hanafalah\LaravelSupport\Models\BaseModel;
use Hanafalah\MicroTenant\Concerns\Models\CentralConnection;
use Hanafalah\MicroTenant\Resources\Tenant\ShowTenant;
use Hanafalah\MicroTenant\Resources\Tenant\ViewTenant;

class Tenant extends BaseModel implements ContractsTenant, TenantWithDatabase{
    use SoftDeletes, HasProps;
    use CentralConnection,
        HasDatabase,
        HasInternalKeys,
        TenantRun,
        InvalidatesResolverCache;

    const FLAG_APP_TENANT     = 'APP';
    const FLAG_CENTRAL_TENANT = 'CENTRAL_TENANT';
    const FLAG_TENANT         = 'TENANT';
    const FLAG_CLUSTER        = 'CLUSTER';

    protected $fillable   = [
        'id','parent_id','name','uuid','reference_id','reference_type',
        'flag','domain_id','props'
    ];

    protected static $modelsShouldPreventAccessingMissingAttributes = false;

    protected static function booted(): void{
        parent::booted();
        static::creating(function($query){
            $query->uuid ??= Str::orderedUuid();
            $query->flag ??= static::FLAG_TENANT;
            $connection = config('database.default');
            $query->db_name ??= config('database.connections.'.$connection.'.database');
        });
    }

    public function getTenantKeyName(): string{
        return 'id';
    }

    public function getConnectionFlagName(): string{
        switch ($this->flag) {
            case static::FLAG_APP_TENANT: return 'central_app';break;
            case static::FLAG_CENTRAL_TENANT: return 'central_tenant';break;
            case static::FLAG_TENANT: return 'tenant';break;
            default: return 'tenant';break;
        }
    }

    public function getTenantKey()
    {
        return $this->getAttribute($this->getTenantKeyName());
    }

    public function getShowResource(){
        return ShowTenant::class;
    }

    public function getViewResource(){
        return ViewTenant::class;
    }

    //SCOPE SECTION
    public function scopeApp($builder){return $builder->flagIn(static::FLAG_APP_TENANT);}
    public function scopeTenant($builder){return $builder->flagIn(static::FLAG_TENANT);}
    public function scopeCentral($builder){return $builder->flagIn(static::FLAG_CENTRAL_TENANT);}
    //END SCOPE SECTION

    //EIGER SECTION
    public function domain(){return $this->belongsToModel('Domain');}
    public function domains(){return $this->hasManyModel('Domain');}
    public function tenantHasModel(){return $this->hasOneModel('TenantHasModel');}
    public function tenantHasModels(){return $this->hasManyModel('TenantHasModel');}
    public function installationSchema(){return $this->morphOneModel('InstallationSchema','reference');} //ONLY ONE
    public function modelHasFeatures(){return $this->hasManyModel('ModelHasFeature');}
    public function modelHasApp(){return $this->morphOneModel('ModelHasApp','model');}
    public function reference(){return $this->morphTo();}
    public function parent(){return $this->belongsToModel('Tenant','parent_id')->with('parent');}
    //END EIGER SECTION

    public function newCollection(array $models = []): TenantCollection
    {
        return new TenantCollection($models);
    }

    protected $dispatchesEvents = [
        'saving' => Events\SavingTenant::class,
        'saved' => Events\TenantSaved::class,
        'creating' => Events\CreatingTenant::class,
        'created' => Events\TenantCreated::class,
        'updating' => Events\UpdatingTenant::class,
        'updated' => Events\TenantUpdated::class,
        'deleting' => Events\DeletingTenant::class,
        'deleted' => Events\TenantDeleted::class,
    ];
}