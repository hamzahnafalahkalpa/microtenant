<?php

namespace Hanafalah\MicroTenant\Commands;

use Hanafalah\ModuleVersion\Concerns\Commands\Schema\SchemaPrompt;

class AddSchemaMakeCommand extends EnvironmentCommand
{
    use SchemaPrompt;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'micro:add-schema {namespace} {--m|model : whether to create a model} {--i|installation : whether to create an installation schema}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command ini digunakan untuk menambahkan schema';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $namespace = $this->argument('namespace');
        if (!$this->option('model') && !$this->option('installation')) {
            $choice = $this->choice(
                'What do you want to do?',
                [
                    'Create model schema',
                    'Create installation schema',
                ]
            );
            switch ($choice) {
                case 'Create model schema':
                    $this->callModelSchema();
                    break;
                case 'Create installation schema':
                    $this->callInstallationSchema($namespace);
                    break;
            }
        }

        if ($this->option('installation')) $this->callInstallationSchema($namespace);
        if ($this->option('model'))   $this->callModelSchema();
    }

    private function callModelSchema()
    {
        $this->call('micro:add-model-schema', [
            'namespace' => $this->argument('namespace')
        ]);
    }
}
