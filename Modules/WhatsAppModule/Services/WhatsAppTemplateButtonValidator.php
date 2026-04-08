<?php

namespace Modules\WhatsAppModule\Services;

/**
 * Validates and builds Meta WhatsApp message template BUTTONS component from admin form rows.
 *
 * Marketing / general template limits (Meta): up to 10 buttons total; at most 2 URL and 1 phone CTA;
 * quick-reply buttons fill the remainder. URL may end with {{1}} for one dynamic path segment.
 */
class WhatsAppTemplateButtonValidator
{
    public const MAX_BUTTONS = 10;

    public const MAX_URL_BUTTONS = 2;

    public const MAX_PHONE_BUTTONS = 1;

    /**
     * @param  array<int, mixed>  $rows
     * @return array{error: ?string, component: ?array<string, mixed>}
     */
    public static function buildButtonsComponent(array $rows): array
    {
        $payloads = [];
        $rows = array_slice(array_values($rows), 0, self::MAX_BUTTONS);

        foreach ($rows as $row) {
            if (!is_array($row)) {
                continue;
            }
            $kind = strtoupper(trim((string) ($row['kind'] ?? '')));
            $text = trim((string) ($row['text'] ?? ''));
            if ($kind === '' && $text === '') {
                continue;
            }
            if ($kind === '' || $text === '') {
                return ['error' => 'Template_buttons_row_incomplete', 'component' => null];
            }
            if (!in_array($kind, ['QUICK_REPLY', 'URL', 'PHONE_NUMBER'], true)) {
                return ['error' => 'Template_button_kind_invalid', 'component' => null];
            }
            if (mb_strlen($text) > 25) {
                return ['error' => 'Template_button_text_max_25', 'component' => null];
            }

            if ($kind === 'QUICK_REPLY') {
                $payloads[] = ['type' => 'QUICK_REPLY', 'text' => $text];

                continue;
            }

            if ($kind === 'URL') {
                $url = trim((string) ($row['url'] ?? ''));
                if (!self::isValidTemplateButtonUrl($url)) {
                    return ['error' => 'Template_button_url_invalid', 'component' => null];
                }
                $payloads[] = ['type' => 'URL', 'text' => $text, 'url' => $url];

                continue;
            }

            $phone = preg_replace('/\s+/', '', (string) ($row['phone'] ?? ''));
            if ($phone === '' || !preg_match('/^\+[1-9]\d{6,14}$/', $phone)) {
                return ['error' => 'Template_button_phone_invalid', 'component' => null];
            }
            $payloads[] = ['type' => 'PHONE_NUMBER', 'text' => $text, 'phone_number' => $phone];
        }

        if ($payloads === []) {
            return ['error' => null, 'component' => null];
        }

        $urlCount = 0;
        $phoneCount = 0;
        foreach ($payloads as $p) {
            if (($p['type'] ?? '') === 'URL') {
                $urlCount++;
            }
            if (($p['type'] ?? '') === 'PHONE_NUMBER') {
                $phoneCount++;
            }
        }
        if ($urlCount > self::MAX_URL_BUTTONS) {
            return ['error' => 'Template_buttons_max_url', 'component' => null];
        }
        if ($phoneCount > self::MAX_PHONE_BUTTONS) {
            return ['error' => 'Template_buttons_max_phone', 'component' => null];
        }

        return [
            'error' => null,
            'component' => [
                'type' => 'BUTTONS',
                'buttons' => $payloads,
            ],
        ];
    }

    /**
     * Same validation as {@see buildButtonsComponent}, returns only the Meta button payloads (no BUTTONS wrapper).
     *
     * @return array{error: ?string, buttons: array<int, array<string, mixed>>}
     */
    public static function metaButtonsFromRows(array $rows): array
    {
        $built = self::buildButtonsComponent($rows);
        if ($built['error'] !== null) {
            return ['error' => $built['error'], 'buttons' => []];
        }
        $comp = $built['component'];
        if ($comp === null) {
            return ['error' => null, 'buttons' => []];
        }

        return ['error' => null, 'buttons' => $comp['buttons'] ?? []];
    }

    /**
     * HTTPS URL; optional single {{1}} placeholder only at end of URL (Meta URL button rule).
     */
    public static function isValidTemplateButtonUrl(string $url): bool
    {
        if ($url === '' || !str_starts_with($url, 'https://')) {
            return false;
        }
        if (strlen($url) > 2000) {
            return false;
        }
        if (preg_match('/\s/', $url)) {
            return false;
        }

        if (!preg_match('/^https:\/\/.+/i', $url)) {
            return false;
        }

        if (!preg_match_all('/\{\{(\d+)\}\}/', $url, $matches)) {
            return true;
        }

        if (count($matches[0]) !== 1) {
            return false;
        }
        if (($matches[1][0] ?? '') !== '1') {
            return false;
        }

        return (bool) preg_match('#\{\{1\}\}(?:/)?$#', $url);
    }
}
