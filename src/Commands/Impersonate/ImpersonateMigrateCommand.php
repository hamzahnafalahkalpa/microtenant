<?php

namespace Hanafalah\MicroTenant\Commands\Impersonate;

use Hanafalah\LaravelSupport\Concerns\Support\HasArray;
use Hanafalah\LaravelSupport\Concerns\Support\HasCache;
use Hanafalah\MicroTenant\Commands\Impersonate\Concerns\HasImpersonate;
use Hanafalah\MicroTenant\Commands\EnvironmentCommand;
use Hanafalah\MicroTenant\Facades\MicroTenant;
use Illuminate\Support\Str;
use function Laravel\Prompts\select;

class ImpersonateMigrateCommand extends EnvironmentCommand
{
    use HasCache, HasArray, HasImpersonate;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'impersonate:migrate 
                                {--app= : The type of the application}
                                {--group= : The type of the group}
                                {--tenant= : The type of the tenant}
                                {--app_id= : The id of the application}
                                {--group_id= : The id of the group}
                                {--tenant_id= : The id of the tenant}
                                {--skip= : The skip other tenant}
                            ';

    protected $__cache_data;
    protected $__choosed_impersonate;

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'This command is used to impersonate as a certain tenant application.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        config(['micro-tenant.installing' => true]);
        $this->findApplication(function($project){
            $this->findGroup($project,function($group){
                $this->findTenant($group);
                $this->impersonateConfig([
                    "project"    => $this->__application,
                    "group"      => $this->__group,
                    "tenant"     => $this->__tenant
                ]);
                $this->setImpersonateNamespace();
                $direct_access = config('micro-tenant.direct_provider_access', true);
                config(['micro-tenant.direct_provider_access' => true]);
                MicroTenant::tenantImpersonate($this->__tenant ?? $this->__group ?? $this->__application);
                config(['micro-tenant.direct_provider_access' => $direct_access]);

                if ($this->option('app'))    $field = 'project';
                if ($this->option('group'))  $field = 'group';
                if ($this->option('tenant')) $field = 'tenant';
                if (!isset($field)) $field = select('Choose impersonate', ['project', 'group', 'tenant']);
                $this->__choosed_impersonate = $field ?? 'tenant';
                if (isset($this->__impersonate[$field])) {
                    $impersonate     = $this->__impersonate[$field];
                    $tenant_path     = $impersonate['paths']['base_path'];
                    $migration_path  = $tenant_path.$impersonate['libs']['migration'];
                    // dd(config('database.connections'));
                    switch ($field) {
                        case 'project':
                            $this->setupDb($field,$this->__application);
                            $this->overrideCaller($this->__application,[$migration_path,$migration_path.DIRECTORY_SEPARATOR.'changes',$migration_path.DIRECTORY_SEPARATOR.'clusters']);
                            $allGroup = $this->getAllGroupWithApp($this->__application->id);
                            if (isset($allGroup) && count($allGroup) > 0) {
                                $path        = $migration_path.DIRECTORY_SEPARATOR.'centrals';
                                $tenant_path = $migration_path.DIRECTORY_SEPARATOR.'tenants';
                                foreach($allGroup as $group) {
                                    $this->setupDb('group',$group);
                                    $this->overrideCaller($group,[$path,$path.DIRECTORY_SEPARATOR.'changes',$path.DIRECTORY_SEPARATOR.'clusters']);
    
                                    $allTenants  = $this->getAllTenantWithGroup($group->id);
                                    if(isset($allTenants) && count($allTenants) > 0) {
                                        foreach($allTenants as $tenant) {
                                            $this->setupDb('tenant',$tenant);
                                            $this->overrideCaller($tenant,[$tenant_path,$tenant_path.DIRECTORY_SEPARATOR.'changes',$tenant_path.DIRECTORY_SEPARATOR.'clusters']);
                                        }
                                    }
                                }
                            }
                        break;
                        case 'group':
                            $this->setupDb($field,$this->__group);
                            $this->overrideCaller($this->__group,[$migration_path,$migration_path.DIRECTORY_SEPARATOR.'changes',$migration_path.DIRECTORY_SEPARATOR.'clusters']);
            
                            $allTenants  = $this->getAllTenantWithGroup($this->__group->id);
                            $tenant_path = $migration_path.DIRECTORY_SEPARATOR."tenants";
                            foreach($allTenants as $tenant) {
                                $this->setupDb('tenant',$tenant);
                                $this->overrideCaller($tenant,[$tenant_path,$tenant_path.DIRECTORY_SEPARATOR.'changes',$tenant_path.DIRECTORY_SEPARATOR.'clusters']);
                            }
                        break;
                        default:
                            $this->setupDb('tenant',$this->__tenant);
                            $this->overrideCaller($this->__tenant,[$migration_path,$migration_path.DIRECTORY_SEPARATOR.'changes',$migration_path.DIRECTORY_SEPARATOR.'clusters']);
                        break;
                    }
                }else{
                    $this->info("Impersonate not found");
                }
            });
        });    
    }

    private function setupDb($field,$tenant){
        switch ($field) {
            case 'project':
                config([
                    'database.connections.central_app.database' => $tenant->db_name,
                    'database.connections.central_app.search_path' => $tenant->db_name == $tenant->tenancy_db_name ? 'public' : $tenant->tenancy_db_name,
                ]);
            break;
            case 'group':
                config([
                    'database.connections.central_tenant.database' => $tenant->db_name,
                    'database.connections.central_tenant.search_path' => $tenant->db_name == $tenant->tenancy_db_name ? 'public' : $tenant->tenancy_db_name,
                ]);
            break;
            default:
                config([
                    'database.connections.tenant.database' => $tenant->db_name,
                    'database.connections.tenant.search_path' => $tenant->db_name == $tenant->tenancy_db_name ? 'public' : $tenant->tenancy_db_name,
                ]);
            break;
        }
    }

    private function getAllGroupWithApp($id) {
        return $this->TenantModel()->central()->where('parent_id',$id)->select('id','name','flag','props')->orderBy('id')->get();
    }

    private function getAllTenantWithGroup($id) {
        return$this->TenantModel()->select('id','name','flag','props')->parentId($id)->orderBy('id')->get();
    }

    private function overrideConfig(mixed $path) {
        // $path = Str::replace('\\',DIRECTORY_SEPARATOR,$path);
        // if (is_array($path)){
        //     foreach ($path as &$value) {
        //         $value = Str::replace('\\',DIRECTORY_SEPARATOR,$value);
        //     }
        // }
        config(['tenancy.migration_parameters.--path' => $path]);
    }

    private function overrideCaller($tenant,mixed $paths) {
        $paths = $this->mustArray($paths);
        foreach ($paths as $path) {
            $path = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $path);
            $path = realpath($path) ?: $path;
            if (Str::endsWith($path,'clusters')){
                if (is_dir($path)){
                    $directories = array_diff(scandir($path, SCANDIR_SORT_NONE), ['.', '..']);
                    $dir_paths = [];
                    foreach ($directories as $directory) {
                        $yearDir = $path.DIRECTORY_SEPARATOR.'tmp'.DIRECTORY_SEPARATOR.$directory.'_'.date('Y');
                        if (is_dir($yearDir)) {
                            $this->cleanupTmpDirectory($yearDir);
                        }

                        // Create directory with full permissions (0777) to avoid permission issues in Docker
                        if (!is_dir($yearDir)) {
                            mkdir($yearDir, 0777, true);
                        }
                        // Ensure directory is writable
                        @chmod($yearDir, 0777);

                        $sourceDir = $path.DIRECTORY_SEPARATOR.$directory;
                        if (is_dir($sourceDir)) {
                            $files = array_diff(scandir($sourceDir, SCANDIR_SORT_NONE), ['.', '..']);
                            foreach ($files as $file) {
                                $sourceFile = $sourceDir.DIRECTORY_SEPARATOR.$file;
                                if (is_file($sourceFile)) {
                                    $fileInfo = pathinfo($file);
                                    $newFileName = $fileInfo['filename'].'_'.date('Y').($fileInfo['extension'] ? '.'.$fileInfo['extension'] : '');
                                    $destFile = $yearDir.DIRECTORY_SEPARATOR.$newFileName;
                                    copy($sourceFile, $destFile);
                                    // Ensure copied file is writable for later cleanup
                                    @chmod($destFile, 0666);
                                }
                            }
                        }
                        $directory = 'tmp'.DIRECTORY_SEPARATOR.$directory.'_'.date('Y');
                        $dir_paths[] = $path.DIRECTORY_SEPARATOR.$directory;
                    }
                    $this->overrideCaller($tenant, $dir_paths);
                }
            }else{
                if (Str::contains($path, 'clusters')){

                }
                $this->overrideConfig($path);
                $this->caller($tenant);
            }
        }
    }

    /**
     * Safely cleanup temporary migration directory
     *
     * @param string $directory
     * @return void
     */
    private function cleanupTmpDirectory(string $directory): void
    {
        if (!is_dir($directory)) {
            return;
        }

        $files = glob("$directory/*.*");
        if ($files === false) {
            return;
        }

        foreach ($files as $file) {
            if (is_file($file)) {
                try {
                    // Try to make file writable before deleting
                    @chmod($file, 0666);
                    if (!@unlink($file)) {
                        // If unlink fails, log but don't throw
                        $this->warn("Could not delete temporary file: $file");
                    }
                } catch (\Throwable $e) {
                    // Log the error but continue with migration
                    $this->warn("Error deleting temporary file $file: " . $e->getMessage());
                }
            }
        }

        // Try to remove the directory
        try {
            @rmdir($directory);
        } catch (\Throwable $e) {
            $this->warn("Could not remove temporary directory: $directory");
        }
    }

    private function caller($tenant) {
        MicroTenant::impersonate($tenant,false);
        $this->call("tenants:migrate", [    
            '--path'    => config('tenancy.migration_parameters.--path'),
            '--tenants' => $tenant->id
        ]);
    }
}
