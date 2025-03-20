<?php

namespace Zahzah\MicroTenant\Models\Version;

use Zahzah\MicroTenant\Concerns\Models\CentralConnection;
use Zahzah\ModuleVersion\Models\Version\ModelHasVersion as VersionModelHasVersion;

class ModelHasVersion extends VersionModelHasVersion{
    use CentralConnection;
} 