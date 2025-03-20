<?php

namespace Zahzah\MicroTenant\Commands;

use Zahzah\ModuleVersion\Concerns\Commands\Schema\SchemaPrompt;

class AddInstallationSchemaMakeCommand extends EnvironmentCommand{
    use SchemaPrompt;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */


    protected $signature = 'micro:add-installation-schema {namespace} {--app-name= : App Name} {--description= : Schema description}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command ini digunakan untuk menambahkan schema';

    /**
     * Execute the console command.
     */
    public function handle(){
        $this->runInstallationSchema();
    }
    
    protected function getInstallationSchemaStubPath(): string{
        return 'MicroTenantStubs/add-installation-schema.stub';
    }
}