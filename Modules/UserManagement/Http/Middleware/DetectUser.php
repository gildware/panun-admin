<?php

namespace Modules\UserManagement\Http\Middleware;

use App\Lib\PaymentAccessToken;
use Closure;
use Illuminate\Http\Request;
use Modules\UserManagement\Entities\User;

class DetectUser
{
    /**
     * Handle an incoming request.
     *
     * @param Request $request
     * @param Closure $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        if ($request->has('access_token')) {
            $userId = PaymentAccessToken::resolve($request['access_token']);
            $user = $userId ? User::find($userId) : null;
            if ($user) {
                $request['user'] = $user;
                return $next($request);
            }
            return response()->json(response_formatter(DEFAULT_401));
        }
        return response()->json(response_formatter(DEFAULT_401));
    }
}
