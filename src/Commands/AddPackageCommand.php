<?php

namespace Hanafalah\MicroTenant\Commands;

use Hanafalah\LaravelPackageGenerator\Commands\GeneratePackageCommand;

class AddPackageCommand extends GeneratePackageCommand
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'micro:add-package 
        {namespace : Namespace Package ex: Hanafalah\\LaravelPackageGenerator}
        {--package-author= : Nama author}
        {--package-email= : Email author}
        {--pattern= : Pattern yang digunakan}
        {--main-id= : Main ID}';

    public function handle(): void{
        $main_id = $this->option('main-id') ?? null; 
        if (isset($main_id)) {
            $this->__replacements['MAIN_ID'] = $main_id;
        }
        parent::handle();
    }
}
