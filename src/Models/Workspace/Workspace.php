<?php

namespace Zahzah\MicroTenant\Models\Workspace;

use Zahzah\MicroTenant\Concerns\Models\CentralConnection;
use Zahzah\ModuleWorkspace\Models\Workspace\Workspace as ModuleWorkspace;

class Workspace extends ModuleWorkspace{
    use CentralConnection;
}