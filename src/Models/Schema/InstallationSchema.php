<?php

namespace Zahzah\MicroTenant\Models\Schema;

use Zahzah\MicroTenant\Concerns\Models\CentralConnection;
use Zahzah\ModuleVersion\Models\Schema\InstallationSchema as SchemaInstallationSchema;

class InstallationSchema extends SchemaInstallationSchema{
    use CentralConnection;
}