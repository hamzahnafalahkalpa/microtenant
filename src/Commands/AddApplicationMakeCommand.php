<?php

namespace Hanafalah\MicroTenant\Commands;

use Illuminate\Support\Facades\Artisan;
use Hanafalah\MicroTenant\Concerns\Commands\HasGeneratorAction;
use Hanafalah\MicroTenant\Concerns\Commands\Tenant\HasService;
use Hanafalah\ModuleVersion\{
    Concerns\Commands\Schema\SchemaPrompt
};

class AddApplicationMakeCommand extends EnvironmentCommand
{
    use SchemaPrompt;
    use HasGeneratorAction {
        HasGeneratorAction::askAppVersion insteadof HasService;
    }
    use HasService {
        HasService::callInstallationSchema insteadof SchemaPrompt;
    }

    public $__microtenant_config = [];

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'micro:add-application {--namespace= : Application namespace} {--description= : Schema description}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'This command is used to add new application';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->init()->setServiceName('app_version');
        $this->setNeedSource();

        $this->askAppVersion();
        if (app()->environment() == 'local') {
            if (isset($this->__ask_app)) $this->installing($this->__ask_app->name);
        }

        if ($this->getAskAppResult() !== null) {
            $arguments = [
                '--reference-id'   => $this->getAskAppResult()->getKey(),
                '--reference-type' => $this->getAskAppResult()->getMorphClass()
            ];
        }
        $this->call('micro:helper-install', $arguments ?? []);
    }

    protected function callProvider()
    {
        $option = ["package-name" => $this->getStaticPackageNameResult()];
        Artisan::call("micro:make-provider", $option);
    }

    protected function callInterface()
    {
        $option = [
            "package-name" => $this->getStaticPackageNameResult(),
            "--name"       => $this->getStaticPackageNameResult()
        ];
        Artisan::call('micro:make-interface', $option);
    }

    /**
     * Get the path to the stub for the add installation schema.
     *
     * @return string The path to the stub.
     */
    protected function getAddInstallationSchemaStubPath(): string
    {
        return 'MicroTenantStubs/add-installation-schema.stub';
    }

    public function callCustomMethod(): array
    {
        return ['Model', 'GeneratorPath'];
    }
}
