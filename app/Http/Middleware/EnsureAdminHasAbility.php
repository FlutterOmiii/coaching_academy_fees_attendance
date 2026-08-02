<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureAdminHasAbility
{
    /**
     * Allow the request only when the signed-in admin holds one of the given
     * abilities. Usage: ->middleware('ability:fees.manage')
     */
    public function handle(Request $request, Closure $next, string ...$abilities): Response
    {
        $admin = Auth::guard('admin')->user();

        if (! $admin) {
            return redirect()->route('admin.login');
        }

        foreach ($abilities as $ability) {
            if ($admin->hasAbility($ability)) {
                return $next($request);
            }
        }

        abort(403, 'You do not have permission to access this section.');
    }
}
