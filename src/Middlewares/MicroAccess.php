<?php

namespace Zahzah\MicroTenant\Middlewares;

use Closure;
use Zahzah\ApiHelper\Facades\ApiAccess;

class MicroAccess {
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle($request, Closure $next)
    { 
        $api_access = ApiAccess::getApiAccess();
        if (isset($api_access)){
            return $next($request);
        }
    }
}