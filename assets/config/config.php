<?php

use Hanafalah\MicroTenant\Models as MicroTenantModels;
use Hanafalah\MicroTenant\Supports\FileActivator;
use Hanafalah\MicroTenant\Commands as Commands;

return [
    'dev_mode' => true,
    'microservices' => [
        'tenant'   => [
            'namespace'  => env('MICROTENANT_TENANT_NAMESPACE', 'Tenants'),
            'path'       => env('MICROTENANT_TENANT_PATH', 'Tenants'),
            'generate' => [
                'migration'       => ['path' => 'Database/Migrations', 'generate' => true],
                'model'           => ['path' => 'Models', 'generate' => true],
                'controller'      => ['path' => 'Controllers', 'generate' => true],
                'provider'        => ['path' => 'Providers', 'generate' => true],
                'config'          => ['path' => 'Config', 'generate' => true],
                'contracts'       => ['path' => 'Contracts', 'generate' => true],
                'concerns'        => ['path' => 'Concerns', 'generate' => true],
                'command'         => ['path' => 'Commands', 'generate' => true],
                'routes'          => ['path' => 'Routes', 'generate' => true],
                'event'           => ['path' => 'Events', 'generate' => true],
                'observer'        => ['path' => 'Observers', 'generate' => true],
                'policies'        => ['path' => 'Policies', 'generate' => true],
                'jobs'            => ['path' => 'Jobs', 'generate' => false],
                'resource'        => ['path' => 'Transformers', 'generate' => false],
                'seeder'          => ['path' => 'Database/Seeders', 'generate' => true],
                'middleware'      => ['path' => 'Middleware', 'generate' => true],
                'request'         => ['path' => 'Requests', 'generate' => true],
                'assets'          => ['path' => 'Resources/assets', 'generate' => false],
                'supports'        => ['path' => 'Supports', 'generate' => true],
                'views'           => ['path' => 'Resources/views', 'generate' => true],
                'schemas'         => ['path' => 'Schemas', 'generate' => true],
                'facades'         => ['path' => 'Facades', 'generate' => true],
                'ignore'          => ['path' => '', 'generate' => true]
            ]
        ],
        'app_version'   => [
            /**
             * pattern for versioning, you can use 1.^, 1.0.^, 1.0.0, 
             * but avoid using 1.0.0 because it will make schema installation become not optimal
             */
            'version_pattern' => '1.^',
            'namespace'    => env('MICROTENANT_APP_NAMESPACE', 'Projects'),
            'path'         => env('MICROTENANT_APP_PATH', 'app/Projects'),
            'generate'     => [
                'migration'       => ['path' => 'Database/Migrations', 'generate' => true],
                'model'           => ['path' => 'Models', 'generate' => true],
                'controller'      => ['path' => 'Controllers', 'generate' => true],
                'provider'        => ['path' => 'Providers', 'generate' => true],
                'config'          => ['path' => 'Config', 'generate' => true],
                'contracts'       => ['path' => 'Contracts', 'generate' => true],
                'concerns'        => ['path' => 'Concerns', 'generate' => true],
                'command'         => ['path' => 'Commands', 'generate' => true],
                'routes'          => ['path' => 'Routes', 'generate' => true],
                'event'           => ['path' => 'Events', 'generate' => false],
                'observer'        => ['path' => 'Observers', 'generate' => true],
                'policies'        => ['path' => 'Policies', 'generate' => true],
                'jobs'            => ['path' => 'Jobs', 'generate' => false],
                'resource'        => ['path' => 'Transformers', 'generate' => false],
                'seeder'          => ['path' => 'Database/Seeders', 'generate' => true],
                'middleware'      => ['path' => 'Middleware', 'generate' => true],
                'request'         => ['path' => 'Requests', 'generate' => true],
                'assets'          => ['path' => 'Resources/assets', 'generate' => true],
                'supports'        => ['path' => 'Supports', 'generate' => true],
                'views'           => ['path' => 'Resources/views', 'generate' => true],
                'schemas'         => ['path' => 'Schemas', 'generate' => true],
                'facades'         => ['path' => 'Facades', 'generate' => true],
                'ignore'          => ['path' => '', 'generate' => true]
            ]
        ]
        //ADD OTHER SERVICES, WE NEED MAIN SERVICE TENANT, MODULE, AND APP VERSION ONLY
    ],
    'login_schema' => null,
    'application'  => [
        /**
         * pattern for versioning, you can use 1.^, 1.0.^, 1.0.0, 
         * but avoid using 1.0.0 because it will make schema installation become not optimal
         */
        'version_pattern' => '1.^'
    ],
    'libs' => [
        'model' => 'Models',
        'contract' => 'Contracts'
    ],
    'database' => [
        'central_tenant'   => [
            'prefix' => 'central_tenant_',
            'suffix' => ''
        ],
        'scope'     => [
            'paths' => [
                'App/Scopes'
            ]
        ],
        'models'  => [
            'App'                         => MicroTenantModels\Application\App::class,
            'ApiAccess'                   => MicroTenantModels\ApiAccess\ApiAccess::class,
            'Schema'                      => MicroTenantModels\Schema\Schema::class,
            'InstallationSchema'          => MicroTenantModels\Schema\InstallationSchema::class,
            'Domain'                      => MicroTenantModels\Tenant\Domain::class,
            'CentralTenant'               => MicroTenantModels\Tenant\CentralTenant::class,
            'Tenant'                      => MicroTenantModels\Tenant\Tenant::class,
            'CentralActivity'             => MicroTenantModels\Activity\CentralActivity::class,
            'CentralActivityStatus'       => MicroTenantModels\Activity\CentralActivityStatus::class,
            'CentralLogHistory'           => MicroTenantModels\LogHistory\CentralLogHistory::class,
            'CentralModelHasRelation'     => MicroTenantModels\Relation\CentralModelHasRelation::class,
            'Domain'                      => MicroTenantModels\Tenant\Domain::class,
            'MasterFeature'               => MicroTenantModels\Feature\MasterFeature::class,
            'ModelHasFeature'             => MicroTenantModels\Feature\ModelHasFeature::class,
            'ModelHasVersion'             => MicroTenantModels\Version\ModelHasVersion::class,
            'ModelHasApp'                 => MicroTenantModels\Application\ModelHasApp::class,
            'PayloadMonitoring'           => MicroTenantModels\PayloadMonitoring\PayloadMonitoring::class,
            'User'                        => MicroTenantModels\User\User::class,
            'UserReference'               => MicroTenantModels\User\UserReference::class,
            'PersonalAccessToken'         => MicroTenantModels\ApiAccess\PersonalAccessToken::class,
            'Workspace'                   => MicroTenantModels\Workspace\Workspace::class,
            'CentralAddress'              => MicroTenantModels\Regional\Address::class
        ],
        'connections' => [
            //THIS SETUP DEFAULT FOR MYSQL
            'central_connection' => [
                'driver'         => env('DB_CENTRAL_DRIVER', 'mysql'),
                'url'            => env('DB_CENTRAL_URL'),
                'host'           => env('DB_CENTRAL_HOST', '127.0.0.1'),
                'port'           => env('DB_CENTRAL_PORT', '3306'),
                'database'       => env('DB_CENTRAL_DATABASE', 'central_database'),
                'username'       => env('DB_CENTRAL_USERNAME', 'root'),
                'password'       => env('DB_CENTRAL_PASSWORD', ''),
                'unix_socket'    => env('DB_CENTRAL_SOCKET', ''),
                'charset'        => env('DB_CENTRAL_CHARSET', 'utf8mb4'),
                'collation'      => env('DB_CENTRAL_COLLATION', 'utf8mb4_unicode_ci'),
                'prefix'         => '',
                'prefix_indexes' => true,
                'strict'         => true,
                'engine'         => null,
                'options'        => extension_loaded('pdo_mysql') ? array_filter([
                    PDO::MYSQL_ATTR_SSL_CA => env('MYSQL_ATTR_SSL_CA'),
                ]) : [],
            ],

            /**
             * Connection used as a "template" for the dynamically created tenant database connection.
             * Note: don't name your template connection tenant. That name is reserved by package.
             */
            'template_tenant_connection' => null,

        ],
        'database_tenant_name' => [
            'prefix' => 'microtenant_',
            'suffix' => ''
        ],

        /**
         * TenantDatabaseManagers are classes that handle the creation & deletion of tenant databases.
         */
        'managers' => [
            'sqlite' => Stancl\Tenancy\TenantDatabaseManagers\SQLiteDatabaseManager::class,
            // 'mysql' => Stancl\Tenancy\TenantDatabaseManagers\MySQLDatabaseManager::class,
            'pgsql'  => Stancl\Tenancy\TenantDatabaseManagers\PostgreSQLDatabaseManager::class,

            /**
             * Use this database manager for MySQL to have a DB user created for each tenant database.
             * You can customize the grants given to these users by changing the $grants property.
             */
            'mysql' => Stancl\Tenancy\TenantDatabaseManagers\PermissionControlledMySQLDatabaseManager::class,

            /**
         * Disable the pgsql manager above, and enable the one below if you
         * want to separate tenant DBs by schemas rather than databases.
         */
            // 'pgsql' => Stancl\Tenancy\TenantDatabaseManagers\PostgreSQLSchemaManager::class, // Separate by schema instead of database
        ]
    ],
    'domains'  => [
        /**
         * Only relevant if you're using the domain or subdomain identification middleware.
         */
        'central_domains' => [
            '127.0.0.1',
            'localhost',
        ],
        'central_tenants' => []
    ],
    'commands' => [
        Commands\AddApplicationMakeCommand::class,
        Commands\InstallMakeCommand::class,
        Commands\ApiHelperInstallMakeCommand::class,
        Commands\InterfaceMakeCommand::class,
        Commands\TenantMakeCommand::class,
        Commands\AddInstallationSchemaMakeCommand::class,
        Commands\AddModelSchemaMakeCommand::class,
        Commands\RunSchemaMakeCommand::class,
        Commands\ProviderMakeCommand::class,
        Commands\InstallImpersonateMakeCommand::class,
        Commands\AddSchemaMakeCommand::class,
        Commands\Impersonate\ImpersonateCommand::class,
        Commands\Impersonate\MigrationMakeCommand::class,
        Commands\Impersonate\ModelMakeCommand::class,
        Commands\Impersonate\ControllerMakeCommand::class,
        Commands\Impersonate\RequestMakeCommand::class,
        Commands\Impersonate\ConcernMakeCommand::class,
        Commands\Impersonate\MiddlewareMakeCommand::class,
        Commands\Impersonate\PolicyMakeCommand::class,
        Commands\Impersonate\ResourceMakeCommand::class,
        Commands\Impersonate\ImpersonatePublishCommand::class,
        Commands\Impersonate\ImpersonateAddProviderCommand::class,
        Commands\Impersonate\ImpersonateMigrateCommand::class,
        Commands\Impersonate\ImpersonateSeedCommand::class
    ],
    'payload_monitoring' => [
        'enabled'     => true,
        'categories'  => [
            'slow'    => 1000, // in miliseconds
            'medium'  => 500,
            'fast'    => 100
        ]
    ],
    /**
     * The list of packages will be added when the system is run, based on the installed features related to the tenant.
     */
    'package_list' => [],
    'tenancy' => [
        /**
         * Tenancy bootstrappers are executed when tenancy is initialized.
         * Their responsibility is making Laravel features tenant-aware.
         *
         * To configure their behavior, see the config keys below.
         */
        'bootstrappers' => [
            Stancl\Tenancy\Bootstrappers\DatabaseTenancyBootstrapper::class,
            Stancl\Tenancy\Bootstrappers\CacheTenancyBootstrapper::class,
            Stancl\Tenancy\Bootstrappers\FilesystemTenancyBootstrapper::class,
            Stancl\Tenancy\Bootstrappers\QueueTenancyBootstrapper::class,
            // Stancl\Tenancy\Bootstrappers\RedisTenancyBootstrapper::class, // Note: phpredis is needed
        ],
        /**
         * Cache tenancy config. Used by CacheTenancyBootstrapper.
         *
         * This works for all Cache facade calls, cache() helper
         * calls and direct calls to injected cache stores.
         *
         * Each key in cache will have a tag applied on it. This tag is used to
         * scope the cache both when writing to it and when reading from it.
         *
         * You can clear cache selectively by specifying the tag.
         */
        'cache' => [
            'authorization' => [
                'prefix' => 'microtenant_'
            ],
            'tag_base' => 'microtenant', // This tag_base, followed by the tenant_id, will form a tag that will be applied on each cache call.
        ],

        /**
         * Filesystem tenancy config. Used by FilesystemTenancyBootstrapper.
         * https://tenancyforlaravel.com/docs/v3/tenancy-bootstrappers/#filesystem-tenancy-boostrapper.
         */
        'filesystem' => [
            /**
             * Each disk listed in the 'disks' array will be suffixed by the suffix_base, followed by the tenant_id.
             */
            'suffix_base' => 'microtenant',
            'disks' => [
                'local',
                'public',
                // 's3',
            ],

            /**
             * Use this for local disks.
             *
             * See https://tenancyforlaravel.com/docs/v3/tenancy-bootstrappers/#filesystem-tenancy-boostrapper
             */
            'root_override' => [
                // Disks whose roots should be overridden after storage_path() is suffixed.
                'local' => '%storage_path%/app/',
                'public' => '%storage_path%/app/public/',
            ],

            /**
             * Should storage_path() be suffixed.
             *
             * Note: Disabling this will likely break local disk tenancy. Only disable this if you're using an external file storage service like S3.
             *
             * For the vast majority of applications, this feature should be enabled. But in some
             * edge cases, it can cause issues (like using Passport with Vapor - see #196), so
             * you may want to disable this if you are experiencing these edge case issues.
             */
            'suffix_storage_path' => true,

            /**
             * By default, asset() calls are made multi-tenant too. You can use global_asset() and mix()
             * for global, non-tenant-specific assets. However, you might have some issues when using
             * packages that use asset() calls inside the tenant app. To avoid such issues, you can
             * disable asset() helper tenancy and explicitly use tenant_asset() calls in places
             * where you want to use tenant-specific assets (product images, avatars, etc).
             */
            'asset_helper_tenancy' => true,
        ],

        /**
         * Redis tenancy config. Used by RedisTenancyBootstrapper.
         *
         * Note: You need phpredis to use Redis tenancy.
         *
         * Note: You don't need to use this if you're using Redis only for cache.
         * Redis tenancy is only relevant if you're making direct Redis calls,
         * either using the Redis facade or by injecting it as a dependency.
         */
        'redis' => [
            'prefix_base' => 'microtenant', // Each key in Redis will be prepended by this prefix_base, followed by the tenant id.
            'prefixed_connections' => [ // Redis connections whose keys are prefixed, to separate one tenant's keys from another.
                // 'default',
            ],
        ],

        /**
         * Features are classes that provide additional functionality
         * not needed for tenancy to be bootstrapped. They are run
         * regardless of whether tenancy has been initialized.
         *
         * See the documentation page for each class to
         * understand which ones you want to enable.
         */
        'features' => [
            // Stancl\Tenancy\Features\UserImpersonation::class,
            // Stancl\Tenancy\Features\TelescopeTags::class,
            // Stancl\Tenancy\Features\UniversalRoutes::class,
            // Stancl\Tenancy\Features\TenantConfig::class, // https://tenancyforlaravel.com/docs/v3/features/tenant-config
            // Stancl\Tenancy\Features\CrossDomainRedirect::class, // https://tenancyforlaravel.com/docs/v3/features/cross-domain-redirect
            // Stancl\Tenancy\Features\ViteBundler::class,
        ],

        /**
         * Should tenancy routes be registered.
         *
         * Tenancy routes include tenant asset routes. By default, this route is
         * enabled. But it may be useful to disable them if you use external
         * storage (e.g. S3 / Dropbox) or have a custom asset controller.
         */
        'routes' => true,

        'migration_parameters' => [
            '--force' => true, // This needs to be true to run migrations in production.
            '--path' => [
                //Some migrations will be automatically read based on the app, tenant, and module version managed by microtenant
                database_path('migrations/tenant'),
            ],
            '--realpath' => true,
        ],

        'seeder_parameters' => [
            '--class' => 'DatabaseSeeder', // root seeder class
            // '--force' => true,
        ],
    ]
];
