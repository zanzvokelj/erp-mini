<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class EnsureAllowedAdminUser
{
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();

        $isAuthorized = $request->expectsJson()
            ? $user?->canAccessApi()
            : $user?->canAccessApp();

        if (! $user || ! $isAuthorized) {

            // API request
            if ($request->expectsJson()) {
                abort(403, 'Forbidden.');
            }

            // WEB request
            return redirect()
                ->route('login')
                ->withErrors([
                    'email' => 'This account is not allowed to access the application.',
                ]);
        }

        return $next($request);
    }
}
