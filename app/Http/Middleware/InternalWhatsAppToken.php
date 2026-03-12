<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class InternalWhatsAppToken
{
    public function handle(Request $request, Closure $next)
    {
        $token = config('services.whatsapp_internal.token');
        if (!$token) {
            abort(503, 'Internal WhatsApp token not configured.');
        }

        $provided = $request->header('X-Internal-Token') ?: $request->bearerToken();
        if (!hash_equals($token, (string) $provided)) {
            abort(401, 'Unauthorized');
        }

        return $next($request);
    }
}

