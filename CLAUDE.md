# CLAUDE.md - MicroTenant Package

## CRITICAL WARNINGS

**This is the multi-tenancy engine used by ALL modules in the Wellmed system.**

1. **NEVER modify the tenant switching logic without full understanding** - Incorrect changes can cause data leakage between tenants
2. **NEVER store tenant-specific data in static properties** - Octane keeps the application in memory between requests
3. **ALWAYS test tenant isolation** - Changes here affect every single request in the system
4. **This package has static state that MUST be flushed** - See Octane Integration section below

## Package Overview

**Name:** `hanafalah/microtenant`
**Namespace:** `Hanafalah\MicroTenant`
**Purpose:** Multi-tenant management with versioning support, built on top of `stancl/tenancy`

This package provides:
- Hierarchical tenant structure (APP -> CENTRAL_TENANT -> TENANT)
- Dynamic database/schema switching per tenant
- Tenant impersonation for command-line operations
- Service provider registration per tenant
- Cluster schema management for yearly data partitioning
- Integration with Laravel Octane for high-performance multi-tenancy

## Dependencies

```json
{
    "stancl/tenancy": "^3.8",
    "hanafalah/laravel-support": "dev-main",
    "hanafalah/api-helper": "dev-main",
    "hanafalah/laravel-feature": "dev-main",
    "hanafalah/module-user": "dev-main",
    "hanafalah/module-workspace": "dev-main",
    "hanafalah/laravel-permission": "dev-main"
}
```

## Directory Structure

```
src/
├── Commands/
│   ├── Impersonate/
│   │   ├── Concerns/
│   │   │   └── HasImpersonate.php       # Shared impersonate logic
│   │   ├── ImpersonateCacheCommand.php  # Cache tenant context
│   │   └── ImpersonateMigrateCommand.php # Run migrations per tenant
│   ├── AddPackageCommand.php
│   ├── AddTenantCommand.php
│   ├── EnvironmentCommand.php
│   └── InstallMakeCommand.php
├── Concerns/
│   ├── Commands/
│   │   └── HasGeneratorAction.php
│   ├── Models/
│   │   ├── CentralAppConnection.php     # APP-level connection trait
│   │   ├── CentralConnection.php        # Central database connection
│   │   ├── CentralTenantConnection.php  # CENTRAL_TENANT connection
│   │   ├── Connection.php               # Base connection trait
│   │   ├── HasTenantValidation.php
│   │   └── TenantConnection.php         # TENANT-level connection
│   ├── Providers/
│   │   ├── HasImpersonate.php
│   │   ├── HasOverrider.php             # Config override logic
│   │   └── HasProviderInjection.php
│   ├── Tenant/
│   │   ├── HasCentralTenant.php
│   │   ├── HasTenant.php
│   │   └── NowYouSeeMe.php
│   └── HasDatabaseDriver.php            # Database driver abstraction
├── Contracts/
│   ├── Data/
│   │   ├── DomainData.php
│   │   └── TenantData.php
│   ├── Schemas/
│   │   ├── Domain.php
│   │   └── Tenant.php
│   ├── Supports/
│   │   ├── ConnectionManager.php
│   │   └── ServiceCache.php
│   └── MicroTenant.php                  # Main contract interface
├── Data/
│   ├── DomainData.php
│   └── TenantData.php
├── Facades/
│   └── MicroTenant.php                  # MicroTenant facade
├── Jobs/
│   └── GenerateClusterSchemas.php       # Async cluster schema generation
├── Listeners/
│   └── BootstrapTenancy.php             # Tenancy initialization listener
├── Middleware/
│   └── SetWorkspaceTimezone.php
├── Middlewares/
│   └── MicroAccess.php                  # Request authentication middleware
├── Models/
│   ├── Activity/
│   │   ├── CentralActivity.php
│   │   └── CentralActivityStatus.php
│   ├── ApiAccess/
│   │   └── PersonalAccessToken.php
│   ├── LogHistory/
│   │   └── CentralLogHistory.php
│   ├── Relation/
│   │   └── CentralModelHasRelation.php
│   ├── Tenant/
│   │   ├── CentralTenant.php
│   │   ├── Domain.php
│   │   └── Tenant.php                   # Main Tenant model
│   ├── BaseModel.php                    # Base model with tenant awareness
│   └── BaseModelTenant.php
├── Observers/
│   └── TenantObserver.php
├── Providers/
│   ├── BaseServiceProvider.php
│   └── CommandServiceProvider.php
├── Resources/
│   ├── Domain/
│   │   ├── ShowDomain.php
│   │   └── ViewDomain.php
│   └── Tenant/
│       ├── ShowTenant.php
│       └── ViewTenant.php
├── Schemas/
│   ├── Domain.php
│   └── Tenant.php
├── Scopes/
│   ├── UseTenantValidation.php          # Auto-filter by tenant_id
│   └── WithoutTenantValidation.php
├── Supports/
│   ├── ConnectionManager.php            # Database connection switching
│   ├── PackageManagement.php
│   └── ServiceCache.php                 # Cache tenant service config
├── helper.php                           # Global helper functions
├── MicroServiceProvider.php             # Base service provider
├── MicroTenant.php                      # Main class with tenant logic
└── MicroTenantServiceProvider.php       # Package service provider
```

## Key Classes and Their Purposes

### MicroTenant.php (Main Class)

The core class handling tenant impersonation and context switching.

**Critical Static Properties (Memory Leak Risk):**
```php
public static $microtenant;              // Current tenant context - MUST be cleared
protected static array $registeredProviders = [];  // Cached providers - OK to keep
protected static array $loadedAutoloaders = [];    // Cached autoloaders - OK to keep
public static $with_database_name = false;
```

**Key Methods:**
- `impersonate(Model $tenant, ?bool $recurse = true, ?bool $initTenancy = true)` - Switch to tenant context
- `tenantImpersonate($tenant = null)` - Full tenant switch with database/schema setup
- `accessOnLogin(?string $token = null)` - Handle login with tenant context
- `onLogin(ModuleApiAccess $api_access)` - Process login and set tenant
- `onLogout(?callable $callback)` - Clear tenant state on logout
- `flushStaticCaches()` - Reset static state for Octane worker recycling

### Tenant Model Flags

```php
const FLAG_APP_TENANT     = 'APP';            // Application level
const FLAG_CENTRAL_TENANT = 'CENTRAL_TENANT'; // Group/central level
const FLAG_TENANT         = 'TENANT';         // Individual tenant
const FLAG_CLUSTER        = 'CLUSTER';        // Cluster level
```

### Connection Traits

Use these traits in your models to set the correct database connection:

- `CentralConnection` - For central database models
- `CentralAppConnection` - For APP-level tenant models
- `CentralTenantConnection` - For CENTRAL_TENANT-level models
- `TenantConnection` - For TENANT-level models

## Configuration

Configuration file: `assets/config/config.php` (published as `config/micro-tenant.php`)

**Key Configuration Sections:**

```php
return [
    'enabled' => true,
    'dev_mode' => false,
    'direct_provider_access' => false,

    // Database naming conventions
    'database' => [
        'database_tenant_name' => [
            'prefix' => 'microtenant_',
            'suffix' => ''
        ],
        'model_connections' => [
            'central' => [...],
            'central_app' => [...],
            'central_tenant' => [...]
        ],
        'managers' => [
            'pgsql' => PostgreSQLDatabaseManager::class,
            'mysql' => PermissionControlledMySQLDatabaseManager::class,
        ]
    ],

    // Tenancy bootstrappers
    'tenancy' => [
        'bootstrappers' => [
            DatabaseTenancyBootstrapper::class,
            CacheTenancyBootstrapper::class,
            FilesystemTenancyBootstrapper::class,
            QueueTenancyBootstrapper::class,
        ]
    ],

    // Job configuration for cluster generation
    'jobs' => [
        'cluster_generation' => [
            'enabled' => env('MICROTENANT_CLUSTER_JOB_ENABLED', true),
            'queue' => 'installation',
            'connection' => 'rabbitmq',
        ]
    ]
];
```

## Common Usage Patterns

### Getting Current Tenant

```php
// Via helper function
$tenant = microtenant();

// Via facade
use Hanafalah\MicroTenant\Facades\MicroTenant;
$tenant = MicroTenant::getMicroTenant();

// Via tenancy (stancl/tenancy)
$tenant = tenancy()->tenant;
```

### Impersonating a Tenant (CLI)

```php
use Hanafalah\MicroTenant\Facades\MicroTenant;

// Switch to tenant context
MicroTenant::tenantImpersonate($tenantId);

// Or with model
$tenant = Tenant::find($tenantId);
MicroTenant::impersonate($tenant);
```

### Using Connection Traits in Models

```php
use Hanafalah\MicroTenant\Concerns\Models\TenantConnection;

class Visit extends BaseModel
{
    use TenantConnection;  // Model uses 'tenant' connection
}
```

### Middleware for Tenant Authentication

```php
// In routes
Route::middleware(['micro-access'])->group(function () {
    // Tenant-aware routes
});
```

## Octane Integration (CRITICAL)

### Static State That Must Be Flushed

The `MicroTenant` class has static properties that persist between Octane requests:

```php
// In MicroTenant.php
public static $microtenant;  // MUST be cleared per-request
protected static array $registeredProviders = [];  // Can be cached for performance
protected static array $loadedAutoloaders = [];    // Can be cached for performance
```

### Flushing State

The `FlushTenantState` listener in the main application handles this:

```php
// app/Listeners/Octane/FlushTenantState.php
protected function clearMicroTenantState(): void
{
    if (class_exists(\Hanafalah\MicroTenant\MicroTenant::class)) {
        \Hanafalah\MicroTenant\MicroTenant::$microtenant = null;
    }
}
```

The package also provides a static method for full cache reset:

```php
MicroTenant::flushStaticCaches();  // Clears ALL static caches
```

**When to use each:**
- `$microtenant = null` - Per-request cleanup (fast, keeps provider cache)
- `flushStaticCaches()` - Full reset (use when worker is recycled)

### Octane Configuration

In `config/octane.php`, ensure MicroTenant is listed for flushing:

```php
'flush' => [
    \Hanafalah\MicroTenant\MicroTenant::class,
    // ...
],
```

## Database Architecture

### Tenant Hierarchy

```
APP (flag='APP')
  └── Uses schema 'app_X' in central database
      └── CENTRAL_TENANT (flag='CENTRAL_TENANT')
          └── Uses schema 'group_X' in central database
              └── TENANT (flag='TENANT')
                  └── Uses SEPARATE database 'clinic_X'
                      └── With yearly schemas: emr_2026, pos_2026, scm_2026
```

### Connection Names

| Flag | Connection Name | Database Location |
|------|----------------|-------------------|
| APP | `central_app` | Schema in central DB |
| CENTRAL_TENANT | `central_tenant` | Schema in central DB |
| TENANT | `tenant` | Separate database |

### Cluster Schemas (Yearly Partitioning)

Tenant databases can have yearly schemas for data partitioning:
- `emr_2026` - Electronic Medical Records
- `pos_2026` - Point of Sale
- `scm_2026` - Supply Chain Management

These are created asynchronously via `GenerateClusterSchemas` job.

## Artisan Commands

```bash
# Impersonate and cache tenant context
php artisan impersonate:cache --app_id=2 --group_id=3 --tenant_id=4

# Run migrations for tenant
php artisan impersonate:migrate --app_id=2 --group_id=3 --tenant_id=4

# Clear impersonate cache
php artisan impersonate:cache --forget

# Add new tenant
php artisan microtenant:add-tenant

# Add package to tenant
php artisan microtenant:add-package
```

## Helper Functions

```php
// Get MicroTenant instance
microtenant()

// Get tenant-specific path
tenant_path('folder/file.php')

// Get repository path
repository_path()

// Get app version path
app_version_path('folder')
```

## Common Pitfalls

1. **Forgetting to initialize tenancy** - Always call `tenantImpersonate()` before tenant operations in CLI
2. **Static state in Octane** - Never store per-request data in static properties
3. **Connection confusion** - Use the correct connection trait for your model's tenant level
4. **Missing parent relations** - When loading tenants, use `with('parent.parent')` for full hierarchy
5. **Cluster schema timing** - Cluster schemas are created asynchronously; don't assume immediate availability

## Debugging

### Check Current Tenant

```php
dd(tenancy()->tenant);
dd(MicroTenant::getMicroTenant());
```

### Check Connection Configuration

```php
dd(config('database.connections.tenant'));
dd(config('database.connections.central_app'));
```

### Enable Profiling

Set in config or .env:
```php
'profiling' => [
    'enabled' => true,
]
```

This logs timing information for impersonation operations.

## Related Documentation

- Main project: `/var/www/projects/wellmed/CLAUDE.md`
- Octane fixes: `/var/www/projects/wellmed/OCTANE_FIXES.md`
- Octane tenant isolation: `/var/www/projects/wellmed/OCTANE_TENANT_ISOLATION.md`
- Laravel Support package: `/var/www/projects/wellmed/repositories/laravel-support/CLAUDE.md`
