<?php

namespace Modules\LeadManagement\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\LeadManagement\Services\SuperfoneApiLogger;
use Modules\LeadManagement\Services\SuperfoneCallService;

class SuperfoneWebhookController extends Controller
{
    public function __construct(
        private readonly SuperfoneCallService $calls,
        private readonly SuperfoneApiLogger $logs,
    ) {
    }

    public function calls(Request $request): JsonResponse
    {
        $started = microtime(true);
        $secret = (string) config('services.superfone.webhook_secret');
        if ($secret === '') {
            $this->logWebhook([], 401, 'unauthorized', 'Webhook secret is not configured.', $started);

            return response()->json(['message' => 'Webhook secret is not configured.'], 401);
        }

        $provided = $this->providedSecret($request);
        if ($provided === '' || ! hash_equals($secret, $provided)) {
            $this->logWebhook([], 401, 'unauthorized', 'Unauthorized.', $started);

            return response()->json(['message' => 'Unauthorized.'], 401);
        }

        $payload = $request->json()->all();
        if (! is_array($payload) || $payload === []) {
            $payload = $request->all();
        }
        $payload = is_array($payload) ? $payload : [];

        if ($payload === []) {
            $this->logWebhook($payload, 200, 'empty', 'Empty webhook body.', $started);

            return response()->json([
                'received' => true,
                'cdr_uuid' => null,
            ]);
        }

        try {
            $call = $this->calls->receive($payload);
        } catch (\Throwable $exception) {
            $this->logWebhook($payload, 500, 'failed', $exception->getMessage(), $started);

            throw $exception;
        }

        $this->logWebhook(
            $payload,
            200,
            $call ? 'received' : 'ignored',
            $call ? null : 'Ignored event or missing call id.',
            $started,
            $call?->cdr_uuid
        );

        return response()->json([
            'received' => true,
            'cdr_uuid' => $call?->cdr_uuid,
        ]);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function logWebhook(array $payload, int $status, string $result, ?string $message, float $started, ?string $cdrUuid = null): void
    {
        $event = strtoupper(trim((string) ($payload['event'] ?? '')));
        $uuid = $cdrUuid ?: trim((string) ($payload['cdr_uuid'] ?? ''));

        $this->logs->write([
            'direction' => 'inbound',
            'kind' => 'webhook',
            'event' => $event !== '' ? $event : null,
            'cdr_uuid' => $uuid !== '' ? $uuid : null,
            'method' => 'POST',
            'url' => '/webhooks/superfone/calls',
            'http_status' => $status,
            'result' => $result,
            'message' => $message,
            'payload' => $payload === [] ? null : $payload,
            'duration_ms' => (int) round((microtime(true) - $started) * 1000),
        ]);
    }

    private function providedSecret(Request $request): string
    {
        $bearer = $request->bearerToken();
        if (is_string($bearer) && $bearer !== '') {
            return $bearer;
        }

        foreach (['X-Webhook-Secret', 'X-Superfone-Secret'] as $header) {
            $value = $request->header($header);
            if (is_string($value) && $value !== '') {
                return $value;
            }
        }

        $authorization = trim((string) $request->header('Authorization', ''));
        if ($authorization !== '' && ! str_starts_with(strtolower($authorization), 'bearer ')) {
            return $authorization;
        }

        return '';
    }
}
