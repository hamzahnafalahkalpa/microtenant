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

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $flags = [
            'FLAG_APP_TENANT' => [
                'message' => 'Project',
                'name' => $this->option('project_name')
            ],
            'FLAG_CENTRAL_TENANT' => [
                'message' => 'Group',
                'name' => $this->option('group_name')
            ],
            'FLAG_TENANT' => [
                'message' => 'Tenant',
                'name' => $this->option('tenant_name')
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
        $tenant = app(config('app.contracts.Tenant'))->prepareStoreTenant($this->requestDto(TenantData::class,[
            'parent_id' => $parent_id ?? null,
            'name' => $name,
            'flag' => $flag,
            'reference_id' => $reference_id ?? null,
            'reference_type' => $reference_type ?? null
        ]));

        $this->call("generator:add",[
            'namespace' => $message."s\\".Str::studly($name),
            '--package-author' => 'hamzah',
            '--package-email' => 'hamzahnafalah@gmail.com',
            '--pattern' => Str::lower($message)
        ]);
        return $tenant;
    }

    public function callCustomMethod(): array
    {
        return ['Model'];
    }
}
