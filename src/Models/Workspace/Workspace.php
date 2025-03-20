<?php

namespace Hanafalah\MicroTenant\Models\Workspace;

use Hanafalah\MicroTenant\Concerns\Models\CentralConnection;
use Hanafalah\ModuleWorkspace\Models\Workspace\Workspace as ModuleWorkspace;

class Workspace extends ModuleWorkspace
{
    use CentralConnection;
}
