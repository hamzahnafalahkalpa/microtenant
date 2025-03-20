<?php

declare(strict_types=1);

namespace Zahzah\MicroTenant\Models\Tenant;

use Zahzah\MicroTenant\Concerns\Models\CentralConnection;
use Illuminate\Database\Eloquent\SoftDeletes;
use Zahzah\MicroTenant\Models\BaseModel;

class Domain extends BaseModel{
    use CentralConnection, SoftDeletes;

    protected $table    = 'domains';
    protected $fillable = ['id','domain','created_at','updated_at','deleted_at'];

    //EIGER SECTION
    public function tenant(){return $this->hasOneModel('Tenant');}
    public function tenants(){return $this->hasManyModel('Tenant');}
    //END EIGER SECTION
}