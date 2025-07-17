<?php

namespace Hanafalah\MicroTenant\Contracts\Schemas;

use Hanafalah\LaravelSupport\Contracts\Supports\DataManagement;

/**
 * @see \Hanafalah\MicroTenant\Schemas\Tenant
 * @method self setParamLogic(string $logic, bool $search_value = false, ?array $optionals = [])
 * @method self conditionals(mixed $conditionals)
 * @method bool deleteTenant()
 * @method bool prepareDeleteTenant(? array $attributes = null)
 * @method mixed getTenant()
 * @method ?Model prepareShowTenant(?Model $model = null, ?array $attributes = null)
 * @method array showTenant(?Model $model = null)
 * @method Collection prepareViewTenantList()
 * @method array viewTenantList()
 * @method LengthAwarePaginator prepareViewTenantPaginate(PaginateData $paginate_dto)
 * @method array viewTenantPaginate(?PaginateData $paginate_dto = null)
 * @method array storeTenant(?TenantData $tenant_dto = null)
 * @method Builder tenant(mixed $conditionals = null)
 */
interface Tenant extends DataManagement{}