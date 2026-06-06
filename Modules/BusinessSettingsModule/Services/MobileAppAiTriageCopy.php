<?php

namespace Modules\BusinessSettingsModule\Services;

/**
 * Warm, polite copy for pre-booking triage (support agent tone).
 */
final class MobileAppAiTriageCopy
{
    public static function empathyOpening(string $issue): string
    {
        $issue = trim($issue);
        if ($issue === '' || MobileAppAiTriageFollowUp::isFollowUp($issue)) {
            return 'Sorry to hear you\'re still having trouble';
        }

        $desc = trim((string) preg_replace('/^(my|the|our|a|an)\s+/iu', '', $issue));
        $desc = trim((string) preg_replace('/\s+/', ' ', $desc));
        if ($desc === '') {
            $desc = $issue;
        }

        $lower = mb_strtolower($desc);
        if (! str_starts_with($lower, 'your ')) {
            $desc = 'your '.$desc;
        }

        if (! str_ends_with($desc, '.')) {
            $desc .= '.';
        }

        return 'Sorry to hear '.$desc;
    }

    /**
     * @param  list<string>  $tips
     */
    public static function issueResponse(string $issue, array $tips): string
    {
        $opening = self::empathyOpening($issue);
        $parts = [$opening];

        $tipBlock = MobileAppAiReplyStyle::formatTipLines($tips);
        if ($tipBlock !== '') {
            $parts[] = 'Have you tried:'."\n".$tipBlock;
        }

        $parts[] = 'I can help you troubleshoot further, or we can go ahead with booking — tap **Book this service** when you\'re ready.';

        return MobileAppAiReplyStyle::clampReply(implode("\n\n", $parts));
    }

    public static function clarifyQuestion(string $question): string
    {
        $q = trim($question);
        if ($q === '') {
            return 'Could you tell me a bit more about what\'s going on?';
        }

        return MobileAppAiReplyStyle::clampReply(
            'I\'d like to help — '.$q
        );
    }

    public static function initialAsk(string $serviceLabel, string $question): string
    {
        $label = trim($serviceLabel);
        $q = trim($question);
        if ($label !== '') {
            return MobileAppAiReplyStyle::clampReply(
                'Happy to help with **'.$label.'**. '.$q
            );
        }

        return MobileAppAiReplyStyle::clampReply($q);
    }

    public static function stillNotFixedAfterTriage(string $originalIssue): string
    {
        return MobileAppAiReplyStyle::clampReply(
            'Thanks for trying those steps. If it\'s still not resolved, a technician visit is the safest next step. Tap **Book this service** and we\'ll match the right service for you.'
        );
    }

    public static function acknowledgeTriedSteps(): string
    {
        return MobileAppAiReplyStyle::clampReply(
            'Thanks for trying those steps. If the problem continues, tap **Book this service** — we\'ll send the right technician. Need more tips first? Tap **More troubleshooting**.'
        );
    }

    /**
     * @param  list<string>  $tips
     */
    public static function additionalIssueResponse(string $issue, array $tips): string
    {
        unset($issue);
        $parts = ['Thanks for the extra detail — that helps us match the right visit.'];

        $tipBlock = MobileAppAiReplyStyle::formatTipLines($tips);
        if ($tipBlock !== '') {
            $parts[] = 'You can also try:'."\n".$tipBlock;
        }

        $parts[] = 'If it\'s still not resolved, tap **Book this service** when you\'re ready.';

        return MobileAppAiReplyStyle::clampReply(implode("\n\n", $parts));
    }

    /**
     * @param  list<string>  $tips
     */
    public static function moreTipsResponse(array $tips): string
    {
        $tipBlock = MobileAppAiReplyStyle::formatTipLines($tips);
        if ($tipBlock === '') {
            return self::acknowledgeTriedSteps();
        }

        return MobileAppAiReplyStyle::clampReply(
            "A few more things to try:\n".$tipBlock."\n\nStill stuck? Tap **Book this service** and we'll send a technician."
        );
    }

    public static function noMoreTips(string $originalIssue): string
    {
        unset($originalIssue);

        return MobileAppAiReplyStyle::clampReply(
            'Those are the main things to try at home. If the problem continues, tap **Book this service** — we\'ll match the right technician for you.'
        );
    }
}
