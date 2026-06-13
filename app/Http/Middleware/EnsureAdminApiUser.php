<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class EnsureAdminApiUser
{
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user('api');

        if (!$user || !in_array($user->user_type, ADMIN_USER_TYPES, true)) {
            return response()->json(response_formatter(DEFAULT_403), 403);
        }

        return $next($request);
    }
}
