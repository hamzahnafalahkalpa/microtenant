<?php

namespace Hanafalah\MicroTenant\Concerns\Commands;

use Illuminate\Support\Facades\Artisan;
use Hanafalah\LaravelStub\Facades\Stub;
use Illuminate\Support\Str;
use Symfony\Component\Filesystem\Filesystem;

trait HasGeneratorAction
{
    protected $__tenant_model;
    protected $__app_tenant;

    /**
     * Menanyakan apakah user ingin menambahkan versi aplikasi atau tidak.
     * Jika user memilih 'y', maka akan dibuatkan versi aplikasi terbaru.
     * @return static
     */
    protected function askAppVersion(): self
    {
        $answer = $this->ask('Please enter the application name to be used ?');
        $this->__ask_app_version = isset($answer);
        if ($this->__ask_app_version) {
            $answer_repo = $this->confirm('Do you want create a repository ?', true);
            if ($answer_repo) {
                $slug = $this->ask('Repository slug');
                $workspace = $this->ask('Workspace', 'multi-variat-indonesia');
                $repo = $this->createRepository($workspace, $slug);
                $this->line('✔️ Repository created successfully');
            }
            $this->__ask_app = $this->AppModel()->firstOrCreate(['name' => $answer]);

            $prefix = config('tenancy.database.prefix');
            $this->__app_tenant = $this->__ask_app->tenant()->create([
                'name'       => $this->__ask_app->name,
                'flag'       => $this->TenantModel()::FLAG_APP_TENANT
            ]);
            $tenant = &$this->__app_tenant;

            $module_version_app = config('module-version.application');

            $namespace = \class_name_builder($answer);
            $tenant->path        = $module_version_app['path'] . '/' . $namespace;
            $tenant->provider    = $module_version_app['namespace'] . '\\' . $namespace . '\\' . $namespace . 'ServiceProvider';
            $tenant->with_source = $this->isNeedSource();
            $tenant->config_path = $this->withSource() . '/' . $module_version_app['generate']['config']['path'] . '/config.php';
            if (isset($repo)) $tenant->vcs_remote = $repo['links']['clone'][0]['href'];
            $tenant->save();
        }
        return $this;
    }

    protected function callProvider()
    {
        Artisan::call("micro:make-provider", [
            "package-name" => $this->getStaticPackageNameResult()
        ]);
    }

    protected function callInterface()
    {
        Artisan::call('micro:make-interface', [
            "package-name" => $this->getStaticPackageNameResult(),
            "--name"       => $this->getStaticPackageNameResult()
        ]);
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

    /**
     * Generate Class
     *
     * This method will generate the class in the package path with the name
     * `{{LOWER_CLASS_NAME}}.php`.
     *
     * The class will be generated with the following properties:
     *
     * - `NAMESPACE`: The namespace of the class.
     * - `CLASS_NAME`: The name of the class.
     * - `LOWER_CLASS_NAME`: The lower case of the package name.
     * - `CONTRACT_PATH`: The path of the contracts.
     * - `SUPPORT_PATH`: The path of the supports.
     * - `SERVICE_NAME`: The name of the service.
     */
    protected function generateClass(): void
    {
        $this->cardLine('Generating Classes', function () {
            Stub::init($this->getClassStubPath(), [
                'ID'                => function () {
                    return $this->__tenant_model->getKey() ?? $this->__ask_central_tenant->getKey() ?? $this->__app_tenant->getKey();
                },
                'NAMESPACE'         => $this->generateNamespace(),
                'CLASS_NAME'        => $this->generateClassName(),
                'LOWER_CLASS_NAME'  => $this->lowerPackageName(),
                'CONTRACT_PATH'     => $this->contractsGeneratorPath(),
                'SUPPORT_PATH'      => $this->supportsGeneratorPath(),
                'SERVICE_NAME'      => $this->getStaticServiceNameResult()
            ])->saveTo($this->getGenerateLocation(), \class_basename($this->getStaticPackageNameResult()) . '.php');
        });
    }

    protected function generateComposer(): void
    {
        $this->cardLine('Generating Composer', function () {
            $path = $this->getGenerateLocation();
            if ($this->isNeedSource()) $path = \str_replace('/src', '', $path);
            $app_namespace = 'app_version';
            $requires = [];
            if ($this->isTenant()) {
                $author_package = class_name_builder(!isset($this->__tenant_model) ? $this->__ask_app->name : $this->__ask_central_tenant->name);
                $requires = [
                    [
                        "model"   => $this->__ask_app,
                        "author"  => Str::lower(config("micro-tenant.microservices.$app_namespace.namespace"))
                    ],
                    [
                        "model"   => $this->__ask_central_tenant,
                        "author"  => Str::lower(class_name_builder($this->__ask_app->name))
                    ]
                ];
            } else {
                $author_package = config("micro-tenant.microservices.$app_namespace.namespace");
            }

            Stub::init($this->getComposerStubPath(), [
                'AUTHOR'                  => Str::lower($author_package),
                'PACKAGE_NAME'            => $this->getStaticPackageNameResult(),
                'LOWER_SECOND_NAMESPACE'  => \strtolower($this->getStaticPackageNameResult()),
                'PROVIDER_PATH'           => $this->providerGeneratorPath(),
                'AUTHOR_NAME'             => 'hanafalah',
                'AUTHOR_MAIL'             => 'hamzahnafalah@gmail.com',
                'NAMESPACE'               => $author_package . '\\\\' . Str::replace('\\', '\\\\', $this->getStaticPackageNameResult()),
                'VERSION_PATH'            => $this->isNeedSource() ? 'src/' : '/',
                'REPOSITORIES'            => function () use ($path, $requires) {
                    if ($this->isTenant() && isset($this->__tenant_model) && $this->__tenant_model->flag == $this->__tenant_model::FLAG_TENANT) {
                        $fs = new Filesystem();
                        $stubs = [];
                        foreach ($requires as $key => $require) {
                            $relativePath = $fs->makePathRelative(base_path($require['model']->path), realpath($path));
                            $stubs[] = Stub::init($this->getComposerRepoStubPath(), [
                                'PACKAGE_AUTHOR' => $require['author'],
                                'PACKAGE_NAME'   => \strtolower($require['model']->name),
                                'PACKAGE_PATH'   => $relativePath
                            ]);
                        }
                        return implode(",\n\t\t", $stubs);
                    } else {
                        return '';
                    }
                },
                'REQUIRE_DEV' => function () use ($requires) {
                    if ($this->isTenant()) {
                        $content = (isset($this->__tenant_model) && $this->__tenant_model->flag == $this->__tenant_model::FLAG_TENANT)
                            ? '"' . $requires[1]['author'] . '/' . \strtolower($this->__ask_central_tenant->name) . '" : "1.x"'
                            : '"' . $requires[0]['author'] . '/' . \strtolower($requires[0]['model']->name) . '" : "1.x"';

                        return Stub::init($this->getComposerRequireStubPath(), [
                            'CONTENT' => $content
                        ]);
                    } else {
                        return '';
                    }
                }
            ])->saveTo($path, 'composer.json');
        });
    }
}
