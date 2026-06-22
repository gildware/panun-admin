<?php

namespace Modules\CallCenterModule\Services;

use Illuminate\Http\Request;
use Modules\CallCenterModule\Entities\IdempotencyKey;

class IdempotencyService
{
    public function replayIfExists(Request $request, string $endpoint): ?array
    {
        $key = trim((string) $request->header('Idempotency-Key'));
        if ($key === '') {
            return null;
        }

        $existing = IdempotencyKey::query()
            ->where('idempotency_key', $key)
            ->where('endpoint', $endpoint)
            ->first();

        if (!$existing) {
            return null;
        }

        return [
            'status' => $existing->response_status,
            'body' => $existing->response_body,
        ];
    }

    public function store(string $key, string $endpoint, int $status, array $body): void
    {
        if ($key === '') {
            return;
        }

        IdempotencyKey::query()->updateOrCreate(
            [
                'idempotency_key' => $key,
                'endpoint' => $endpoint,
            ],
            [
                'response_status' => $status,
                'response_body' => $body,
            ]
        );
    }
}
