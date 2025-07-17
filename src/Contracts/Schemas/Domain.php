<?php

namespace Hanafalah\MicroTenant\Contracts\Schemas;

use Hanafalah\LaravelSupport\Contracts\Supports\DataManagement;

/**
 * @see \Hanafalah\MicroTenant\Schemas\Domain
 * @method self setParamLogic(string $logic, bool $search_value = false, ?array $optionals = [])
 * @method self conditionals(mixed $conditionals)
 * @method bool deleteDomain()
 * @method bool prepareDeleteDomain(? array $attributes = null)
 * @method mixed getDomain()
 * @method ?Model prepareShowDomain(?Model $model = null, ?array $attributes = null)
 * @method array showDomain(?Model $model = null)
 * @method Collection prepareViewDomainList()
 * @method array viewDomainList()
 * @method LengthAwarePaginator prepareViewDomainPaginate(PaginateData $paginate_dto)
 * @method array viewDomainPaginate(?PaginateData $paginate_dto = null)
 * @method array storeDomain(?DomainData $domain_dto = null)
 * @method Builder domain(mixed $conditionals = null)
 */
interface Domain extends DataManagement{}