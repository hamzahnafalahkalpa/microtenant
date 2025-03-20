<?php

namespace Hanafalah\MicroTenant\Concerns\Commands\Tenant;

use Hanafalah\LaravelSupport\Concerns\Support\HasRepository;
use Hanafalah\ModuleVersion\Concerns\Commands\Installing\AppInstallPrompt;
use Hanafalah\ModuleVersion\Concerns\HasModuleService;

trait HasService
{
    use HasModuleService;
    use HasTenantPrompt;
    use HasRepository;

    protected static array $__services = [], $__service_names = [];

    /**
     * Setups the process by initializing the class and setting the service name
     * and package name from the command arguments.
     *
     * @return self The current instance of the class.
     */
    protected function setup(): self
    {
        if ($this->notReady()) {
            $this->newLine();
            $this->cardLine('Initialize Process', function () {
                $this->init()
                    ->setServiceName($this->argument('service-name'))
                    ->setChoosedService($this->getStaticServicesResult()[$this->getStaticServiceNameResult()])
                    ->setPackageName($this->argument('package-name'))
                    ->setServiceFilePath($this->getStaticPackageNameResult());
            });
        }
        return $this;
    }

    public function callInstallationSchema(string $namespace): void
    {
        $this->call('micro:add-installation-schema', [
            'namespace'            => $namespace,
            '--app-name'           => $namespace,
            '--description'        => "Installation schema for app " . $this->getAskAppResult()->name,
        ]);
    }

    /**
     * Check if the current service name is the app version service.
     *
     * @return bool
     */
    protected function isAppVersion(): bool
    {
        return static::$__service_name == 'app_version';
    }

    /**
     * Check if the service name is the tenant service.
     *
     * @return bool
     */
    protected function isTenant(): bool
    {
        return static::$__service_name == 'tenant';
    }

    /**
     * Set the service name to use for this command.
     *
     * @param string $service_name
     * @return $this
     */
    protected function setServiceName(string $service_name): self
    {
        static::$__service_name = $service_name;
        return $this;
    }

    /**
     * Set the services provided by the package.
     *
     * @return $this The current instance of the class.
     */
    protected function setServices(): self
    {
        static::$__services      = $this->getMicrotenantConfigResult()['microservices'];
        static::$__service_names = array_keys(static::$__services);
        return $this;
    }

    /**
     * Find the index of the service name in the service names array.
     *
     * @param string $service_name
     * @return int|bool The index of the service name or false if not found.
     */
    protected function findService(string $service_name): int|bool
    {
        return \array_search($this->getMicrotenantConfigResult()[$service_name], static::$__service_names);
    }

    /**
     * Set the choosed service config to use for this command.
     *
     * @param array|null $choosed_service The choosed service config to use.
     *
     * @return $this
     */
    protected function setChoosedService($choosed_service = null): self
    {
        static::$__choosed_config = $choosed_service ?? $this->getMicrotenantConfigResult()['microservices'][static::$__service_name];
        $this->setServicePath(static::$__choosed_config['path']);
        return $this;
    }
}
