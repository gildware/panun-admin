<?php

namespace Modules\LeadManagement\Services;

use Illuminate\Support\Facades\Log;
use Modules\LeadManagement\Entities\SuperfoneApiLog;

class SuperfoneApiLogger
{
    /**
     * @param  array<string, mixed>  $attributes
     */
    public function write(array $attributes): void
    {
        try {
            if (array_key_exists('payload', $attributes)) {
                $attributes['payload'] = $this->payload($attributes['payload']);
            }

            SuperfoneApiLog::query()->create($attributes);
        } catch (\Throwable $exception) {
            Log::warning('Superfone API log failed', ['message' => $exception->getMessage()]);
        }
    }

    /**
     * @return array<string, mixed>|null
     */
    private function payload(mixed $payload): ?array
    {
        if ($payload === null) {
            return null;
        }

        if (! is_array($payload)) {
            return ['value' => (string) $payload];
        }

        $encoded = json_encode($payload);
        if ($encoded === false) {
            return ['note' => 'Payload could not be encoded.'];
        }

        if (strlen($encoded) <= 100000) {
            return $payload;
        }

        return [
            'note' => 'Payload omitted because it was larger than 100 KB.',
            'event' => isset($payload['event']) ? (string) $payload['event'] : null,
            'cdr_uuid' => isset($payload['cdr_uuid']) ? (string) $payload['cdr_uuid'] : null,
        ];
    }
}
