<?php

namespace Zahzah\MicroTenant\Commands;

use Zahzah\LaravelStub\Facades\Stub;

use Illuminate\Support\Str;

class ProviderMakeCommand extends EnvironmentCommand
{

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'micro:make-provider 
                {package-name : The name of module class}
                {--name : The name of the class module}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new service provider class';

    /** @var string */
    protected $__name;

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $this->setup();

        $package_name = static::$__package_name;
        $this->__name = !$this->option('name') ? $package_name : $this->option('name');

        $save_path = $this->getGenerateLocation().'/'.$this->providerGeneratorPath();

        $this->cardLine('Creating Package Provider',function() use ($save_path,$package_name){
            Stub::init($this->getBaseStub().'/provider.stub',[
                'WHEN_BOOTED'         => function(){
                    $class_name = $this->generateNamespace();
                    $class_name = \class_name_builder(\class_basename($class_name));
                    return Stub::init($this->getBootedTenantProviderStub(),[
                        'CLASS_NAME' => $class_name,
                        'BOOTED_REGISTER' => function() use ($class_name){
                            return Stub::init($this->getBootedRegisterStub(),[
                                'CLASS_NAME' => $class_name,
                                'FACADES_PATH' => $this->facadesGeneratorPath()
                            ])->render();
                        }
                    ])->render();
                    // if ($this->option('tenant') !== null){
                    // }else{
                    //     return Stub::init($this->getBootedAppProviderStub(),[
                    //         'CLASS_NAME' => $class_name,
                    //         'BOOTED_REGISTER' => function() use ($class_name){
                    //             return Stub::init($this->getBootedRegisterStub(),[
                    //                 'CLASS_NAME' => $class_name,
                    //                 'FACADES_PATH' => $this->facadesGeneratorPath()
                    //             ])->render();
                    //         }
                    //     ])->render();
                    // }
                },
                'CLASS_NAMESPACE'   => $this->generateNamespace(),
                'NAMESPACE'         => $this->generateNamespace('provider'),
                'CONTRACT_PATH'     => $this->contractsGeneratorPath(),
                'SUPPORT_PATH'      => $this->supportsGeneratorPath(),
                'FACADES_PATH'      => $this->facadesGeneratorPath(),
                'CLASS_NAME'        => $package_name,
                'LOWER_CLASS_NAME'  => $this->lowerPackageName(),
                'CONFIG_NAME'       => Str::lower(Str::replace(' ','-',$package_name)),
                'DEFINE_FEATURE'    => ''
            ])->saveTo($save_path,$package_name.'ServiceProvider.php');
        });        
    }

    /**
     * Get the path to the stub for the add booted tenant provider.
     *
     * @return string The path to the stub.
     */
    protected function getBootedTenantProviderStub(): string{
        return 'MicroTenantStubs/booted-tenant-provider.stub';
    }

    /**
     * Get the path to the stub for the add booted application provider.
     *
     * @return string The path to the stub.
     */
    protected function getBootedAppProviderStub(): string{
        return 'MicroTenantStubs/booted-app-provider.stub';
    }

    /**
     * Get the path to the stub for the add booted application provider.
     *
     * @return string The path to the stub.
     */
    protected function getBootedRegisterStub(): string{
        return 'MicroTenantStubs/booted-register.stub';
    }
}
