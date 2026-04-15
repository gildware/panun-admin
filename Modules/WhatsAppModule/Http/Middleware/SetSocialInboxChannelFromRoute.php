<?php

namespace Modules\WhatsAppModule\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;
use Modules\WhatsAppModule\Support\SocialInboxChannel;
use Symfony\Component\HttpFoundation\Response;

class SetSocialInboxChannelFromRoute
{
    public function handle(Request $request, Closure $next): Response
    {
        $channel = $request->route('channel');
        if (!is_string($channel) || !SocialInboxChannel::isValid($channel)) {
            abort(404);
        }
        app()->instance('social_inbox_channel', $channel);
        URL::defaults(['channel' => $channel]);

        return $next($request);
    }
}
