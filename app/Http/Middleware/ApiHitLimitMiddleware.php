<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;

class ApiHitLimitMiddleware
{
    public function handle(Request $request, Closure $next, string $attempt_type = 'normal', int $hit_count = 5, int $decay_seconds = 60)
    {
        $key = 'hit_limit:' . $attempt_type . ':' . (optional($request->user())->id ?: $request->ip());

        if (Cache::has($key)) {
            return response()->json(TOO_MANY_ATTEMPT_403);
        }

        Cache::put($key, 1, $decay_seconds);

        return $next($request);
    }
}
