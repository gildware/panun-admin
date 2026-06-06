<?php

namespace Modules\AdminModule\Services;

use Illuminate\Support\Facades\Cache;

class AdminBusinessAiSessionService
{
    private function cacheKey(int $adminUserId): string
    {
        return 'admin_business_ai:'.(string) $adminUserId;
    }

    /**
     * @return list<array{role: string, text: string, at: string}>
     */
    public function messages(int $adminUserId): array
    {
        $stored = Cache::get($this->cacheKey($adminUserId), []);

        return is_array($stored) ? $stored : [];
    }

    /**
     * @return list<array{role: string, text: string, at: string}>
     */
    public function append(int $adminUserId, string $role, string $text): array
    {
        $messages = $this->messages($adminUserId);
        $messages[] = [
            'role' => $role === 'model' ? 'model' : 'user',
            'text' => mb_substr(trim($text), 0, 12000),
            'at' => now()->toIso8601String(),
        ];

        $limit = (int) config('admin_business_ai.context_turn_limit', 20) * 2;
        if (count($messages) > $limit) {
            $messages = array_slice($messages, -$limit);
        }

        $ttl = (int) config('admin_business_ai.session_ttl_minutes', 1440);
        Cache::put($this->cacheKey($adminUserId), $messages, now()->addMinutes($ttl));

        return $messages;
    }

    public function reset(int $adminUserId): void
    {
        Cache::forget($this->cacheKey($adminUserId));
    }

    /**
     * Remove the last message (used when a turn fails after the user line was stored).
     */
    public function popLast(int $adminUserId): void
    {
        $messages = $this->messages($adminUserId);
        if ($messages === []) {
            return;
        }
        array_pop($messages);
        $ttl = (int) config('admin_business_ai.session_ttl_minutes', 1440);
        Cache::put($this->cacheKey($adminUserId), $messages, now()->addMinutes($ttl));
    }
}
