<?php

namespace Modules\CallCenterModule\Http\Middleware;

use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class EnsureCallCenterServiceToken
{
    public function handle(Request $request, Closure $next)
    {
        $token = config('services.call_center.api_key');
        if (!$token) {
            return $this->error('service_unavailable', 'Call center API key not configured.', 503);
        }

        $provided = $request->header('X-API-Key') ?: $request->bearerToken();
        if (!hash_equals($token, (string) $provided)) {
            return $this->error('unauthorized', 'Invalid or missing API credentials.', 401);
        }

        return $next($request);
    }

    private function error(string $code, string $message, int $status): JsonResponse
    {
        return response()->json([
            'error' => [
                'code' => $code,
                'message' => $message,
            ],
        ], $status);
    }
}
