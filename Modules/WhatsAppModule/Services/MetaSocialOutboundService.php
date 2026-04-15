<?php

namespace Modules\WhatsAppModule\Services;

use Illuminate\Support\Facades\Http;

/**
 * Outbound Facebook Messenger + Instagram (IGDM) text via Graph API.
 */
class MetaSocialOutboundService
{
    public function sendMessengerText(string $recipientPsid, string $text, ?string &$err, ?array &$graph): ?string
    {
        $token = trim((string) config('services.facebook_messenger.page_access_token'));
        if ($token === '') {
            $err = 'messenger_token_missing';

            return null;
        }
        $ver = (string) config('services.facebook_messenger.graph_version', 'v19.0');
        $url = 'https://graph.facebook.com/'.$ver.'/me/messages';
        $resp = Http::withToken($token)
            ->acceptJson()
            ->post($url, [
                'recipient' => ['id' => $recipientPsid],
                'messaging_type' => 'RESPONSE',
                'message' => ['text' => $text],
            ]);
        $graph = [
            'http' => $resp->status(),
            'body' => $resp->json(),
        ];
        if (!$resp->successful()) {
            $err = 'messenger_http_'.$resp->status();

            return null;
        }
        $mid = data_get($resp->json(), 'message_id');
        if (is_string($mid) && $mid !== '') {
            return $mid;
        }

        return (string) (data_get($resp->json(), 'message_id') ?? '');
    }

    public function sendInstagramText(string $recipientIgsid, string $text, ?string &$err, ?array &$graph): ?string
    {
        $token = trim((string) config('services.instagram_dm.access_token'));
        $igUserId = trim((string) config('services.instagram_dm.instagram_user_id'));
        if ($token === '' || $igUserId === '') {
            $err = 'instagram_token_missing';

            return null;
        }
        $ver = (string) config('services.instagram_dm.graph_version', 'v19.0');
        $url = 'https://graph.facebook.com/'.$ver.'/'.$igUserId.'/messages';
        $resp = Http::withToken($token)
            ->acceptJson()
            ->post($url, [
                'recipient' => ['id' => $recipientIgsid],
                'message' => ['text' => $text],
            ]);
        $graph = [
            'http' => $resp->status(),
            'body' => $resp->json(),
        ];
        if (!$resp->successful()) {
            $err = 'instagram_http_'.$resp->status();

            return null;
        }

        return (string) (data_get($resp->json(), 'message_id') ?? data_get($resp->json(), 'id') ?? '');
    }
}
