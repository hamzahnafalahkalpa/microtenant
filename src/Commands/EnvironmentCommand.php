<?php

namespace Zahzah\MicroTenant\Commands;

use Zahzah\MicroTenant\Concerns\Commands\Tenant;
use Zahzah\LaravelSupport\{
    Commands\EnvironmentCommand as CommandsEnvironmentCommand
};
use Zahzah\MicroTenant\FileRepository;
use Zahzah\ModuleVersion\Concerns\Commands\HasGeneratorAction;
use Zahzah\ModuleVersion\Concerns\Commands\Initialize;

class EnvironmentCommand extends CommandsEnvironmentCommand
{
    use Tenant\HasService;
    use Initialize;
    use HasGeneratorAction;
    public $__microtenant_config = [];

    /**
     * Setups the process by initializing the class and setting the service name
     * and package name from the command arguments.
     *
     * @return self The current instance of the class.
     */
    protected function setup(): self{        
        if ($this->notReady()){
            $this->newLine();
            $this->cardLine('Initialize Process',function(){
                $this->init()
                    ->setServiceName($this->getStaticServiceNameResult())
                    ->setChoosedService($this->getStaticServicesResult()[$this->getStaticServiceNameResult()])
                    ->setPackageName($this->argument('package-name'))
                    ->setServiceFilePath($this->getStaticPackageNameResult());
            });
        }
        return $this;
    }  

    /**
     * Initialize the environment command.
     *
     * This method is called right after the command is created.
     * The purpose of this method is to set the local config to "microtenant".
     *
     * @return $this
     */
    protected function init(): self{
        //INITIALIZE SECTION
        $this->initConfig()
            ->setConfig('micro-tenant',$this->__microtenant_config)
            ->setServices()
            ->setRepository(FileRepository::class)
            ->initialized();
        return $this;
    }

    /**
     * Retrieves the path of the package.
     *
     * @return string
     */
    protected function dir(): string{
        return __DIR__.'/../';
    }

    public function callCustomMethod(): array{
        return ['Model','GeneratorPath'];
    }
}
