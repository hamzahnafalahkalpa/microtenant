<?php

namespace Hanafalah\MicroTenant\Supports;

use Hanafalah\LaravelSupport\Concerns\Support\HasCache;
use Hanafalah\MicroTenant\Contracts\Supports\ServiceCache as SupportsServiceCache;
use Illuminate\Support\Facades\Artisan;

class ServiceCache implements SupportsServiceCache{
    use HasCache;

    protected $__cache_data = [
        'microtenant' => [
            'name'    => 'app-microtenant',
            'tags'    => ['microtenant','app-microtenant'],
            'forever' => true
        ]
    ];

    public function handle(?array $cache_data = null): void{
        $cache_data ??= $this->__cache_data['microtenant'];
        $this->setCache($cache_data, function(){
            $cache = [
                'app.cached_lists' => [
                    'app.contracts',
                    'database.models',
                    'config-cache'
                ],
                'app.contracts'         => config('app.contracts'),
                'database.models'       => config('database.models')
            ];
            return $cache;
        }, false);
    }   

    public function getConfigCache(): ?array{
        $cache_data = $this->__cache_data['microtenant'];
        $cache = $this->getCache($cache_data['name'],$cache_data['tags']);
        if (isset($cache)){
            config([
                'app.cached_lists' => $cache['app.cached_lists'] ?? [],
                'app.contracts'    => $cache['app.contracts'] ?? [],
                'database.models'  => $cache['database.models'] ?? []
            ]);
        }
        return $cache;
    }
}