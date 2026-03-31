<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Config;
use Modules\ZoneManagement\Entities\Zone;

class ZoneAdder
{
    /**
     * Handle an incoming request.
     *
     * @param Request $request
     * @param Closure(Request): (Response|RedirectResponse)  $next
     * @return JsonResponse
     */
    public function handle(Request $request, Closure $next)
    {
        if (request()->is('api/*/customer?*') || request()->is('api/*/customer/*')) {
            $zoneId = $request->header('zoneid') ?? null;
            Config::set('zone_id', $zoneId);

            // Only enforce leaf-zone validation for valid UUID inputs.
            if ($zoneId && preg_match('/^[0-9A-F]{8}-[0-9A-F]{4}-4[0-9A-F]{3}-[89AB][0-9A-F]{3}-[0-9A-F]{12}$/i', $zoneId)) {
                $zone = Zone::ofStatus(1)->where('id', $zoneId)->first();
                if (!isset($zone)) {
                    return response()->json(response_formatter(ZONE_404), 401);
                }

                // Reject non-leaf zones: a zone is "leaf" when it has no child zones.
                $hasChildren = Zone::query()->where('parent_id', $zoneId)->exists();
                if ($hasChildren) {
                    return response()->json(response_formatter(ZONE_SELECT_LEAF_401), 401);
                }
            }
        }
        return $next($request);
    }
}
