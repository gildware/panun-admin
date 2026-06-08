<?php

namespace Modules\LeadManagement\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Modules\WhatsAppModule\Entities\WhatsAppMessage;
use Modules\WhatsAppModule\Services\WhatsAppGeminiSupportClient;
use Modules\WhatsAppModule\Services\WhatsAppLeadLifecycleService;

class WhatsAppFollowupSummaryService
{
    private const FULL_MESSAGE_LIMIT = 18;

    private const INCREMENTAL_MESSAGE_LIMIT = 12;

    private const MESSAGE_CHAR_LIMIT = 260;

    public function __construct(
        private readonly WhatsAppGeminiSupportClient $gemini
    ) {}

    /**
     * Read cached summary without calling Gemini.
     *
     * @return array{summary: ?string, is_current: bool, needs_refresh: bool}
     */
    public function getCachedSummary(string $waPhone): array
    {
        $waPhone = trim($waPhone);
        if ($waPhone === '') {
            return ['summary' => null, 'is_current' => false, 'needs_refresh' => false];
        }

        $revision = $this->threadRevision($waPhone);
        $cached = $this->readCache($waPhone);
        if ($cached === null) {
            return ['summary' => null, 'is_current' => false, 'needs_refresh' => false];
        }

        $cachedId = (int) $cached['latest_message_id'];
        $isCurrent = $cachedId === $revision['latest_id'];

        return [
            'summary' => (string) $cached['summary'],
            'is_current' => $isCurrent,
            'needs_refresh' => !$isCurrent && $revision['latest_id'] > $cachedId,
        ];
    }

    /**
     * @param  array<string, mixed>  $candidate
     * @return array{summary: ?string, from_cache: bool, ai_called: bool}
     */
    public function summarizeWithMeta(string $waPhone, array $candidate = []): array
    {
        $waPhone = trim($waPhone);
        if ($waPhone === '') {
            return ['summary' => null, 'from_cache' => false, 'ai_called' => false];
        }

        $revision = $this->threadRevision($waPhone);
        if ($revision['latest_id'] <= 0) {
            return ['summary' => null, 'from_cache' => false, 'ai_called' => false];
        }

        $cached = $this->readCache($waPhone);
        if ($cached !== null && (int) $cached['latest_message_id'] === $revision['latest_id']) {
            return [
                'summary' => (string) $cached['summary'],
                'from_cache' => true,
                'ai_called' => false,
            ];
        }

        if ($cached !== null && (int) $cached['latest_message_id'] < $revision['latest_id']) {
            $newMessages = $this->loadMessagesAfter($waPhone, (int) $cached['latest_message_id'], self::INCREMENTAL_MESSAGE_LIMIT);
            if ($newMessages->isNotEmpty()) {
                $updated = $this->generateIncrementalWithGemini((string) $cached['summary'], $newMessages, $candidate);
                if ($updated !== null && $updated !== '') {
                    $this->writeCache($waPhone, $updated, $revision['latest_id']);

                    return [
                        'summary' => $updated,
                        'from_cache' => false,
                        'ai_called' => true,
                    ];
                }
            }
        }

        $messages = $this->loadRecentMessages($waPhone, self::FULL_MESSAGE_LIMIT);
        if ($messages->isEmpty()) {
            return ['summary' => null, 'from_cache' => false, 'ai_called' => false];
        }

        $summary = $this->generateFullWithGemini($messages, $candidate);
        if ($summary !== null && $summary !== '') {
            $this->writeCache($waPhone, $summary, $revision['latest_id']);
        }

        return [
            'summary' => $summary,
            'from_cache' => false,
            'ai_called' => $summary !== null && $summary !== '',
        ];
    }

    /**
     * AI-generated conversation summary, cached per phone until a new message arrives.
     *
     * @param  array<string, mixed>  $candidate
     */
    public function summarize(string $waPhone, array $candidate = []): ?string
    {
        return $this->summarizeWithMeta($waPhone, $candidate)['summary'];
    }

    /**
     * @return array{latest_id: int, message_count: int}
     */
    private function threadRevision(string $waPhone): array
    {
        $row = WhatsAppMessage::query()
            ->where('phone', $waPhone)
            ->selectRaw('COALESCE(MAX(id), 0) as latest_id, COUNT(*) as message_count')
            ->first();

        return [
            'latest_id' => (int) ($row->latest_id ?? 0),
            'message_count' => (int) ($row->message_count ?? 0),
        ];
    }

    /**
     * @return array{summary: string, latest_message_id: int, generated_at: string}|null
     */
    private function readCache(string $waPhone): ?array
    {
        $data = Cache::get($this->cacheKey($waPhone));
        if (!is_array($data) || !isset($data['summary'], $data['latest_message_id'])) {
            return null;
        }

        $summary = trim((string) $data['summary']);
        if ($summary === '') {
            return null;
        }

        return [
            'summary' => $summary,
            'latest_message_id' => (int) $data['latest_message_id'],
            'generated_at' => (string) ($data['generated_at'] ?? ''),
        ];
    }

    private function writeCache(string $waPhone, string $summary, int $latestMessageId): void
    {
        $ttl = (int) config('services.omnidimension.followup_summary_cache_ttl', 2592000);
        Cache::put($this->cacheKey($waPhone), [
            'summary' => $this->truncate($summary, 1900),
            'latest_message_id' => $latestMessageId,
            'generated_at' => now()->toIso8601String(),
        ], $ttl > 0 ? $ttl : 2592000);
    }

    private function cacheKey(string $waPhone): string
    {
        return 'wa_followup_ai_summary:v2:' . substr(hash('sha256', $waPhone), 0, 24);
    }

    /**
     * @return Collection<int, WhatsAppMessage>
     */
    private function loadRecentMessages(string $waPhone, int $limit): Collection
    {
        return WhatsAppMessage::query()
            ->where('phone', $waPhone)
            ->orderByDesc('id')
            ->limit(max(1, min($limit, 25)))
            ->get(['id', 'message_text', 'direction', 'sent_by'])
            ->reverse()
            ->values();
    }

    /**
     * @return Collection<int, WhatsAppMessage>
     */
    private function loadMessagesAfter(string $waPhone, int $afterId, int $limit): Collection
    {
        return WhatsAppMessage::query()
            ->where('phone', $waPhone)
            ->where('id', '>', $afterId)
            ->orderBy('id')
            ->limit(max(1, min($limit, 20)))
            ->get(['id', 'message_text', 'direction', 'sent_by'])
            ->values();
    }

    /**
     * @param  Collection<int, WhatsAppMessage>  $messages
     * @param  array<string, mixed>  $candidate
     */
    private function generateFullWithGemini(Collection $messages, array $candidate): ?string
    {
        if (trim((string) config('services.gemini.api_key')) === '') {
            return null;
        }

        $transcript = $this->formatTranscript($messages);
        if ($transcript === '') {
            return null;
        }

        $metaLine = $this->compactMetaLine($candidate);
        $system = <<<'PROMPT'
Summarize this WhatsApp thread for a voice follow-up call prep note.
Reply with ONE plain-English paragraph (max 90 words): who contacted Panun Kaergar, service/request, location or timing if any, and what is still pending after the last AI reply.
No bullets, markdown, or JSON.
PROMPT;

        $user = ($metaLine !== '' ? $metaLine . "\n\n" : '') . $transcript;

        return $this->callGemini($system, $user, $candidate);
    }

    /**
     * @param  Collection<int, WhatsAppMessage>  $newMessages
     * @param  array<string, mixed>  $candidate
     */
    private function generateIncrementalWithGemini(string $previousSummary, Collection $newMessages, array $candidate): ?string
    {
        if (trim((string) config('services.gemini.api_key')) === '') {
            return null;
        }

        $transcript = $this->formatTranscript($newMessages);
        if ($transcript === '') {
            return $this->truncate($previousSummary, 1900);
        }

        $system = <<<'PROMPT'
Update a voice follow-up prep summary using new WhatsApp messages only.
Reply with ONE plain-English paragraph (max 90 words). Merge new facts into the previous summary; drop stale details if contradicted.
No bullets, markdown, or JSON. If nothing important changed, return the previous summary with minimal edits.
PROMPT;

        $user = "Previous summary:\n" . $this->truncate($previousSummary, 600)
            . "\n\nNew messages:\n" . $transcript;

        return $this->callGemini($system, $user, $candidate);
    }

    /**
     * @param  array<string, mixed>  $candidate
     */
    private function callGemini(string $system, string $user, array $candidate): ?string
    {
        try {
            $text = $this->gemini->generatePlainText($system, $user);
            if ($text === null || trim($text) === '') {
                return null;
            }

            return $this->truncate(trim($text), 1900);
        } catch (\Throwable $e) {
            Log::warning('WhatsApp follow-up AI summary failed', [
                'phone' => $candidate['phone'] ?? null,
                'message' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Stable metadata only — avoid fields that change without new messages (e.g. silent duration).
     *
     * @param  array<string, mixed>  $candidate
     */
    private function compactMetaLine(array $candidate): string
    {
        $parts = [];
        $name = WhatsAppLeadLifecycleService::realCustomerName($candidate['display_name'] ?? null);
        if ($name !== null) {
            $parts[] = 'Contact: ' . $this->truncate($name, 80);
        }
        $leadType = trim((string) ($candidate['lead_type'] ?? ''));
        if ($leadType !== '') {
            $parts[] = 'Lead type: ' . $leadType;
        }

        return implode(' · ', $parts);
    }

    /**
     * @param  Collection<int, WhatsAppMessage>  $messages
     */
    private function formatTranscript(Collection $messages): string
    {
        $lines = [];
        foreach ($messages as $msg) {
            $text = trim((string) $msg->message_text);
            if ($text === '' || (str_starts_with($text, '[') && str_ends_with($text, ']'))) {
                continue;
            }

            $role = match (true) {
                $msg->direction === 'IN' => 'C',
                strtoupper((string) ($msg->sent_by ?? '')) === 'AI' => 'AI',
                default => 'A',
            };

            $lines[] = $role . ': ' . $this->truncate($text, self::MESSAGE_CHAR_LIMIT);
        }

        return implode("\n", $lines);
    }

    private function truncate(string $text, int $max): string
    {
        $text = preg_replace('/\s+/', ' ', trim($text)) ?? $text;
        if (mb_strlen($text) <= $max) {
            return $text;
        }

        return mb_substr($text, 0, $max - 1) . '…';
    }
}
