<?php

namespace Hanafalah\MicroTenant\Models\Activity;

use Illuminate\Database\Eloquent\Relations\Relation;
use Hanafalah\LaravelSupport\Models\Activity\ActivityStatus;

class CentralActivityStatus extends ActivityStatus
{
  protected $connection = 'central';

  public static $__activity;
  protected $table      = 'activity_statuses';

  protected static function booting(): void
  {
    static::$__activity = app(config('database.models.CentralActivity'));
  }

  //MUTATOR SECTION
  public function getActivityMessage($messageCode = null)
  {
    $activity    = static::$__activity;
    $relation    = Relation::morphMap()[$activity->reference_type];
    $messageCode = $messageCode ?? $activity->activity_flag . '_' . $this->status;
    $model       = new $relation;
    return $model::$activityList[$messageCode];
  }
  // END MUTATOR SECTION

  // EIGER SECTION
  public function activity()
  {
    return $this->belongsToModel('CentralActivity');
  }
  public function reference()
  {
    return $this->morphTo();
  }
  public function author()
  {
    return $this->morphTo();
  }
  // END EIGER SECTION
}
