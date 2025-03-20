<?php

namespace Zahzah\MicroTenant\Commands\Impersonate;

use Zahzah\LaravelSupport\Commands\EnvironmentCommand as SupportEnvironmentCommand;
use Zahzah\LaravelSupport\Concerns\Support\HasArray;
use Zahzah\LaravelSupport\Concerns\Support\HasCache;
use Zahzah\MicroTenant\Commands\Impersonate\Concern\generatorHelperPath;
use Zahzah\MicroTenant\Facades\MicroTenant;

class EnvironmentCommand extends SupportEnvironmentCommand 
{
    use generatorHelperPath, HasCache, HasArray;
    protected static $__impersonateCache, $__path, $__basePath, $__fullPath, $__impersonateOrigin;
    protected string $__choosed_impersonate; 
    protected static array $__impersonate_config;

    protected function initCacheData(): self{
      static::$__impersonate_config = MicroTenant::getCacheData("impersonate");
      return $this;
    }

    protected function getCacheData(){
        $this->initCacheData();
        static::$__impersonateCache = $this->getCache(static::$__impersonate_config['name'],static::$__impersonate_config['tags']);
        return static::$__impersonateCache;
    }

    protected function isHasCache(): bool{
      return isset(static::$__impersonateCache);
    }

    protected function forgetCacheByTags(): self{
      $this->forgetTags(static::$__impersonateCache['tags']);
      static::$__impersonateCache = null;
      return $this;
    }

    protected function replacement(string $value){
      return preg_replace('/\\\\+/', '\\', $value);
    }
      
    protected function isChenkingImpersonateApp(? string $libSegnature = null, string $option=null) : void {
      $this->getCacheData();
      if(!$this->isHasCache()) {
        $this->info("Please choose impersonate application first");
        $this->call("impersonate:cache",[
          '--app_id'    => $this->option('app_id') ?? null,
          '--group_id'  => $this->option('group_id') ?? null,
          '--tenant_id' => $this->option('tenant_id') ?? null,
        ]);
        $this->getCacheData();
      }else{
        $tenant = static::$__impersonateCache->tenant;
        if (isset($tenant->model)){
            MicroTenant::tenantImpersonate($tenant->model);
        }
      }
      if(isset($libSegnature)) $this->setPathCommand($libSegnature,$option);
    }

    protected function setPathCommand($signature,$option) : void{
      if ($this->option('app') || $option == "application")   $field = 'application';
      if ($this->option('group') || $option == "group")       $field = 'group';

      $impersonate = static::$__impersonateCache->{$field ?? 'tenant'};
      static::$__basePath = $impersonate->model->path; 

      // PADA APPLICATION PATHNYA SELALU NULL PADAHAL KETIKA DI DD ADA
      if(!isset(static::$__basePath)) {
        static::$__basePath = "app/Projects/".$impersonate->model->name;
      }

      $pathLib            = $impersonate->config['libs'][$signature];
      static::$__fullPath = static::$__basePath."/src/".$pathLib;
      static::$__fullPath = base_path() . "/" . static::$__fullPath;

      // CEK IS EXITS FOLDER
      if (!is_dir(static::$__fullPath)) mkdir(static::$__fullPath, 0777, true);
    }

    protected function getImpersonate(){
      $this->isChenkingImpersonateApp();
      if ($this->option('app'))   $field = 'application';
      if ($this->option('group')) $field = 'group';
      $this->__choosed_impersonate = $field ?? 'tenant';
      
      return static::$__impersonateCache->{$this->__choosed_impersonate};
    }

    protected function recache(): self{
      $list   = ['--app_id' => 'application','--group_id' => 'group','--tenant_id' => 'tenant'];
      $option = ['--forget'];
      foreach ($list as $key => $value) {
          $option[$key] = self::$__impersonateCache->{$value}->model->id;
      }
      $this->callSilent("impersonate:cache",$option);
      unset($option['--forget']);
      $this->callSilent("impersonate:cache",$option);
      return $this;
    }
}
