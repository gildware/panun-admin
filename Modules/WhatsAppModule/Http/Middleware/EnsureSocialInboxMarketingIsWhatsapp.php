<?php

namespace Modules\WhatsAppModule\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Modules\WhatsAppModule\Support\SocialInboxChannel;
use Symfony\Component\HttpFoundation\Response;

/**
 * WhatsApp Cloud marketing (WABA templates, bulk, campaigns) is only available for the WhatsApp channel.
 */
class EnsureSocialInboxMarketingIsWhatsapp
{
    public function handle(Request $request, Closure $next): Response
    {
        $ch = $request->route('channel');
        if ($ch !== SocialInboxChannel::WHATSAPP) {
            abort(404);
        }

        return $next($request);
    }
}
