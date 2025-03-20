<?php

namespace Zahzah\MicroTenant\Commands\Impersonate\Concern;

use Zahzah\LaravelStub\Facades\Stub;
use Zahzah\MicroTenant\Concerns\Commands\HasGeneratorAction;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;


Trait generatorHelperPath  {
    protected $__signatureName;



    /**
     * Get the full path of the impersonate lib.
     *
     * @param  array  $args
     * @return string
     */
    protected function getGenerateLocation($args): string {
        return $args["BASE_PATH"]."/src/". static::$__impersonateCache->{$this->__field ?? 'tenant'}->config['libs'][$args['SEGMENTATION']];
    }

    /**
     * Checking if the name given has a slash in it. If yes, it will create the directory
     * and update the full path. If no, it will return the last part of the name given
     *
     * @return string
     */

    protected function checkingInFolder($Mkdir = true) {
        if (Str::contains($this->argument('name'), '/')) {
            $pathParts              = explode('/', $this->argument('name'));
            $fullDirectoryPath      = static::$__fullPath . '/' . implode('/', array_slice($pathParts, 0, -1));

            if (!File::exists($fullDirectoryPath) && $Mkdir) {
                File::makeDirectory($fullDirectoryPath, 0755, true);
            }

            static::$__fullPath = $fullDirectoryPath;
        }

        $className = isset($pathParts) ? end($pathParts) : null;
        return [$className ,dirname($this->argument('name')) ?? null];
    }


    /**
     * Generate a command from stub to given path.
     *
     * @param  array  $args
     * @return void
     */
    protected function generatorCommandMigration($args): void{
        $path = $this->getGenerateLocation($args);
        $path = $this->casterNameSpace($args,$path);

        Stub::init("MicroTenantStubs\MakeMigration.stub",[
            "CLASS_NAME" => $args['CLASS_NAME'],
            "NAMESPACE"  => $path,
        ])->saveTo($path,$args['FILE_NAME'].'.php',);
    }

    protected function generatorCommandPolicy($args): void{
        $path = $this->getGenerateLocation($args);
        $path = $this->casterNameSpace($args,$path);

        Stub::init("MicroTenantStubs\MakePolicy.stub",[
            "CLASS_NAME" => $args['CLASS_NAME'],
            "NAMESPACE"  => $path,
        ])->saveTo($path,$args['CLASS_NAME'].'.php',);
    }

    protected function generatorCommandResource($args): void{
        $path = $this->getGenerateLocation($args);
        $path = $this->casterNameSpace($args,$path);

        Stub::init("MicroTenantStubs\MakeResource.stub",[
            "CLASS_NAME" => $args['CLASS_NAME'],
            "NAMESPACE"  => $path,
        ])->saveTo($path,$args['CLASS_NAME'].'.php',);
    }

    protected function generatorCommandMiddleware($args): void{
        $path = $this->getGenerateLocation($args);
        $path = $this->casterNameSpace($args,$path);
        Stub::init("MicroTenantStubs\MakeMiddlware.stub",[
            "CLASS_NAME" => $args['CLASS_NAME'],
            "NAMESPACE"  => $path,
        ])->saveTo($path,$args['CLASS_NAME'].'.php',);
    }
    
    /**
     * Generate a migration from stub to given path.
     *
     * @param  array  $args
     * @return void
     */
    protected function generatorCommandConcern($args): void{
        $path = $this->getGenerateLocation($args);
        $path = $this->casterNameSpace($args,$path);

        Stub::init("MicroTenantStubs\MakeConcern.stub",[
            "CLASS_NAME" => $args['CLASS_NAME'],
            "NAMESPACE"  => $path,
        ])->saveTo($path,$args['CLASS_NAME'].'.php',);
    }


    /**
     * Generate a model from stub to given path.
     *
     * @param  array  $args
     * @return void
     */
    protected function generatorCommandModel($args): void{
        $path = $this->getGenerateLocation($args);
        $path = $this->casterNameSpace($args,$path);

        Stub::init("MicroTenantStubs\MakeModel.stub",[
            "CLASS_NAME" => $args['CLASS_NAME'],
            "NAMESPACE"  => $path,
        ])->saveTo(static::$__fullPath,$args['CLASS_NAME'].'.php');
    }

    protected function generatorCommandController($args): void{
        $path = $this->getGenerateLocation($args);
        $path = $this->casterNameSpace($args,$path);

        Stub::init($this->getStub($args),[
            "NAMESPACE"          => $path,
            "EXTENDED_CLASS"     => $args['EXTENDED_CLASS'],
            "NAMESPACE_EXTENDED" => $args['NAMESPACE_EXTENDED'],
            "CLASS_NAME" => call_user_func(function() use ($args) {
                return (isset($args['IS_ENVIRONMENT'])) 
                    ? "Environment".$args['CLASS_NAME'] 
                    : $args['CLASS_NAME'];
            }),
            "USE_IN"     => call_user_func(function() use ($args) {
                return null; 
            }),
        ])->saveTo(
                static::$__fullPath,
                (isset($args['IS_ENVIRONMENT']))  
                    ? "Environment".$args['CLASS_NAME'].'.php' 
                    : $args['CLASS_NAME'].'.php'
        );
    }

    protected function generatorCommandRequest($args): void{
        $path = $this->getGenerateLocation($args);
        $path = $this->casterNameSpace($args,$path);

        Stub::init("MicroTenantStubs\MakeRequest.stub",[
            "NAMESPACE"  => $path,
            "CLASS_NAME" => $args['CLASS_NAME'],
        ])->saveTo($path,$args['CLASS_NAME'].'.php');
    }

    private function getStub($args) {
        if(isset($args['IS_ENVIRONMENT'])) {
            return "MicroTenantStubs\MakeControllerEnv.stub";
        }elseif(isset($args['IS_API_RESOURCE'])) {
            return "MicroTenantStubs\MakeControllerRes.stub";
        }else {
            return "MicroTenantStubs\MakeController.stub";
        }
    }

    private function casterNameSpace($args,$path) {
        if(isset($args['IN_FOLDER'])) {
            $path .= '\\'.$args["IN_FOLDER"];   
        }elseif(isset($args['FOLDER_NAME'])) {
            $path .= '\\'.$args["FOLDER_NAME"];
        }

        $path = str_replace('/', '\\', $path);
        return $path;
    }

}   
