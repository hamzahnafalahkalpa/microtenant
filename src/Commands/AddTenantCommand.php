<?php

namespace Hanafalah\MicroTenant\Commands;

use Hanafalah\LaravelSupport\Concerns\Support\HasRequestData;
use Hanafalah\MicroTenant\Contracts\Data\TenantData;
use Hanafalah\ModuleWorkspace\Contracts\Data\WorkspaceData;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Str;

use function Laravel\Prompts\select;
use function Laravel\Prompts\text;

class AddTenantCommand extends EnvironmentCommand
{
    use HasRequestData;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'micro:add-tenant 
                            {--project_name= : Nama project}
                            {--group_name= : Nama group}
                            {--tenant_name= : Nama tenant}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command ini digunakan untuk pembuatan tenant';

    
    private function generatePath(array $config_laravel_package, string $path, string $name, string $type, string $context):string {
        return $path.DIRECTORY_SEPARATOR.$name.DIRECTORY_SEPARATOR.'src'.DIRECTORY_SEPARATOR.$config_laravel_package['patterns'][$type]['generates'][$context]['path'];
    }

    public function handle()
    {
        $config_laravel_package = config('laravel-package-generator');
        $flags = [
            'FLAG_APP_TENANT' => [
                'message'        => 'Project',
                'name'           => $name = $this->option('project_name') ?? '{{name}}',
                'path'           => $published_at = $config_laravel_package['patterns']['project']['published_at'],
                'provider'       => 'Projects',
                'config_path'    => $this->generatePath($config_laravel_package, $published_at,$name,'project','config'),
                'migration_path' => $this->generatePath($config_laravel_package, $published_at,$name,'project','migration'),
            ],
            'FLAG_CENTRAL_TENANT' => [
                'message'        => 'Group',
                'name'           => $name = $this->option('group_name') ?? '{{name}}',
                'path'           => $published_at = $config_laravel_package['patterns']['group']['published_at'],
                'config_path'    => $this->generatePath($config_laravel_package, $published_at,$name,'group','config'),
                'migration_path' => $this->generatePath($config_laravel_package, $published_at,$name,'group','migration'),
            ],
            'FLAG_TENANT' => [
                'message'        => 'Tenant',
                'name'           => $name = $this->option('tenant_name') ?? '{{name}}',
                'path'           => $published_at = $config_laravel_package['patterns']['tenant']['published_at'],
                'config_path'    => $this->generatePath($config_laravel_package, $published_at,$name,'tenant','config'),
                'migration_path' => $this->generatePath($config_laravel_package, $published_at,$name,'tenant','migration'),
            ]
        ];

        foreach ($flags as $flag => $condition) {
            $projects = $this->TenantModel()->CFlagIn($flag)->orderBy('name','asc')->get();
            $project  = select(
                label : "Pilih {$condition['message']}",
                options : ["Buat {$condition['message']} Baru",...$projects->pluck('name')->toArray()],
                default : null,
                required : true
            );
            if ($project == "Buat {$condition['message']} Baru"){
                $project = $this->createTenant($this->TenantModel()->constant($this->TenantModel(),$flag), $condition, $parent_id ?? null);
            }else{
                $project = $this->TenantModel()->CFlagIn($flag)->where('name',$project)->firstOrFail();
            }
            $parent_id = $project->getKey();
        }
    }

    protected function createTenant(string $flag, array $condition, mixed $parent_id = null): Model{
        $message = $condition['message'];
        $name = $conditionp['name'] ?? text(
            label: "Tell me your $message name ?",
            placeholder: 'E.g. MyApp',
            hint: 'This will be displayed on your tenant name.'
        );
        if ($flag == 'TENANT'){            
            $workspace = app(config('app.contracts.Workspace'))->prepareStoreWorkspace($this->requestDto(WorkspaceData::class,[
                'name' => $name,
                'setting' => [
                    'address' => [
                        'name'           => 'sangkuriang',
                        'province_id'    => null,
                        'district_id'    => null,
                        'subdistrict_id' => null,
                        'village_id'     => null
                    ],
                    'email'   => 'hamzahnuralfalah@gmail.com',
                    'phone'   => '0819-0652-1808',
                    'owner_id' => null,
                    'owner' => [
                        'id' => null,
                        'name' => null
                    ]
                ]
            ]));
            $reference_id   = $workspace->getKey();
            $reference_type = $workspace->getMorphClass();
        }
        
        $studly_name    = Str::studly($name);
        $path           = Str::replace('{{name}}',$studly_name,Str::replace(base_path().'/','',$condition['path'] ?? ''));
        $config_path    = Str::replace('{{name}}',$studly_name,Str::replace(base_path().'/','',$condition['config_path'] ?? ''));
        $migration_path = Str::replace('{{name}}',$studly_name,Str::replace(base_path().'/','',$condition['migration_path'] ?? ''));
        $tenant = app(config('app.contracts.Tenant'))->prepareStoreTenant($this->requestDto(TenantData::class,[
            'parent_id'      => $parent_id ?? null,
            'name'           => $name,
            'flag'           => $flag,
            'reference_id'   => $reference_id ?? null,
            'reference_type' => $reference_type ?? null,
            'path'           => $path,
            'config_path'    => $config_path,
            'migration_path' => $migration_path
        ]));
        $this->call("micro:add-package",[
            'namespace' => $message."s\\".Str::studly($name),
            '--package-author' => 'hamzah',
            '--package-email' => 'hamzahnafalah@gmail.com',
            '--pattern' => Str::lower($message),
            '--main-id' => $tenant->getKey()
        ]);
        return $tenant;
    }

    public function callCustomMethod(): array
    {
        return ['Model'];
    }
}
