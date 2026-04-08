<?php

namespace Modules\WhatsAppModule\Services;

/**
 * Sends WhatsApp session messages using the same button kinds as marketing templates
 * (quick reply, URL, phone). Cloud API limits differ from templates: at most 3 quick-reply
 * buttons in one interactive "button" message; each CTA URL is a separate interactive message;
 * phone CTAs use {@see WhatsAppCloudService::sendInteractiveCtaPhone} (cta_phone), same as
 * marketing template phone buttons but without template approval.
 */
class WhatsAppSessionInteractiveSequence
{
    /**
     * @param  array<int, array<string, mixed>>  $metaButtons  Output of WhatsAppTemplateButtonValidator::buildButtonsComponent()['component']['buttons']
     * @param  list<string>|null  $quickReplyIds  Optional explicit ids for first quick replies (e.g. act_book, act_troubleshoot, act_provider for default greeting)
     * @return list<?string> Graph message ids in send order (null entries on failure)
     */
    public function send(
        WhatsAppCloudService $cloud,
        string $phone,
        string $mainBody,
        array $metaButtons,
        ?string &$error,
        ?array $quickReplyIds = null
    ): array {
        $error = null;
        $ids = [];

        $qrs = [];
        $urls = [];
        $phones = [];
        foreach ($metaButtons as $b) {
            $t = strtoupper((string) ($b['type'] ?? ''));
            if ($t === 'QUICK_REPLY') {
                $qrs[] = $b;
            } elseif ($t === 'URL') {
                $urls[] = $b;
            } elseif ($t === 'PHONE_NUMBER') {
                $phones[] = $b;
            }
        }

        $qrs = array_slice($qrs, 0, 3);
        $replyPayload = [];
        foreach ($qrs as $i => $qr) {
            $text = mb_substr(trim((string) ($qr['text'] ?? '')), 0, 20);
            if ($text === '') {
                continue;
            }
            $id = is_array($quickReplyIds) && isset($quickReplyIds[$i]) && $quickReplyIds[$i] !== ''
                ? (string) $quickReplyIds[$i]
                : 'sess_qr_'.($i + 1);
            $replyPayload[] = ['id' => $id, 'title' => $text];
        }

        $bodyTrim = trim($mainBody);
        if ($replyPayload !== []) {
            $err = null;
            $ids[] = $cloud->sendInteractiveButtons($phone, $bodyTrim !== '' ? $mainBody : ' ', $replyPayload, $err);
            if ($err !== null && $err !== '') {
                $error = $err;

                return $ids;
            }
        } elseif ($bodyTrim !== '') {
            $err = null;
            $ids[] = $cloud->sendText($phone, $mainBody, $err);
            if ($err !== null && $err !== '') {
                $error = $err;

                return $ids;
            }
        }

        foreach ($urls as $u) {
            $label = mb_substr(trim((string) ($u['text'] ?? '')), 0, 25);
            $url = self::resolveSessionUrl((string) ($u['url'] ?? ''));
            if ($label === '' || $url === '') {
                continue;
            }
            $err = null;
            $ids[] = $cloud->sendInteractiveCtaUrl($phone, ' ', $label, $url, $err);
            if ($err !== null && $err !== '') {
                $error = $err;

                return $ids;
            }
        }

        foreach ($phones as $p) {
            $label = mb_substr(trim((string) ($p['text'] ?? '')), 0, 25);
            $tel = preg_replace('/\s+/', '', (string) ($p['phone_number'] ?? ''));
            if ($label === '' || $tel === '') {
                continue;
            }
            $err = null;
            // Second message after main text / QR: minimal body, same pattern as CTA URL messages.
            $id = $cloud->sendInteractiveCtaPhone($phone, ' ', $label, $tel, $err);
            if ($id === null) {
                $errLegacy = null;
                $line = $label."\n".$tel;
                $id = $cloud->sendText($phone, $line, $errLegacy);
                if ($id === null) {
                    $error = ($errLegacy !== null && $errLegacy !== '') ? $errLegacy : $err;

                    return $ids;
                }
            }
            $ids[] = $id;
        }

        return $ids;
    }

    /**
     * Build the same quick-reply / URL / phone structure as {@see send()} without calling the Cloud API.
     * Used by the admin AI playground to render tappable buttons matching production.
     *
     * @param  array<int, array<string, mixed>>  $metaButtons
     * @param  list<string>|null  $quickReplyIds
     * @return array{quick_replies: list<array{id: string, title: string}>, urls: list<array{label: string, url: string}>, phones: list<array{label: string, phone: string}>}
     */
    public static function snapshotFromMeta(array $metaButtons, ?array $quickReplyIds = null): array
    {
        $qrs = [];
        $urls = [];
        $phones = [];
        foreach ($metaButtons as $b) {
            $t = strtoupper((string) ($b['type'] ?? ''));
            if ($t === 'QUICK_REPLY') {
                $qrs[] = $b;
            } elseif ($t === 'URL') {
                $urls[] = $b;
            } elseif ($t === 'PHONE_NUMBER') {
                $phones[] = $b;
            }
        }

        $qrs = array_slice($qrs, 0, 3);
        $quick_replies = [];
        foreach ($qrs as $i => $qr) {
            $title = mb_substr(trim((string) ($qr['text'] ?? '')), 0, 20);
            if ($title === '') {
                continue;
            }
            $id = is_array($quickReplyIds) && isset($quickReplyIds[$i]) && $quickReplyIds[$i] !== ''
                ? (string) $quickReplyIds[$i]
                : 'sess_qr_'.($i + 1);
            $quick_replies[] = ['id' => $id, 'title' => $title];
        }

        $url_out = [];
        foreach ($urls as $u) {
            $label = mb_substr(trim((string) ($u['text'] ?? '')), 0, 25);
            $url = self::resolveSessionUrl((string) ($u['url'] ?? ''));
            if ($label !== '' && $url !== '') {
                $url_out[] = ['label' => $label, 'url' => $url];
            }
        }

        $phone_out = [];
        foreach ($phones as $p) {
            $label = trim((string) ($p['text'] ?? ''));
            $tel = preg_replace('/\s+/', '', (string) ($p['phone_number'] ?? ''));
            if ($label !== '' && $tel !== '') {
                $phone_out[] = ['label' => $label, 'phone' => $tel];
            }
        }

        return [
            'quick_replies' => $quick_replies,
            'urls' => $url_out,
            'phones' => $phone_out,
        ];
    }

    /**
     * Template URLs may end with {{1}}; session CTA requires a resolved https URL — strip the placeholder.
     */
    public static function resolveSessionUrl(string $url): string
    {
        $url = trim($url);
        if ($url === '') {
            return '';
        }
        $url = preg_replace('#\{\{1\}\}(?:/)?$#', '', $url) ?? $url;

        return trim($url);
    }
}
