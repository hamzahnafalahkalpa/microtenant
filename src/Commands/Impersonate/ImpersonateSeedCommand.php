<?php

namespace Hanafalah\MicroTenant\Commands\Impersonate;

use Hanafalah\LaravelSupport\Concerns\Support\HasArray;
use Hanafalah\LaravelSupport\Concerns\Support\HasCache;
use Hanafalah\MicroTenant\Commands\Impersonate\Concern\generatorHelperPath;
use Illuminate\Support\Str;

class ImpersonateSeedCommand extends EnvironmentCommand
{
    use HasCache, HasArray, generatorHelperPath;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'impersonate:seed {--name= : specify the path of the lib} 
                            {--all} {--app} {--group}
                            {--app_id= : The id of the app}
                            {--group_id= : The id of the group}
                            {--tenant_id= : The id of the tenant}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'This command is running seeding in a application impersonate.';


    /**
     * Execute the console command.
     */
    public function handle()
    {
        config([
            'micro-tenant.superadmin' => true
        ]);
        if ($this->option('all')) {
            $this->seeding();
            foreach (['--app', '--group'] as $key => $value) {
                $this->call("impersonate:seed", [
                    $value => true,
                    '--name' => $this->option('name'),
                ]);
            }
        } else {
            $this->seeding();
        }
        config([
            'micro-tenant.superadmin' => false
        ]);
    }

    private function seeding()
    {
        $impersonate = $this->getImpersonate();

        $namespace   = Str::replace('/', '\\', $impersonate->config['namespace'] . '\\' . ($impersonate->config['libs']['seeder'] ?? 'Database\\Seeders') . '\\');
        $namespace   .= $this->option('name') ?? 'DatabaseSeeder';
        if (\class_exists($namespace)) {
            $this->call('db:seed', [
                '--class' => $namespace,
            ]);

            $this->info('Seeding ' . $namespace . ' done!');
        } else {
            $this->error('Seeding ' . $namespace . ' not found!');
        }
    }
}
