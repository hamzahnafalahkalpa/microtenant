<?php

namespace Hanafalah\MicroTenant\Middlewares;

use Closure;
use Hanafalah\ApiHelper\Facades\ApiAccess;
use Hanafalah\MicroTenant\Facades\MicroTenant;
use Illuminate\Support\Facades\Auth;

class MicroAccess
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle($request, Closure $next)
    {
        ApiAccess::accessOnLogin(function ($api_access) {
            MicroTenant::onLogin($api_access);
        });
        return $next($request);
    }
}
