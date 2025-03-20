<?php

declare(strict_types=1);

namespace Hanafalah\MicroTenant\Contracts;

use Illuminate\Container\Container;
use Hanafalah\LaravelSupport\Contracts\FileRepository as SupportFileRepositoryInterface;
use Hanafalah\MicroTenant\Supports\Generator;

interface FileRepositoryInterface extends SupportFileRepositoryInterface
{
    public function __construct(Container $app, ...$args);
    public function setupServices(): self;
    public function setupClassDiscover($services = []);
}
