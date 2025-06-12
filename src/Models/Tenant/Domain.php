<?php

declare(strict_types=1);

namespace Hanafalah\MicroTenant\Models\Tenant;

use Hanafalah\LaravelHasProps\Concerns\HasProps;
use Illuminate\Database\Eloquent\SoftDeletes;
use Hanafalah\MicroTenant\Models\BaseModel;
use Hanafalah\Microtenant\Resources\Domain\{ShowDomain, ViewDomain};

class Domain extends BaseModel
{
    use SoftDeletes, HasProps;
    
    protected $list = ['id', 'name', 'created_at', 'updated_at', 'deleted_at'];
    protected $casts = [
        'name' => 'string'
    ];

    public function getViewResource(){return ViewDomain::class;}
    public function getShowResource(){return ShowDomain::class;}

    //EIGER SECTION
    public function tenant(){return $this->hasOneModel('Tenant');}
    public function tenants(){return $this->hasManyModel('Tenant');}
    //END EIGER SECTION
}
