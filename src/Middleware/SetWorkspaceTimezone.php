<?php

namespace Hanafalah\MicroTenant\Middleware;

use Closure;
use Illuminate\Http\Request;

/**
 * Sets the application timezone per request based on workspace/tenant timezone.
 *
 * This middleware is Octane-safe as it sets timezone per request without using
 * static variables or global state that persists between requests.
 *
 * The timezone is automatically loaded from workspace settings during tenant initialization
 * by MicroTenantServiceProvider: $workspace->setting['timezone']['name']
 *
 * Priority order:
 * 1. Authenticated user's timezone preference (if user has custom timezone)
 * 2. Workspace timezone (from tenant's workspace settings)
 * 3. Application default timezone (UTC)
 */
class SetWorkspaceTimezone
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        $timezone = $this->resolveTimezone($request);

        // Set the timezone for this request context
        // This is per-request and doesn't persist in Octane workers
        date_default_timezone_set($timezone);

        // Store in request for easy access throughout request lifecycle
        $request->attributes->set('client_timezone', $timezone);

        return $next($request);
    }

    /**
     * Resolve the timezone from various sources.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return string
     */
    protected function resolveTimezone(Request $request): string
    {
        // 1. Check authenticated user's timezone preference
        if ($user = $request->user()) {
            if (isset($user->timezone) && $this->isValidTimezone($user->timezone)) {
                return $user->timezone;
            }
        }

        // 2. Check workspace timezone (set by MicroTenantServiceProvider during tenant bootstrap)
        // This comes from: $workspace->setting['timezone']['name']
        if ($timezone = config('app.client_timezone')) {
            if ($this->isValidTimezone($timezone)) {
                return $timezone;
            }
        }

        // 3. Fallback to app timezone
        return config('app.timezone', 'UTC');
    }

    /**
     * Validate if the timezone identifier is valid.
     *
     * @param  string  $timezone
     * @return bool
     */
    protected function isValidTimezone(string $timezone): bool
    {
        return in_array($timezone, timezone_identifiers_list());
    }
}
