<?php

namespace Modules\InAppCallModule\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Modules\InAppCallModule\Services\InAppCallService;

class EnsureInAppCallIsActive
{
    public function __construct(
        protected InAppCallService $inAppCallService,
    ) {}

    /**
     * @param Request $request
     * @param Closure $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        if (! $this->inAppCallService->isEnabled()) {
            return response()->json(DEFAULT_403);
        }

        return $next($request);
    }
}
