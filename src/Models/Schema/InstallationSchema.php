<?php

namespace Hanafalah\MicroTenant\Models\Schema;

use Hanafalah\MicroTenant\Concerns\Models\CentralConnection;
use Hanafalah\ModuleVersion\Models\Schema\InstallationSchema as SchemaInstallationSchema;

class InstallationSchema extends SchemaInstallationSchema
{
    use CentralConnection;
}
