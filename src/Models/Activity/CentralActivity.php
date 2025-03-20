<?php

namespace Zahzah\MicroTenant\Models\Activity;

use Zahzah\LaravelSupport\Models\Activity\Activity;

class CentralActivity extends Activity{
    protected $connection = 'central';

    protected $table      = 'activities';
    protected $fillable   = [
      "id","activity_flag","reference_type","reference_id",
      "status","activity_status","message","created_at","updated_at"
    ];

    //EIGER SECTION
    public function activityStatus(){return $this->hasOneModel('CentralActivityStatus');}
    public function activityStatuses(){return $this->hasMany('CentralActivityStatus');}
    //END EIGER SECTION
}
