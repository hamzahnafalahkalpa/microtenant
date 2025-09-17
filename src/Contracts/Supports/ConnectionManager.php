<?php 

namespace Hanafalah\MicroTenant\Contracts\Supports;

use Illuminate\Database\Eloquent\Model;

interface ConnectionManager{
     public function handle(Model $tenant): void;
}