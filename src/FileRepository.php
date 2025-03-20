<?php

declare(strict_types=1);

namespace Hanafalah\MicroTenant;

use Illuminate\Container\Container;
use Illuminate\Filesystem\Filesystem;
use Hanafalah\LaravelSupport\FileRepository as LaravelSupportFileRepository;
use Hanafalah\MicroTenant\Contracts\FileRepositoryInterface;

class FileRepository extends LaravelSupportFileRepository implements FileRepositoryInterface
{
    /** @var string */
    protected $__json_path;

    /** @var array */
    protected $__micro_services = [];

    /** @var Filesystem */
    protected $__file_system;

    public function __construct(Container $app, ...$args)
    {
        parent::__construct($app, ...$args);
        $this->__config            = config('micro-tenant');
        $this->__micro_services    = $this->__config['microservices'];
        $this->__json_path         = base_path('microservice.json');
        $this->__file_system       = new Filesystem();
    }

    public function setupServices(): self
    {
        return $this;
    }

    public function setupClassDiscover($services = [])
    {
        if (!\is_array($services)) $services = [$services];
        if (count($services) > 0) {
            $paths = [];
            foreach ($services as $service) $paths[] = $this->__micro_services[$service]['path'];
            $list_class_paths = $this->eachServices($paths, function ($path) {
                return $path;
            });
            $conf_provider = 'laravel-support.class_discovering.provider';
            config([$conf_provider . '.paths' => $list_class_paths]);
            return $this->discoveringClass($list_class_paths, config($conf_provider . '.base_classes'));
        }
    }
}
