<?php

namespace Modules\BusinessSettingsModule\Services;

/**
 * Map free-text (English + Roman Urdu/Hinglish) to wizard picks without requiring a button tap.
 */
final class MobileAppAiWizardChoiceInterpreter
{
    public static function meansCompanyChoosesProvider(string $text): bool
    {
        $lower = mb_strtolower(trim($text));
        if ($lower === '') {
            return false;
        }

        if (preg_match(
            '/\b(any|auto|anyone|anybody|whoever|best\s+available|no\s+preference|doesn\'?t\s+matter|you\s+(pick|choose|decide)|let\s+(you|us|panun|company)|assign|choose\s+for\s+me)\b/i',
            $lower
        )) {
            return true;
        }

        if (preg_match(
            '/\b(kisi\s*ko|koi\s*bhi|kuch\s*bhi|khud\s*hi|khud\s+se|tum\s*hi|aap\s*hi|company|panun|bejh|bhej|becho|bech\s*do|chun\s*lo|chuno|select\s*karo)\b/iu',
            $lower
        )) {
            return true;
        }

        if (str_contains($lower, 'bejhao') || str_contains($lower, 'bejho') || str_contains($lower, 'bhej do')) {
            return true;
        }

        // "send anyone" / "any provider is fine"
        return (bool) preg_match('/\b(send|sent)\s+(any|someone|anyone)\b/i', $lower);
    }

    /**
     * User is confirming a choice already shown (not asking for a new search).
     */
    public static function isReaffirmation(string $text): bool
    {
        return MobileAppAiConversationalResponder::isReaffirmation($text);
    }

    public static function meansAsapSchedule(string $text): bool
    {
        $parsed = MobileAppAiSchedulePhraseParser::parse($text);

        return ($parsed['ok'] ?? false) && ($parsed['schedule_type'] ?? '') === 'asap';
    }

    /**
     * @param  list<array<string, mixed>>  $options
     * @return array<string, mixed>|null
     */
    public static function resolveProviderOption(array $options, string $text): ?array
    {
        if (self::meansCompanyChoosesProvider($text)) {
            return self::findProviderAutoOption($options);
        }

        if (preg_match('/^\d+$/', trim($text))) {
            $n = (int) trim($text);
            foreach ($options as $o) {
                if ((int) ($o['pick'] ?? -1) === $n) {
                    return $o;
                }
            }
        }

        $lower = mb_strtolower(trim($text));
        foreach ($options as $o) {
            $pick = (int) ($o['pick'] ?? -1);
            if ($pick === 0) {
                continue;
            }
            $name = mb_strtolower(trim((string) ($o['name'] ?? '')));
            if ($name === '' || $name === 'let panun kaergar choose for you') {
                continue;
            }
            if (str_contains($lower, $name) || str_contains($name, $lower)) {
                return $o;
            }
            foreach (preg_split('/\s+/', $name) as $part) {
                if (mb_strlen($part) >= 3 && str_contains($lower, $part)) {
                    return $o;
                }
            }
        }

        return null;
    }

    /**
     * @param  list<array<string, mixed>>  $options
     * @return array<string, mixed>|null
     */
    public static function findProviderAutoOption(array $options): ?array
    {
        foreach ($options as $o) {
            if ((int) ($o['pick'] ?? -1) === 0 || ($o['provider_id'] ?? null) === null) {
                return $o;
            }
        }

        return $options[0] ?? null;
    }

    /**
     * @param  list<array<string, mixed>>  $options
     * @return array<string, mixed>|null
     */
    public static function resolveByNameOrNumber(array $options, string $text, string $labelKey = 'name'): ?array
    {
        $choice = trim($text);
        if ($choice === '') {
            return null;
        }

        if (preg_match('/^\d+$/', $choice)) {
            $n = (int) $choice;
            foreach ($options as $o) {
                if ((int) ($o['pick'] ?? -1) === $n) {
                    return $o;
                }
            }
        }

        $lower = mb_strtolower($choice);
        foreach ($options as $o) {
            $name = mb_strtolower(trim((string) ($o[$labelKey] ?? $o['name'] ?? $o['label'] ?? $o['address'] ?? '')));
            if ($name !== '' && (str_contains($name, $lower) || str_contains($lower, $name))) {
                return $o;
            }
        }

        return null;
    }
}
