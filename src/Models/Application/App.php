<?php

namespace Zahzah\MicroTenant\Models\Application;

use Zahzah\MicroTenant\Concerns\Models\CentralConnection;
use Zahzah\ModuleVersion\Models\Application\App as ApplicationApp;

class App extends ApplicationApp
{
    use CentralConnection;

    //EIGER SECTION
    public function apiAccess(){return $this->morphOneModel('ApiAccess','reference');}
    public function tenant(){return $this->morphOneModel('Tenant','reference');}
    //END EIGER SECTION
}