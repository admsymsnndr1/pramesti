<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class CheckPermission
{
    /**
     * Usage: ->middleware('permission:wbs.update')
     * Several permissions = any of them is enough: 'permission:tasks.view,wbs.view'
     */
    public function handle(Request $request, Closure $next, string ...$permissions)
    {
        $user = $request->user();

        if (!$user || !$user->hasAnyPermission($permissions)) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'You do not have access to this feature'], 403);
            }

            abort(403, 'You do not have access to this feature');
        }

        return $next($request);
    }
}
