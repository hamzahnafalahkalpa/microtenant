<?php

namespace Hanafalah\MicroTenant\Models\Version;

use Hanafalah\MicroTenant\Concerns\Models\CentralConnection;
use Hanafalah\ModuleVersion\Models\Version\ModelHasVersion as VersionModelHasVersion;

class ModelHasVersion extends VersionModelHasVersion
{
    use CentralConnection;
}
