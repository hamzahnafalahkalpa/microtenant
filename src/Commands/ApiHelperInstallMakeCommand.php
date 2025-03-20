<?php

namespace Zahzah\MicroTenant\Commands;

use Zahzah\ApiHelper\Commands\InstallMakeCommand as CommandsInstallMakeCommand;

class ApiHelperInstallMakeCommand extends CommandsInstallMakeCommand{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'micro:helper-install {--reference-id= : Reference Id} {--reference-type= : Reference Type}';

    /**
     * Execute the console command.
     */
    public function handle(){
        parent::handle();
    }

    /**
     * Ask if user want to generate api access
     *
     * @return void
     */
    protected function askingGenerateApiAccess(){
        if ($this->askGenerateApiAccess()){
            $this->info('✔️  Generate Key');
            $this->call('helper:generate',[
                '--reference-id'   => $this->option('reference-id'),
                '--reference-type' => $this->option('reference-type')
            ]);
            $this->info('✔️  Generated Key');
        }
    }
}