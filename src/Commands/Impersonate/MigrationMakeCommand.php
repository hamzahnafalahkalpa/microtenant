<?php

namespace Hanafalah\MicroTenant\Commands\Impersonate;

use Hanafalah\LaravelSupport\Concerns\Support\HasArray;
use Hanafalah\LaravelSupport\Concerns\Support\HasCache;

class MigrationMakeCommand extends EnvironmentCommand
{
    use HasCache, HasArray;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'impersonate:make-migration {name} {--app} {--group}
                            {--app_id= : The id of the app}
                            {--group_id= : The id of the group}
                            {--tenant_id= : The id of the tenant}';
    protected $lib       = 'migration';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'This command is create miggration in impersonate application.';


    /**
     * Execute the console command.
     */
    public function handle()
    {
        // CHECKING EXISTING IMPERSONATE APP
        $name = $this->argument("name");
        $pos  = strpos($name, 'to_');

        $tableName = ($pos !== false) ? substr($name, $pos + 3) : strtolower($name);

        $this->isChenkingImpersonateApp($this->lib);
        list($className, $inFolder) = $this->checkingInFolder();

        $this->generatorCommandMigration([
            "FULL_PATH"     => static::$__fullPath,
            "BASE_PATH"     => static::$__basePath,
            "STUB_PATH"     => __DIR__ . "/Stubs/MakeMigration.stub",
            "CLASS_NAME"    => $tableName,
            "SEGMENTATION"  => "migration",
            "FILE_NAME"     => $this->generateFileName($tableName),
            "IN_FOLDER"     => call_user_func(function () use ($inFolder, $className) {
                return (isset($className)) ? $inFolder : null;
            })
        ]);
    }

    private function generateFileName($tableName)
    {
        $nameMigration = $this->checkingInFolder(false);
        $timestamp     = date('Y_m_d_His');
        $nameMigration = (isset($nameMigration[0])) ? $nameMigration[0] : $tableName;
        $description   = "create_table_to_" . $nameMigration;
        $fileName      = $timestamp . '_' . $description;
        return $fileName;
    }
}
