<?php

namespace Zahzah\MicroTenant\Commands\Impersonate;

use Illuminate\Support\Facades\File;
use Zahzah\LaravelSupport\Concerns\Support\HasArray;
use Zahzah\LaravelSupport\Concerns\Support\HasCache;
use Zahzah\MicroTenant\Commands\Impersonate\Concern\generatorHelperPath;
use Illuminate\Support\Str;

class ModelMakeCommand extends EnvironmentCommand
{
    use HasCache, HasArray,generatorHelperPath;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'impersonate:make-model {name} {--m : with migration} 
                            {--app} {--group}
                            {--app_id= : The id of the app}
                            {--group_id= : The id of the group}
                            {--tenant_id= : The id of the tenant}';
    protected $lib       = 'model';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'This command is create model in impersonate application.';


    /**
     * Execute the console command.
     */
    public function handle()
    {
        // CHECKING EXISTING IMPERSONATE APP
        $this->isChenkingImpersonateApp($this->lib);
        list($className,$inFolder) = $this->checkingInFolder();

        $this->generatorCommandModel([
            "FULL_PATH"     => static::$__fullPath,
            "BASE_PATH"     => static::$__basePath,
            "STUB_PATH"     => __DIR__."/Stubs/MakeModel.stub",
            "CLASS_NAME"    => $className ?? $this->argument("name"),
            "SEGMENTATION"  => $this->lib,
            "IN_FOLDER"     => call_user_func(function () use ($inFolder,$className) {
                return (isset($className)) ? $inFolder : null;
            })
        ]);

        if($this->option('m')){
            $namePlural = Str::plural($className ?? $this->argument("name"));
            $this->call("impersonate:make-migration", ["name" => $namePlural]);
        }
    }
}

