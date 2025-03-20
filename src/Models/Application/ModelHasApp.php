<?php

namespace Zahzah\MicroTenant\Models\Application;

use Zahzah\MicroTenant\Concerns\Models\CentralConnection;
use Zahzah\ModuleVersion\Models\Application\ModelHasApp as ApplicationModelHasApp;
use Zahzah\MicroTenant\Models;

class ModelHasApp extends ApplicationModelHasApp{
    use CentralConnection;
}