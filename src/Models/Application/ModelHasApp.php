<?php

namespace Hanafalah\MicroTenant\Models\Application;

use Hanafalah\MicroTenant\Concerns\Models\CentralConnection;
use Hanafalah\ModuleVersion\Models\Application\ModelHasApp as ApplicationModelHasApp;
use Hanafalah\MicroTenant\Models;

class ModelHasApp extends ApplicationModelHasApp
{
    use CentralConnection;
}
