<?php

namespace Zahzah\MicroTenant\Commands;

use Zahzah\LaravelStub\Facades\Stub;

class InterfaceMakeCommand extends EnvironmentCommand
{

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'micro:make-interface 
                {package-name      : The name of module class}
                {--name            : The name of the class module}
                {--class-namespace : Using class namespace to generate interface}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new contract class';

    /** @var string */
    protected $__name,$__class_namespace;

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $this->setup();
        $this->__class_namespace = $this->option('class-namespace');
        $this->cardLine('Generating Interface '.$this->__class_namespace,function(){
            $this->__name = !$this->option('name') ? $this->getStaticPackageNameResult() : $this->option('name');
            $save_path = $this->getGenerateLocation().'/'.$this->contractsGeneratorPath();
            Stub::init($this->getBaseStub().'/interface.stub',[
                'NAMESPACE'              => $this->generateNamespace('contracts'),
                'INTERFACE_NAME'         => $this->__name.'Interface',
                'METHODS'                => ''
            ])->saveTo($save_path,$this->__name.'Interface.php');
        });
    }
}
