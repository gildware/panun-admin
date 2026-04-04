<?php

namespace Modules\WhatsAppModule\Services;

/**
 * Detects when customers (or the model) put a service/work-type word in the "name" field
 * — e.g. Roman Urdu "mistary" (plastering) mistaken for a person's name or normalized to "Misty".
 */
final class WhatsAppAiBookingNameHeuristics
{
    /**
     * True when the string is almost certainly a service description, not a booking contact name.
     */
    public static function looksLikeServiceNotPersonName(string $name): bool
    {
        $raw = trim($name);
        if ($raw === '') {
            return false;
        }

        $lower = mb_strtolower($raw, 'UTF-8');
        $compact = preg_replace('/[\s\-_\.]+/u', '', $lower);
        $compact = is_string($compact) ? $compact : $lower;
        if ($compact === '') {
            return false;
        }

        // ASCII-only check for levenshtein (Roman Urdu trade words)
        $ascii = preg_replace('/[^\x20-\x7E]/', '', $compact);
        $ascii = strtolower(str_replace([' ', '-', '_', '.'], '', $ascii));

        $substrings = [
            'mistary', 'mistry', 'mistri', 'mistree', 'misteri',
            'plaster', 'palester', 'palastar', 'plastar', 'plastern',
            'plumbing', 'plumber', 'plambing',
            'electrician',
            'painting',
        ];

        foreach ($substrings as $frag) {
            if (str_contains($compact, $frag)) {
                return true;
            }
        }

        // Common mis-hearings / model normalisation of "mistary"
        foreach (['misty', 'mistry', 'mistri', 'misti', 'mistery'] as $alias) {
            if ($ascii === $alias) {
                return true;
            }
        }

        $maxLen = strlen($ascii);
        if ($maxLen >= 4 && $maxLen <= 12) {
            foreach (['mistary', 'mistry', 'plaster', 'palester'] as $root) {
                if (strlen($root) <= 255 && $maxLen <= 255) {
                    $d = levenshtein($ascii, $root);
                    if ($d !== -1 && $d <= 2) {
                        return true;
                    }
                }
            }
        }

        return false;
    }
}
