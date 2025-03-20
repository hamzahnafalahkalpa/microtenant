<?php

namespace Hanafalah\MicroTenant\Commands\Impersonate;

use Illuminate\Support\Facades\File;
use Hanafalah\LaravelSupport\Concerns\Support\HasArray;
use Hanafalah\LaravelSupport\Concerns\Support\HasCache;
use Hanafalah\MicroTenant\Commands\Impersonate\Concern\generatorHelperPath;
use Illuminate\Support\Str;

class RequestMakeCommand extends EnvironmentCommand
{
    use HasCache, HasArray, generatorHelperPath;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'impersonate:make-request {name} {--app} {--group}
                            {--app_id= : The id of the app}
                            {--group_id= : The id of the group}
                            {--tenant_id= : The id of the tenant}';
    protected $lib       = 'request';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'This command is create request rules in impersonate application.';


    /**
     * Execute the console command.
     */
    public function handle()
    {
        // CHECKING EXISTING IMPERSONATE APP
        $this->isChenkingImpersonateApp($this->lib);
        list($className, $inFolder) = $this->checkingInFolder();

        $this->generatorCommandRequest([
            "FULL_PATH"     => static::$__fullPath,
            "BASE_PATH"     => static::$__basePath,
            "CLASS_NAME"    => $className ?? $this->argument("name"),
            "FOLDER_NAME"   => Str::plural(strtolower($this->argument("name")) . "Request"),
            "SEGMENTATION"  => $this->lib,
            "IN_FOLDER"     => call_user_func(function () use ($className, $inFolder) {
                return (isset($className)) ? $inFolder : null;
            }),
        ]);
    }
}
