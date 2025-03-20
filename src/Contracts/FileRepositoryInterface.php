<?php

declare(strict_types=1);

namespace Zahzah\MicroTenant\Contracts;

use Illuminate\Container\Container;
use Zahzah\LaravelSupport\Contracts\FileRepository as SupportFileRepositoryInterface;
use Zahzah\MicroTenant\Supports\Generator;

interface FileRepositoryInterface extends SupportFileRepositoryInterface
{
    public function __construct(Container $app,...$args);
    public function setupServices(): self;
    public function setupClassDiscover($services=[]);
}