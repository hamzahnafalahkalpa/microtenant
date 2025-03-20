<?php

namespace Hanafalah\MicroTenant\Concerns\Tenant;

use Illuminate\Support\Facades\Schema;
use Hanafalah\MicroTenant\Facades\MicroTenant;
use Hanafalah\MicroTenant\Models\Tenant\Tenant;

trait NowYouSeeMe
{
    protected $__table_name;

    public function isTableExists(callable $callback)
    {
        $this->currentTenant(function ($schema, $table_name) use ($callback) {
            $is_exist = $schema->hasTable($table_name);
            if ($is_exist) $callback();
        });
    }

    public function isNotTableExists(callable $callback)
    {
        $this->currentTenant(function ($schema, $table_name) use ($callback) {
            $is_exist = $schema->hasTable($table_name);
            if (!$is_exist) $callback();
        });
    }

    public function isColumnExists(string $column_name, callable $callback)
    {
        return $this->currentTenant(function ($schema, $table_name) use ($column_name, $callback) {
            $is_exists = $schema->hasColumn($table_name, $column_name);
            if ($is_exists) $callback($column_name);
        });
    }

    public function isNotColumnExists(string $column_name, callable $callback)
    {
        return $this->currentTenant(function ($schema, $table_name) use ($column_name, $callback) {
            $is_exists = $schema->hasColumn($table_name, $column_name);
            if (!$is_exists) $callback($column_name);
        });
    }

    private function currentTenant(callable $callback)
    {
        $this->__table_name = $this->__table->getTable();
        $tenant_model = tenancy()->tenant;
        $tenant_id = tenancy()->tenant->getKey();
        if ($tenant_model->flag != Tenant::FLAG_TENANT) {
            $current_tenant_id = MicroTenant::getMicroTenant()->tenant->model->id;
            tenancy()->initialize($current_tenant_id);
        }
        $this->__table_name = $this->__table->getTable();
        $schema    = Schema::connection($this->__table->getConnectionName());
        $callback($schema, explode('.', $this->__table_name)[1] ?? $this->__table_name);
        tenancy()->initialize($tenant_id);
    }
}
