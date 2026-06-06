<?php

namespace Modules\BusinessSettingsModule\Services;

/**
 * Detect follow-up messages during triage (already tried / still broken) vs a new problem description.
 */
final class MobileAppAiTriageFollowUp
{
    public static function isFollowUp(string $text): bool
    {
        $t = mb_strtolower(trim($text));
        if ($t === '') {
            return false;
        }

        if (MobileAppAiServiceTriageService::wantsToProceedToBooking($text)) {
            return true;
        }

        if (MobileAppAiConversationalResponder::isReaffirmation($text)) {
            return true;
        }

        if (preg_match(
            '/\b(tried|checked|done\s+that|already|everything|still\s+not|not\s+working|doesn\'?t\s+work|didn\'?t\s+work|'
            .'no\s+luck|same\s+problem|not\s+fixed|not\s+helped|kuch\s+farak|kaam\s+nahi)\b/iu',
            $t
        )) {
            return true;
        }

        return (bool) preg_match('/^(yes|haan|ha|ok|okay)\b/iu', $t)
            && ! self::describesProblem($t);
    }

    public static function describesProblem(string $text): bool
    {
        if (MobileAppAiReplyStyle::isVagueIssue($text)) {
            return false;
        }

        $resolved = MobileAppAiServiceIntentResolver::resolve($text);

        return $resolved['trade_id'] !== ''
            || MobileAppAiBookingMessageDetector::hasServiceTradeHint($text)
            || (bool) preg_match(
                '/\b(leak|leaking|tap|pipe|drain|cooling|heating|broken|spark|trip|block|drip|noise|smell)\b/iu',
                $text
            );
    }

    public static function isUsableIssueDescription(string $text): bool
    {
        $text = trim($text);

        return $text !== ''
            && self::describesProblem($text)
            && ! self::isFollowUp($text);
    }
}
