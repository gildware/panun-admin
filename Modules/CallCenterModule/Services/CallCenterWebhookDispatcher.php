<?php

namespace Modules\CallCenterModule\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class CallCenterWebhookDispatcher
{
    public function dispatch(string $event, int $customerProfileId, array $data = []): void
    {
        $url = config('services.call_center.webhook_url');
        $secret = config('services.call_center.webhook_secret');

        if (!$url) {
            return;
        }

        $payload = [
            'event' => $event,
            'customer_id' => $customerProfileId,
            'occurred_at' => now()->utc()->toIso8601String(),
            'data' => $data,
        ];

        $body = json_encode($payload);
        $headers = ['Content-Type' => 'application/json'];

        if ($secret) {
            $headers['X-Laravel-Signature'] = hash_hmac('sha256', $body, $secret);
        }

        try {
            Http::timeout(5)
                ->withHeaders($headers)
                ->withBody($body, 'application/json')
                ->post($url);
        } catch (\Throwable $e) {
            Log::warning('Call center webhook dispatch failed', [
                'event' => $event,
                'customer_id' => $customerProfileId,
                'error' => $e->getMessage(),
            ]);
        }
    }

    public function resolveCustomerProfileIdForUser(string $userId): ?int
    {
        return \Modules\CallCenterModule\Entities\CustomerProfile::query()
            ->where('user_id', $userId)
            ->value('id');
    }
}
