<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class EnsureAllowedAdminUser
{
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();

        if (! $user || $user->hasAllowedAdminAccess()) {
            return $next($request);
        }

        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        if ($request->expectsJson()) {
            abort(403, 'Unauthorized.');
        }

        return redirect()
            ->route('login')
            ->withErrors([
                'email' => 'This account is not allowed to access the application.',
            ]);
    }
}
