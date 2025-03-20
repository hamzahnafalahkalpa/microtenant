<?php

declare(strict_types=1);

namespace Hanafalah\MicroTenant\Contracts;

use Hanafalah\MicroTenant\MicroTenant;

interface ActivatorInterface
{

    public function allow(MicroTenant $microTenant);

    public function disallow(MicroTenant $microTenant);
}
