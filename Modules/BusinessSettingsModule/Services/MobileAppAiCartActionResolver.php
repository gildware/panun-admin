<?php

namespace Modules\BusinessSettingsModule\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Modules\UserManagement\Entities\User;
use Modules\WhatsAppModule\Services\WhatsAppGeminiSupportClient;

/**
 * Turn classified cart intent + natural language into a deterministic cart op (line ids or generic filters).
 * Avoids growing phrase-regex lists — Gemini reads live cart lines; backend validates and executes.
 */
class MobileAppAiCartActionResolver
{
    public function __construct(
        protected MobileAppAiRuntimeResolver $runtime,
        protected WhatsAppGeminiSupportClient $gemini,
        protected MobileAppAiCartService $cartService,
    ) {}

    /**
     * @return array{op: string, target: string, schedule_text: string, cart_line_ids: list<string>, cart_filter: string}|null
     */
    public function resolve(User $user, string $text, MobileAppAiIntentClassification $classification): ?array
    {
        $intent = $classification->intent;
        if (! MobileAppAiIntentCatalog::isCartFamily($intent) || $intent === MobileAppAiIntentCatalog::VIEW_CART
            || $intent === MobileAppAiIntentCatalog::CART_SCHEDULE_QUERY
            || $intent === MobileAppAiIntentCatalog::PRICING_QUERY) {
            return null;
        }

        $lines = $this->cartLinesForUser($user);
        if ($lines === [] && $intent !== MobileAppAiIntentCatalog::CART_CLEAR) {
            return null;
        }

        $fromEntities = $this->fromClassificationEntities($intent, $classification, $lines);
        if ($fromEntities !== null) {
            return $fromEntities;
        }

        if ((bool) config('mobile_app_ai_intent_classification.cart_action_use_gemini', true)
            && $this->runtime->enabled()) {
            $fromGemini = $this->resolveWithGemini($user, $text, $intent, $lines);
            if ($fromGemini !== null) {
                return $fromGemini;
            }
        }

        return $this->resolveMessage($user, $text) ?? $this->intentToParsed($intent, $classification);
    }

    /**
     * @param  list<array{cart_line_id: string, service_name: string, service_schedule: ?string, schedule_label: string}>  $lines
     * @return array{op: string, target: string, schedule_text: string, cart_line_ids: list<string>, cart_filter: string}|null
     */
    private function fromClassificationEntities(
        string $intent,
        MobileAppAiIntentClassification $classification,
        array $lines,
    ): ?array {
        $ids = $classification->entityStringList('cart_line_ids');
        $filter = $classification->entityString('cart_filter');
        $validIds = $this->filterValidLineIds($ids, $lines);

        if ($validIds !== []) {
            return [
                'op' => $this->intentToOp($intent, $classification),
                'target' => $classification->entityString('remove_target'),
                'schedule_text' => $classification->entityString('schedule_text'),
                'cart_line_ids' => $validIds,
                'cart_filter' => '',
            ];
        }

        if ($filter !== '' && $this->isKnownCartFilter($filter)) {
            return [
                'op' => $this->intentToOp($intent, $classification),
                'target' => $classification->entityString('remove_target'),
                'schedule_text' => $classification->entityString('schedule_text'),
                'cart_line_ids' => [],
                'cart_filter' => $filter,
            ];
        }

        $remove = $classification->entityString('remove_target');
        $keep = $classification->entityString('keep_target');
        if ($remove !== '' || $keep !== '') {
            $op = $keep !== '' && $remove === '' ? 'keep_only' : 'remove';

            return [
                'op' => $op,
                'target' => $keep !== '' && $remove === '' ? $keep : $remove,
                'schedule_text' => $classification->entityString('schedule_text'),
                'cart_line_ids' => [],
                'cart_filter' => '',
            ];
        }

        return null;
    }

    /**
     * AI-primary cart understanding — uses live cart catalog + customer words (Hinglish/English).
     *
     * @return array{op: string, target: string, schedule_text: string, cart_line_ids: list<string>, cart_filter: string}|null
     */
    public function resolveMessage(User $user, string $text): ?array
    {
        $lines = $this->cartLinesForUser($user);

        if ((bool) config('mobile_app_ai_intent_classification.cart_action_use_gemini', true)
            && $this->runtime->enabled()) {
            $fromGemini = $this->resolveWithGemini($user, $text, MobileAppAiIntentCatalog::CART_REMOVE_ITEM, $lines);
            if ($fromGemini !== null) {
                return $fromGemini;
            }
        }

        $parsed = MobileAppAiCartRequestParser::parse($text);
        if ($parsed === null || ($parsed['op'] ?? '') === 'view') {
            return null;
        }

        return [
            'op' => (string) ($parsed['op'] ?? ''),
            'target' => (string) ($parsed['target'] ?? ''),
            'schedule_text' => (string) ($parsed['schedule_text'] ?? ''),
            'cart_line_ids' => [],
            'cart_filter' => (string) ($parsed['cart_filter'] ?? ''),
        ];
    }

    /**
     * @param  list<array{cart_line_id: string, service_name: string, service_schedule: ?string, schedule_label: string}>  $lines
     * @return array{op: string, target: string, schedule_text: string, cart_line_ids: list<string>, cart_filter: string}|null
     */
    private function resolveWithGemini(User $user, string $text, string $intent, array $lines): ?array
    {
        $now = Carbon::now()->format('Y-m-d H:i:s T');
        $catalog = $this->formatCartCatalog($lines);
        $targetIntent = $intent;

        $system = <<<'PROMPT'
You interpret what the customer wants to do with their cart (English, Roman Urdu, Hinglish).
Return ONLY one JSON object:
{"op":"remove|keep_only|keep_one|clear_all|reschedule|view","cart_line_ids":[],"cart_filter":"none|visit_before_now|visit_after_now|no_schedule","remove_target":"","keep_target":"","schedule_text":"","confidence":0.0-1.0}

Rules:
- Use exact cart_line_id values from the catalog when the user points at specific lines (e.g. past visits, duplicates, "first AC", "all AC except inverter").
- op **keep_one** = keep exactly one matching line (any one is fine) and remove other duplicates of the same service (e.g. "koi bhi ek rakho baki delete", "AC ki ek hi service rakho").
- op **keep_only** = remove everything EXCEPT lines matching keep_target (e.g. "keep only inverter").
- cart_filter visit_before_now = lines whose visit datetime is already in the past relative to server_now.
- cart_filter visit_after_now = future visits only.
- cart_filter no_schedule = lines missing a visit time.
- remove_target / keep_target = service name scope (AC, inverter) when ids are unclear; leave empty if using cart_line_ids or cart_filter.
- clear_all only when user wants the whole cart emptied.
- Do not invent cart_line_ids; only use ids from the catalog.
PROMPT;

        $userPrompt = "server_now: {$now}\nIntent hint: {$targetIntent}\n{$catalog}\nCustomer: {$text}";

        try {
            $raw = $this->gemini->generatePlainText($system, $userPrompt);
            if ($raw === null) {
                return null;
            }
            $json = $this->extractJson($raw);
            if ($json === null) {
                return null;
            }

            $op = (string) ($json['op'] ?? '');
            if ($op === '' || $op === 'view') {
                return null;
            }

            $ids = $this->filterValidLineIds(
                $this->normalizeIdList($json['cart_line_ids'] ?? []),
                $lines
            );
            $filter = (string) ($json['cart_filter'] ?? 'none');
            if ($filter === 'none') {
                $filter = '';
            }

            $keepTarget = trim((string) ($json['keep_target'] ?? ''));
            $removeTarget = trim((string) ($json['remove_target'] ?? ''));

            return [
                'op' => $op,
                'target' => $op === 'keep_only' && $keepTarget !== ''
                    ? $keepTarget
                    : ($op === 'keep_one' ? $removeTarget : $removeTarget),
                'schedule_text' => trim((string) ($json['schedule_text'] ?? '')),
                'cart_line_ids' => $ids,
                'cart_filter' => $this->isKnownCartFilter($filter) ? $filter : '',
            ];
        } catch (\Throwable $e) {
            Log::warning('mobile_app_ai.cart_action_gemini_failed', ['error' => $e->getMessage()]);

            return null;
        }
    }

    /**
     * @return array{op: string, target: string, schedule_text: string, cart_line_ids: list<string>, cart_filter: string}|null
     */
    private function intentToParsed(string $intent, MobileAppAiIntentClassification $c): ?array
    {
        $op = $this->intentToOp($intent, $c);
        if ($op === '') {
            return null;
        }

        return [
            'op' => $op,
            'target' => $c->entityString('remove_target'),
            'schedule_text' => $c->entityString('schedule_text'),
            'cart_line_ids' => $c->entityStringList('cart_line_ids'),
            'cart_filter' => $c->entityString('cart_filter'),
        ];
    }

    private function intentToOp(string $intent, MobileAppAiIntentClassification $c): string
    {
        return match ($intent) {
            MobileAppAiIntentCatalog::CART_CLEAR => 'clear_all',
            MobileAppAiIntentCatalog::CART_REMOVE_ITEM => $c->entityString('keep_target') !== '' && $c->entityString('remove_target') === ''
                ? 'keep_only'
                : 'remove',
            MobileAppAiIntentCatalog::CART_RESCHEDULE => 'reschedule',
            default => '',
        };
    }

    /**
     * @param  list<array{cart_line_id: string, service_name: string, service_schedule: ?string, schedule_label: string}>  $lines
     */
    private function formatCartCatalog(array $lines): string
    {
        if ($lines === []) {
            return 'Cart catalog: (empty)';
        }

        $rows = ['Cart catalog (cart_line_id | service | visit):'];
        foreach ($lines as $line) {
            $visit = ($line['schedule_label'] ?? '') !== ''
                ? (string) $line['schedule_label']
                : (($line['service_schedule'] ?? '') !== '' ? (string) $line['service_schedule'] : 'no visit set');
            $rows[] = '- '.($line['cart_line_id'] ?? '').' | '.($line['service_name'] ?? 'Service').' | '.$visit;
        }

        return implode("\n", $rows);
    }

    /**
     * @return list<array{cart_line_id: string, service_name: string, service_schedule: ?string, schedule_label: string}>
     */
    private function cartLinesForUser(User $user): array
    {
        $summary = $this->cartService->cartSummaryForUser($user);
        $out = [];
        foreach ($summary['items'] ?? [] as $item) {
            if (! is_array($item)) {
                continue;
            }
            $id = (string) ($item['cart_line_id'] ?? '');
            if ($id === '') {
                continue;
            }
            $out[] = [
                'cart_line_id' => $id,
                'service_name' => (string) ($item['service_name'] ?? 'Service'),
                'service_schedule' => isset($item['service_schedule']) ? (string) $item['service_schedule'] : null,
                'schedule_label' => (string) ($item['schedule_label'] ?? ''),
            ];
        }

        return $out;
    }

    /**
     * @param  mixed  $raw
     * @return list<string>
     */
    private function normalizeIdList(mixed $raw): array
    {
        if (! is_array($raw)) {
            return [];
        }

        $ids = [];
        foreach ($raw as $id) {
            if (is_string($id) && trim($id) !== '') {
                $ids[] = trim($id);
            }
        }

        return array_values(array_unique($ids));
    }

    /**
     * @param  list<string>  $ids
     * @param  list<array{cart_line_id: string}>  $lines
     * @return list<string>
     */
    private function filterValidLineIds(array $ids, array $lines): array
    {
        $allowed = [];
        foreach ($lines as $line) {
            $allowed[(string) ($line['cart_line_id'] ?? '')] = true;
        }

        $valid = [];
        foreach ($ids as $id) {
            if (isset($allowed[$id])) {
                $valid[] = $id;
            }
        }

        return $valid;
    }

    private function isKnownCartFilter(string $filter): bool
    {
        return in_array($filter, ['visit_before_now', 'visit_after_now', 'no_schedule'], true);
    }

    /**
     * @return array<string, mixed>|null
     */
    private function extractJson(string $raw): ?array
    {
        $raw = trim($raw);
        if (preg_match('/\{[\s\S]*\}/', $raw, $m)) {
            $raw = $m[0];
        }
        $decoded = json_decode($raw, true);

        return is_array($decoded) ? $decoded : null;
    }
}
