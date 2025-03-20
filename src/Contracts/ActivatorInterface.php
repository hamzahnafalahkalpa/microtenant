<?php

declare(strict_types=1);

namespace Zahzah\MicroTenant\Contracts;

use Zahzah\MicroTenant\MicroTenant;

interface ActivatorInterface
{

    public function allow(MicroTenant $microTenant);
    
    public function disallow(MicroTenant $microTenant);
}