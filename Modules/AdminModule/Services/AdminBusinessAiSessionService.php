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
     * @return list<array{role: string, text: string, at: string, charts?: list<array<string, mixed>>}>
     */
    public function messages(int $adminUserId): array
    {
        $stored = Cache::get($this->cacheKey($adminUserId), []);

        return is_array($stored) ? $stored : [];
    }

    /**
     * @param  list<array<string, mixed>>|null  $charts
     * @param  list<array<string, mixed>>|null  $tables
     * @return list<array{role: string, text: string, at: string, charts?: list<array<string, mixed>>, tables?: list<array<string, mixed>>, note?: string}>
     */
    public function append(int $adminUserId, string $role, string $text, ?array $charts = null, ?array $tables = null, ?string $note = null): array
    {
        $messages = $this->messages($adminUserId);
        $entry = [
            'role' => $role === 'model' ? 'model' : 'user',
            'text' => mb_substr(trim($text), 0, 12000),
            'at' => now()->toIso8601String(),
        ];
        if ($charts !== null && $charts !== []) {
            $entry['charts'] = array_slice($charts, 0, 6);
        }
        if ($tables !== null && $tables !== []) {
            // Keep session payload lean: store capped rows only.
            $entry['tables'] = array_map(static function (array $table): array {
                $rows = is_array($table['rows'] ?? null) ? $table['rows'] : [];
                $table['rows'] = array_slice($rows, 0, 40);

                return $table;
            }, array_slice($tables, 0, 4));
        }
        $note = $note !== null ? trim($note) : '';
        if ($note !== '') {
            $entry['note'] = mb_substr($note, 0, 2000);
        }
        $messages[] = $entry;

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
