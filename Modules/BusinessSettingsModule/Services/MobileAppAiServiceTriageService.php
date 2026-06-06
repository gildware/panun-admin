<?php

namespace Modules\BusinessSettingsModule\Services;

use Modules\BusinessSettingsModule\Entities\MobileAppAiConversation;

/**
 * Short triage before booking: clarify issue → brief relevant tips → book.
 */
class MobileAppAiServiceTriageService
{
    public function __construct(
        protected MobileAppAiSupportKnowledgeService $supportKnowledge,
        protected MobileAppAiBookingUiPresenter $bookingUi,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function startTriage(MobileAppAiConversation $conversation, string $rawText, string $serviceQuery): array
    {
        $intent = MobileAppAiServiceIntentResolver::resolve($rawText);
        $serviceQuery = trim($serviceQuery) !== '' ? trim($serviceQuery) : $intent['catalog_query'];
        if ($serviceQuery === '') {
            $serviceQuery = MobileAppAiServiceQueryNormalizer::normalize($rawText);
        }
        $pack = $this->resolveServicePack($serviceQuery, $rawText);

        $draft = [
            'step' => 'service_triage',
            'choices' => [
                'service_query' => $serviceQuery,
                'service_name' => $serviceQuery,
                'service_pack' => $pack['key'],
                'triage_original_issue' => trim($rawText),
                'last_customer_message' => trim($rawText),
            ],
            'options' => [],
        ];

        $conversation->booking_draft = $draft;
        $conversation->save();

        if (MobileAppAiTriageFollowUp::isUsableIssueDescription($rawText)) {
            return $this->continueTriage($conversation, $rawText, true);
        }

        $reply = MobileAppAiTriageCopy::initialAsk($serviceQuery, $pack['ask']);

        return $this->result($conversation, $reply);
    }

    /**
     * @return array<string, mixed>
     */
    public function continueTriage(MobileAppAiConversation $conversation, string $text, bool $isFirstIssueReply = false): array
    {
        $draft = is_array($conversation->booking_draft) ? $conversation->booking_draft : [];
        $serviceQuery = trim((string) ($draft['choices']['service_query'] ?? ''));
        $originalIssue = trim((string) ($draft['choices']['triage_original_issue'] ?? ''));
        $tipsAlreadyShown = ($draft['choices']['triage_tips_shown'] ?? false) === true;

        if ($originalIssue === '' && MobileAppAiTriageFollowUp::isUsableIssueDescription($text)) {
            $originalIssue = trim($text);
        }

        $pack = $this->packByStoredKey(
            (string) ($draft['choices']['service_pack'] ?? ''),
            $serviceQuery,
            $originalIssue !== '' ? $originalIssue : $text
        );

        $draft['step'] = 'service_triage';
        $draft['choices']['last_customer_message'] = trim($text);
        if ($originalIssue !== '') {
            $draft['choices']['triage_original_issue'] = $originalIssue;
            $draft['choices']['issue_description'] = $originalIssue;
        }

        if (MobileAppAiTriageFollowUp::isFollowUp($text)) {
            $conversation->booking_draft = $draft;
            $conversation->save();
            $reply = MobileAppAiTriageFollowUp::describesProblem($originalIssue)
                ? MobileAppAiTriageCopy::stillNotFixedAfterTriage($originalIssue)
                : MobileAppAiTriageCopy::acknowledgeTriedSteps();

            return $this->result($conversation, $reply);
        }

        if ($tipsAlreadyShown && MobileAppAiTriageFollowUp::isUsableIssueDescription($text)) {
            $merged = $this->mergeIssues($originalIssue, $text);
            $draft['choices']['triage_original_issue'] = $merged;
            $draft['choices']['issue_description'] = $merged;
            $conversation->booking_draft = $draft;
            $conversation->save();

            $tipLines = $this->relevantTipLines($serviceQuery, $merged, $pack);
            $reply = MobileAppAiTriageCopy::additionalIssueResponse($merged, $tipLines);

            return $this->result($conversation, $reply);
        }

        if ($tipsAlreadyShown) {
            $conversation->booking_draft = $draft;
            $conversation->save();

            return $this->result($conversation, MobileAppAiTriageCopy::acknowledgeTriedSteps());
        }

        if (! MobileAppAiTriageFollowUp::isUsableIssueDescription($text) && ! $isFirstIssueReply) {
            $conversation->booking_draft = $draft;
            $conversation->save();
            $reply = MobileAppAiTriageCopy::clarifyQuestion($pack['clarify'] ?? $pack['ask']);

            return $this->result($conversation, $reply);
        }

        $issueForTips = $originalIssue !== '' ? $originalIssue : trim($text);
        $tipLines = $this->relevantTipLines($serviceQuery, $issueForTips, $pack);
        $draft['choices']['triage_tips_shown'] = true;
        $draft['choices']['triage_tip_offset'] = count($tipLines);
        $conversation->booking_draft = $draft;
        $conversation->save();

        $reply = MobileAppAiTriageCopy::issueResponse($issueForTips, $tipLines);

        return $this->result($conversation, $reply);
    }

    /**
     * Extra home-service tips (button: More troubleshooting) — not app/payment help.
     *
     * @return array<string, mixed>
     */
    public function moreTips(MobileAppAiConversation $conversation): array
    {
        $draft = is_array($conversation->booking_draft) ? $conversation->booking_draft : [];
        $serviceQuery = trim((string) ($draft['choices']['service_query'] ?? ''));
        $originalIssue = trim((string) ($draft['choices']['triage_original_issue'] ?? ''));
        $offset = (int) ($draft['choices']['triage_tip_offset'] ?? 0);

        $pack = $this->packByStoredKey(
            (string) ($draft['choices']['service_pack'] ?? ''),
            $serviceQuery,
            $originalIssue !== '' ? $originalIssue : $serviceQuery
        );

        $allTips = $pack['tips'];
        $next = array_slice($allTips, $offset, MobileAppAiReplyStyle::MAX_TIP_LINES);
        $newOffset = $offset + count($next);

        $draft['step'] = 'service_triage';
        $draft['choices']['triage_tips_shown'] = true;
        $draft['choices']['triage_tip_offset'] = $newOffset;
        $conversation->booking_draft = $draft;
        $conversation->save();

        if ($next === []) {
            return $this->result($conversation, MobileAppAiTriageCopy::noMoreTips($originalIssue));
        }

        $issue = $originalIssue !== '' ? $originalIssue : $serviceQuery;
        $reply = $offset === 0
            ? MobileAppAiTriageCopy::issueResponse($issue, $next)
            : MobileAppAiTriageCopy::moreTipsResponse($next);

        return $this->result($conversation, $reply);
    }

    private function mergeIssues(string $original, string $additional): string
    {
        $original = trim($original);
        $additional = trim($additional);
        if ($original === '') {
            return $additional;
        }
        if ($additional === '' || mb_strtolower($additional) === mb_strtolower($original)) {
            return $original;
        }

        return $original.'; '.$additional;
    }

    public static function wantsToProceedToBooking(string $text): bool
    {
        $lower = mb_strtolower(trim($text));
        if ($lower === '') {
            return false;
        }

        if (preg_match('/\b(don\'?t book|not yet|baad me|later|abhi nahi|mat book)\b/iu', $lower)) {
            return false;
        }

        if (preg_match('/\b(proceed_booking|book_now|add to cart)\b/iu', $lower)) {
            return true;
        }

        if (MobileAppAiBookingMessageDetector::looksLikeServiceBookingRequest($text)) {
            return true;
        }

        return (bool) preg_match(
            '/\b(book|booking|schedule|visit|technician|karwana|karwao|karwa do|bhej do|bhejdo|book kar|book karo|haan book|yes book|ok book|theek book|chalo book)\b/iu',
            $lower
        ) || (bool) preg_match(
            '/\bservice\s+kar(?:ni|na|wana|wani)\b/iu',
            $lower
        );
    }

    public static function shouldStartTriage(string $text, array $draft = []): bool
    {
        $step = (string) ($draft['step'] ?? '');
        if (in_array($step, ['service_triage', 'service_query'], true)) {
            return false;
        }

        if (MobileAppAiBookingMessageDetector::looksLikeBulkBookingDetails($text)) {
            return false;
        }

        if (MobileAppAiBookingMessageDetector::hasTimeHint($text)) {
            return false;
        }

        if (self::wantsToProceedToBooking($text)) {
            return false;
        }

        if (MobileAppAiServiceIntentResolver::shouldSkipTriage($text)) {
            return false;
        }

        if (! MobileAppAiServiceQueryNormalizer::looksLikeServiceRequest($text)) {
            return false;
        }

        return mb_strlen(trim($text)) >= 3 && mb_strlen(trim($text)) <= 120;
    }

    /**
     * @param  array{key: string, ask: string, clarify: string, tips: list<string>}  $pack
     * @return list<string>
     */
    private function relevantTipLines(string $serviceQuery, string $originalIssue, array $pack): array
    {
        $lines = array_slice($pack['tips'], 0, MobileAppAiReplyStyle::MAX_TIP_LINES);

        if ($lines !== []) {
            return $lines;
        }

        $knowledge = $this->supportKnowledge->search($serviceQuery.' '.$originalIssue);
        foreach ($knowledge['troubleshooting'] ?? [] as $row) {
            if (! is_array($row)) {
                continue;
            }
            foreach ($row['steps'] ?? [] as $step) {
                $lines[] = (string) $step;
                if (count($lines) >= MobileAppAiReplyStyle::MAX_TIP_LINES) {
                    break 2;
                }
            }
        }

        return $lines;
    }

    /**
     * @return array{key: string, ask: string, clarify: string, tips: list<string>}
     */
    private function packByStoredKey(string $packKey, string $serviceQuery, string $context): array
    {
        $config = config('mobile_app_ai_service_triage', []);
        $default = is_array($config['default'] ?? null) ? $config['default'] : [];

        if ($packKey !== '' && is_array($config[$packKey] ?? null)) {
            return $this->packFromConfig($packKey, $config[$packKey], $default);
        }

        return $this->resolveServicePack($serviceQuery, $context);
    }

    /**
     * @return array{key: string, ask: string, clarify: string, tips: list<string>}
     */
    private function resolveServicePack(string $serviceQuery, string $context): array
    {
        $intent = MobileAppAiServiceIntentResolver::resolve($context);
        if ($intent['trade_id'] !== '') {
            $map = [
                'plumbing' => 'plumb',
                'electrical' => 'electric',
                'ac' => 'ac',
                'appliance' => 'appliance',
                'cleaning' => 'default',
                'carpentry' => 'default',
                'painting' => 'default',
                'pest' => 'default',
            ];
            $key = $map[$intent['trade_id']] ?? 'default';
            $config = config('mobile_app_ai_service_triage', []);
            if (is_array($config[$key] ?? null)) {
                return $this->packFromConfig($key, $config[$key], $config['default'] ?? []);
            }
        }

        $haystack = mb_strtolower($serviceQuery.' '.$context);
        $config = config('mobile_app_ai_service_triage', []);
        $default = is_array($config['default'] ?? null) ? $config['default'] : [];

        $map = [
            'ac' => ['ac', 'air condition', 'cooling', 'a/c'],
            'plumb' => ['plumb', 'pipe', 'tap', 'leak', 'geyser', 'drain'],
            'electric' => ['electric', 'wiring', 'switch', 'socket', 'mcb', 'short'],
            'appliance' => ['fridge', 'refrigerator', 'washing', 'ro ', 'tv', 'geyser'],
        ];

        foreach ($map as $key => $needles) {
            foreach ($needles as $needle) {
                if (str_contains($haystack, $needle) && is_array($config[$key] ?? null)) {
                    return $this->packFromConfig($key, $config[$key], $default);
                }
            }
        }

        return $this->packFromConfig('default', $default, $default);
    }

    /**
     * @param  array<string, mixed>  $pack
     * @param  array<string, mixed>  $default
     * @return array{key: string, ask: string, clarify: string, tips: list<string>}
     */
    private function packFromConfig(string $key, array $pack, array $default): array
    {
        return [
            'key' => $key,
            'ask' => (string) ($pack['ask'] ?? $default['ask'] ?? 'What is the problem?'),
            'clarify' => (string) ($pack['clarify'] ?? $default['clarify'] ?? $pack['ask'] ?? ''),
            'tips' => $this->tipList($pack['tips'] ?? $default['tips'] ?? []),
        ];
    }

    /**
     * @return list<string>
     */
    private function tipList(mixed $tips): array
    {
        if (! is_array($tips)) {
            return [];
        }

        return array_values(array_filter(array_map('strval', $tips)));
    }

    /**
     * @return array<string, mixed>
     */
    private function result(MobileAppAiConversation $conversation, string $reply): array
    {
        $draft = is_array($conversation->booking_draft) ? $conversation->booking_draft : [];

        return [
            'ok' => true,
            'customer_message' => $reply,
            'wizard_step' => 'service_triage',
            'ui' => $this->bookingUi->buildForDraft($draft),
        ];
    }
}
