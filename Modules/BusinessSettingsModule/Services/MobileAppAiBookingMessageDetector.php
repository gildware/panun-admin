<?php

namespace Modules\BusinessSettingsModule\Services;

/**
 * Detect booking intent and route free-text to the booking wizard without Gemini.
 */
final class MobileAppAiBookingMessageDetector
{
    /** @var list<string> */
    private const BOOKING_INTENT_PHRASES = [
        'book a service', 'book service', 'i want to book', 'need to book', 'help me book',
        'make a booking', 'schedule a service', 'new booking', 'book an', 'book my',
        'want booking', 'need service', 'get service', 'arrange service', 'home service',
        'service chahiye', 'booking karni', 'book karo', 'book kar do',
        'want technician', 'need technician', 'technician chahiye', 'technician chaiye',
        'service hi chahiye', 'service hi chaiye', 'bola na service', 'bas service chahiye',
        'service karni hai', 'service karwani hai', 'service karna hai', 'booking karni hai',
    ];

    /**
     * Customer insists they want to book (often after triage or a misunderstanding).
     */
    public static function looksLikeServiceBookingRequest(string $text): bool
    {
        $lower = mb_strtolower(trim($text));

        if (preg_match(
            '/\b(?:bola\s+na|bas|sirf|only|mujhe|mujhay|mukhya|mujhay)\b.*\b(?:service\s+(?:chahiye|chaiye|hi)|book|booking)\b/iu',
            $lower
        )) {
            return true;
        }

        if (preg_match(
            '/\b(?:nahi|no|nah)\b.*\b(?:service\s+(?:hi\s+)?(?:chahiye|chaiye)|book|booking)\b/iu',
            $lower
        )) {
            return true;
        }

        if (preg_match(
            '/\bservice\s+(?:hi\s+)?(?:chahiye|chaiye)\b/iu',
            $lower
        ) || str_contains($lower, 'service chahiye')) {
            return true;
        }

        if (preg_match(
            '/\bservice\s+kar(?:ni|na|wana|wani)\b/iu',
            $lower
        ) || preg_match(
            '/\b(?:book(?:ing)?|service)\s+kar(?:ni|na|wana|wani)\s+hai\b/iu',
            $lower
        )) {
            return true;
        }

        if (preg_match('/\bbola\s+na\b/iu', $lower) && mb_strlen($lower) <= 24) {
            return true;
        }

        return false;
    }

    public static function looksLikeTechnicianRequest(string $text): bool
    {
        $lower = mb_strtolower(trim($text));

        return (bool) preg_match(
            '/\b(?:want|need|chahiye|chaiye|book)\b.*\b(?:technician|mistri|mistry|mason|worker)\b/iu',
            $lower
        ) || (bool) preg_match('/\b(?:technician|mistri)\b/iu', $lower);
    }

    /** @var list<string> */
    private const SERVICE_TRADE_HINTS = [
        'plumb', 'electric', 'carpent', 'paint', 'clean', 'ac repair', 'air condition',
        'appliance', 'pest', 'garden', 'mason', 'mistar', 'mistry', 'mistri', 'plaster',
        'welding', 'generator', 'solar', 'geyser', 'ro service', 'water tank', 'tiling',
        'flooring', 'shifting', 'packers', 'cctv', 'security', 'cook', 'chef', 'driver',
        'babysit', 'tutor', 'salon', 'beauty', 'hair', 'laundry', 'dry clean',
    ];

    /** @var list<string> */
    private const ACTIVE_WIZARD_STEPS = [
        'service_triage', 'service_query', 'service_confirm', 'service', 'variation', 'schedule', 'address', 'provider', 'ready',
    ];

    public static function isActiveBookingWizardStep(?string $step): bool
    {
        return in_array((string) $step, self::ACTIVE_WIZARD_STEPS, true);
    }

    /**
     * Customer gave enough detail to try action=apply in one shot (with or without the word "book").
     */
    public static function looksLikeBulkBookingDetails(string $text): bool
    {
        $trimmed = trim($text);
        if (mb_strlen($trimmed) < 10) {
            return false;
        }

        if (self::looksLikeBookingWithDetails($trimmed)) {
            return true;
        }

        $hasTime = self::hasTimeHint($trimmed);
        $hasAddress = self::hasAddressHint($trimmed);
        $hasService = self::hasServiceTradeHint($trimmed);
        $hasBookingIntent = self::hasBookingIntent($trimmed);

        if ($hasService && $hasTime) {
            return true;
        }

        if ($hasService && $hasAddress) {
            return true;
        }

        if ($hasBookingIntent && ($hasTime || $hasService || $hasAddress)) {
            return true;
        }

        if ($hasTime && $hasAddress && mb_strlen($trimmed) >= 20) {
            return true;
        }

        return false;
    }

    /**
     * @deprecated alias — use looksLikeBulkBookingDetails
     */
    public static function looksLikeBookingWithDetails(string $text): bool
    {
        $trimmed = trim($text);
        if (mb_strlen($trimmed) < 12) {
            return false;
        }

        $lower = mb_strtolower($trimmed);
        $hasBookingIntent = self::hasBookingIntent($lower);
        $hasTime = self::hasTimeHint($trimmed);

        if ($hasBookingIntent && $hasTime) {
            return true;
        }

        return str_contains($lower, 'book') && $hasTime && (self::hasServiceTradeHint($trimmed) || str_contains($lower, 'service'));
    }

    /**
     * Prefer server-side booking wizard over Gemini for this message.
     */
    public static function shouldTryRuleBasedApply(string $text, ?array $draft = null): bool
    {
        if (self::looksLikeBulkBookingDetails($text)) {
            return true;
        }

        $step = is_array($draft) ? (string) ($draft['step'] ?? '') : '';
        if ($step === 'service_query' && mb_strlen(trim($text)) >= 2) {
            return true;
        }

        if ($step === 'schedule' && mb_strlen(trim($text)) >= 2) {
            return true;
        }

        if (self::isActiveBookingWizardStep($step) && self::resolveWizardPayload($text, $draft ?? []) !== null) {
            return true;
        }

        if ($step === 'service_triage' && mb_strlen(trim($text)) >= 2) {
            return true;
        }

        if (($step === 'idle' || $step === '') && MobileAppAiServiceIntentResolver::shouldSkipTriage($text)) {
            return true;
        }

        return false;
    }

    public static function looksLikeBookingCountQuery(string $text): bool
    {
        $t = mb_strtolower(trim($text));

        return (bool) preg_match(
            '/\b(?:how many|how much|kitne|number of|count of|total)\b.*\b(?:booking|bookings|order|orders)\b/iu',
            $t
        ) || (bool) preg_match(
            '/\b(?:booking|bookings|order)\b.*\b(?:how many|do i have|i have|mere pass|mera)\b/iu',
            $t
        ) || (bool) preg_match(
            '/\b(?:meri|mere)\s+(?:booking|bookings)\s+(?:kitni|kitne)\b/iu',
            $t
        );
    }

    public static function looksLikeBookingStatusQuery(string $text): bool
    {
        if (self::looksLikeBookingCountQuery($text)) {
            return true;
        }

        $t = mb_strtolower(trim($text));

        return (bool) preg_match(
            '/\b(booking status|my booking|my bookings|order status|track booking|where is my booking|status of (?:my )?booking|check (?:my )?booking|booking update|list (?:my )?bookings|show (?:my )?bookings)\b/i',
            $t
        );
    }

    public static function looksLikeAppTroubleshoot(string $text): bool
    {
        $t = mb_strtolower(trim($text));

        if (MobileAppAiServiceQueryNormalizer::looksLikeProblemOrService($text)) {
            return false;
        }

        if (MobileAppAiServiceIntentResolver::resolve($text)['unsupported'] !== null) {
            return false;
        }

        return (bool) preg_match(
            '/\b(otp|payment failed|payment issue|cart empty|cart problem|cannot pay|checkout|sign in|sign-in|login|log in|address not|not in zone|provider did not|no show|refund|cancel booking|notification|help with app|app (?:is )?not)\b/i',
            $t
        ) || (bool) preg_match('/\b(not working|problem with|issue with)\b/i', $t)
            && ! MobileAppAiServiceIntentResolver::resolve($text)['trade_id'];
    }

    /**
     * Short message naming a trade/category (e.g. "plumber", "AC repair").
     */
    public static function looksLikeServiceSearchOnly(string $text): bool
    {
        $t = mb_strtolower(trim($text));
        if ($t === '' || mb_strlen($t) > 80) {
            return false;
        }

        if (self::hasBookingIntent($t) || self::hasTimeHint($text)) {
            return false;
        }

        if (preg_match('/^\d+$/', $t)) {
            return false;
        }

        return self::hasServiceTradeHint($t)
            || MobileAppAiServiceQueryNormalizer::looksLikeServiceRequest($text);
    }

    public static function hasTimeHint(string $text): bool
    {
        return (bool) preg_match(
            '/\b(asap|as soon|earliest|jaldi|abhi|turant|foran|aaj|kal|parson|today|tomorrow|day after tomorrow|day after|tonight|subah|sham|dopahar|raat|baje|bje|bajey|morning|afternoon|evening|next week|monday|tuesday|wednesday|thursday|friday|saturday|sunday|\d{1,2}:\d{2}|\d{1,2}\s*(?:am|pm)|at\s+\d{1,2}(?::\d{2})?\s*(?:am|pm)?)\b/i',
            $text
        );
    }

    public static function hasAddressHint(string $text): bool
    {
        if (preg_match('/\bat\s+[a-zA-Z][a-zA-Z0-9\s\-]{2,}/iu', $text)) {
            return true;
        }

        return (bool) preg_match(
            '/\b(rajbagh|srinagar|baramulla|anantnag|budgam|ganderbal|pulwama|shopian|kulgam|kupwara|bandipora|jammu|address|home|house|flat|apartment|colony|road|street|lane|near)\b/i',
            $text
        );
    }

    public static function hasServiceTradeHint(string $text): bool
    {
        $lower = mb_strtolower($text);
        foreach (self::SERVICE_TRADE_HINTS as $hint) {
            if (str_contains($lower, $hint)) {
                return true;
            }
        }

        return (bool) preg_match(
            '/\b(ac|a\/c|air\s*condition|fridge|refrigerator|geyser|washing\s*machine|ro\b|tv\b)\b/i',
            $text
        );
    }

    public static function hasBookingIntent(string $text): bool
    {
        if (self::looksLikeBookingStatusQuery($text)) {
            return false;
        }

        if (MobileAppAiCartScheduleReply::looksLikeCartScheduleQuery($text)) {
            return false;
        }

        if (self::looksLikeServiceBookingRequest($text)) {
            return true;
        }

        $lower = mb_strtolower(trim($text));
        foreach (self::BOOKING_INTENT_PHRASES as $phrase) {
            if (str_contains($lower, $phrase)) {
                return true;
            }
        }

        if (preg_match('/\b(book|booking)\b/i', $lower)) {
            return true;
        }

        // "schedule a service" — not "what is the schedule date"
        return (bool) preg_match(
            '/\bschedule\s+(?:a|an|my|the|new|home|service|booking)\b/i',
            $lower
        );
    }

    /**
     * Map free-text to a booking wizard action while a draft is in progress.
     *
     * @param  array<string, mixed>  $draft
     * @return array<string, mixed>|null
     */
    public static function resolveWizardPayload(string $text, array $draft): ?array
    {
        $step = (string) ($draft['step'] ?? '');
        $t = trim($text);
        if ($t === '' || ! self::isActiveBookingWizardStep($step)) {
            return null;
        }

        if ($step === 'ready') {
            if (self::isAffirmative($t)) {
                return ['action' => 'confirm'];
            }
            if (self::isNegative($t)) {
                return ['action' => 'cancel'];
            }

            return null;
        }

        if ($step === 'schedule') {
            if (mb_strlen($t) >= 2) {
                return ['action' => 'time', 'when' => $t, 'message' => $t];
            }

            return null;
        }

        if ($step === 'provider') {
            return ['action' => 'pick', 'choice' => $t, 'message' => $t];
        }

        if ($step === 'service_triage') {
            if (self::looksLikeServiceBookingRequest($t)
                || MobileAppAiServiceTriageService::wantsToProceedToBooking($t)) {
                return ['action' => 'proceed_booking'];
            }

            return ['action' => 'triage_issue', 'message' => $t];
        }

        if ($step === 'service_query') {
            if (self::looksLikeBulkBookingDetails($t)) {
                return ['action' => 'apply', 'message' => $t];
            }

            $query = MobileAppAiServiceQueryNormalizer::normalize($t);
            if ($query === '') {
                $query = trim($t);
            }

            return [
                'action' => 'search',
                'query' => $query,
                'message' => $t,
            ];
        }

        if ($step === 'service_confirm') {
            if (self::isAffirmative($t) || self::wantsProceedServiceConfirm($t)) {
                return ['action' => 'confirm_service'];
            }
            if (self::isNegative($t)) {
                return ['action' => 'show_service_options'];
            }

            return ['action' => 'pick', 'choice' => $t, 'message' => $t];
        }

        if ($step === 'address') {
            return ['action' => 'pick', 'choice' => $t, 'message' => $t];
        }

        if (in_array($step, ['service', 'variation'], true)) {
            return ['action' => 'pick', 'choice' => $t, 'message' => $t];
        }

        return null;
    }

    public static function isAffirmative(string $text): bool
    {
        $lower = mb_strtolower(trim($text));

        return (bool) preg_match(
            '/^(yes|yeah|yep|yup|ok|okay|correct|right|confirm|done|sure|haan|ha|theek|thik|sahi|bilkul)\b/i',
            $lower
        ) || str_contains($lower, 'looks good') || str_contains($lower, 'add to cart');
    }

    /**
     * Short confusion / "what?" — not a service description or booking refusal.
     */
    public static function looksLikeConfusionQuestion(string $text): bool
    {
        $lower = mb_strtolower(trim($text));
        if ($lower === '') {
            return false;
        }

        if (self::looksLikeServiceBookingRequest($text) || self::isAffirmative($text) || self::isNegative($text)) {
            return false;
        }

        return (bool) preg_match(
            '/^(?:kya|kyaa|kyu|kyun|kaise|kaisa|matlab|samjha|samjhe|what|huh|pardon|sorry)\??$/iu',
            $lower
        ) || (bool) preg_match('/^\?+$/u', $lower)
            || (bool) preg_match('/\b(?:kya\s+bola|kya\s+matlab|what\s+do\s+you\s+mean)\b/iu', $lower);
    }

    public static function isNegative(string $text): bool
    {
        $lower = mb_strtolower(trim($text));

        return (bool) preg_match('/^(no|nope|cancel|stop|wrong|change)\b/i', $lower);
    }

    public static function wantsProceedServiceConfirm(string $text): bool
    {
        $lower = mb_strtolower(trim($text));

        return str_contains($lower, 'confirm_service')
            || str_contains($lower, 'book this')
            || str_contains($lower, 'yes book');
    }

    /**
     * @param  list<string>  $needles
     */
    private static function containsAny(string $haystack, array $needles): bool
    {
        foreach ($needles as $n) {
            if (str_contains($haystack, $n)) {
                return true;
            }
        }

        return false;
    }
}
