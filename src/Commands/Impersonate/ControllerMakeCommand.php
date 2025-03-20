<?php

namespace Hanafalah\MicroTenant\Commands\Impersonate;

use Hanafalah\LaravelSupport\Concerns\Support\HasArray;
use Hanafalah\LaravelSupport\Concerns\Support\HasCache;
use Illuminate\Support\Str;

class ControllerMakeCommand extends EnvironmentCommand
{
    use HasCache, HasArray;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'impersonate:make-controller {name} 
                            {--environment : with environtment} 
                            {--full : with environment and apiResources} 
                            {--apiResource : with resource controller} 
                            {--app} {--group}
                            {--app_id= : The id of the app}
                            {--group_id= : The id of the group}
                            {--tenant_id= : The id of the tenant}';
    protected $lib       = 'controller';
    protected $__field;

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'This command is create controller in impersonate application.';


    /**
     * Execute the console command.
     */
    public function handle()
    {
        if ($this->option('app')) $this->__field   = "application";
        if ($this->option('group')) $this->__field = "group";

        // CHECKING EXISTING IMPERSONATE APP
        $this->isChenkingImpersonateApp($this->lib, $this->__field);
        list($className, $inFolder) = $this->checkingInFolder();

        if ($this->option('environment') || $this->option("full")) {
            $this->generatorCommandController([
                "IS_ENVIRONMENT"     => true,
                "FULL_PATH"          => static::$__fullPath,
                "BASE_PATH"          => static::$__basePath,
                "STUB_PATH"          => __DIR__ . "/Stubs/MakeController.stub",
                "CLASS_NAME"         => $className ?? $this->argument("name"),
                "SEGMENTATION"       => $this->lib,
                "EXTENDED_CLASS"     => null,
                "NAMESPACE_EXTENDED" => null,
                "IN_FOLDER"          => call_user_func(function () use ($inFolder, $className) {
                    return (isset($className)) ? $inFolder : null;
                }),
            ]);
        }

        if ($this->option("apiResource") || $this->option("full")) {
            $segments    = explode('/', $this->argument("name"));
            $lastSegment = strtolower(end($segments)) . "Request";
            $request     = Str::plural($lastSegment);
            foreach (["viewRequest", "showRequest", "storeRequest", "updateRequest", "deleteRequest"] as $request) {
                $this->generatorCommandRequest([
                    "FULL_PATH"     => static::$__fullPath,
                    "BASE_PATH"     => static::$__basePath,
                    "CLASS_NAME"    => $request,
                    "FOLDER_NAME"   => Str::plural($lastSegment),
                    "SEGMENTATION"  => "request",
                ]);
            }
        }

        $this->generatorCommandController([
            "FULL_PATH"     => static::$__fullPath,
            "BASE_PATH"     => static::$__basePath,
            "STUB_PATH"     => __DIR__ . "/Stubs/MakeController.stub",
            "CLASS_NAME"    => $className ?? $this->argument("name"),
            "SEGMENTATION"  => $this->lib,
            "IN_FOLDER"     => call_user_func(function () use ($inFolder, $className) {
                return (isset($className)) ? $inFolder : null;
            }),
            "EXTENDED_CLASS" => call_user_func(function () use ($className) {
                return ($this->option("environment") || $this->option("full")) ? "Environment" . $className ?? $this->argument("name") : "BaseController";
            }),
            "IS_API_RESOURCE"    => call_user_func(function () {
                return ($this->option("apiResource") || $this->option("full")) ? true : null;
            }),
            "NAMESPACE_EXTENDED" => call_user_func(function () use ($className) {
                $namespaceReq = static::$__basePath . "/src/" . static::$__impersonateCache->{$this->__field ?? "tenant"}->config["libs"]['request'] . "/" . Str::plural(strtolower($className ?? $this->argument("name")) . "Request");
                $namespaceReq = str_replace('/', '\\', $namespaceReq);
                if (!$this->option("environment") && !$this->option("apiResource") && !$this->option("full")) {
                    return "use Hanafalah\LaravelSupport\Controllers\BaseController;";
                } else {
                    if ($this->option("full")) {
                        $extendedClass = null;
                    } else {
                        $extendedClass = "use Hanafalah\LaravelSupport\Controllers\BaseController;";
                    }
                    return "
{$extendedClass}
use {$namespaceReq}\{
    viewRequest,showRequest,storeRequest,updateRequest,deleteRequest
};
                            ";
                }
            }),
        ]);
    }
}
