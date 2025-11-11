<?php

declare(strict_types=1);

namespace Hanafalah\MicroTenant\Models\Tenant;

use Hanafalah\LaravelHasProps\Concerns\HasProps;
use Illuminate\Database\Eloquent\SoftDeletes;
use Hanafalah\MicroTenant\Models\BaseModel;
use Hanafalah\MicroTenant\Resources\Domain\{ShowDomain, ViewDomain};
use Illuminate\Database\Eloquent\Concerns\HasUlids;

class Domain extends BaseModel
{
    use SoftDeletes, HasProps, HasUlids;
    
    public $incrementing = false;
    protected $keyType = 'string';
    protected $primaryKey = 'id';
    protected $list = ['id', 'domain', 'tenant_id', 'created_at', 'updated_at', 'deleted_at'];
    protected $casts = [
        'domain' => 'string'
    ];

    public function getViewResource(){return ViewDomain::class;}
    public function getShowResource(){return ShowDomain::class;}

    //EIGER SECTION
    public function tenant(){return $this->hasOneModel('Tenant');}
    public function tenants(){return $this->hasManyModel('Tenant');}
    //END EIGER SECTION
}
