<?php

namespace Modules\WhatsAppModule\Services;

use Carbon\Carbon;
use Modules\WhatsAppModule\Entities\WhatsAppUser;
use Modules\WhatsAppModule\Support\SocialInboxChannel;

/**
 * Normalize Meta CTWA referral payloads and apply first-touch attribution on the thread.
 */
class WhatsAppCtwaAttributionService
{
    /**
     * @param  array<string, mixed>|null  $referral
     * @return array{
     *     referral_json: array<string, mixed>|null,
     *     ctwa_clid: ?string,
     *     referral_source_id: ?string,
     *     referral_source_type: ?string,
     *     referral_source_url: ?string,
     *     referral_headline: ?string,
     *     referral_body: ?string
     * }|null
     */
    public function normalizeReferral(?array $referral): ?array
    {
        if ($referral === null || $referral === []) {
            return null;
        }

        $ctwaClid = isset($referral['ctwa_clid']) ? trim((string) $referral['ctwa_clid']) : '';
        $sourceType = isset($referral['source_type']) ? trim((string) $referral['source_type']) : '';
        $sourceId = isset($referral['source_id']) ? trim((string) $referral['source_id']) : '';
        $sourceUrl = isset($referral['source_url']) ? trim((string) $referral['source_url']) : '';
        $headline = isset($referral['headline']) ? trim((string) $referral['headline']) : '';
        $body = isset($referral['body']) ? trim((string) $referral['body']) : '';

        if ($ctwaClid === '' && $sourceId === '' && $sourceType === '' && $sourceUrl === '') {
            return null;
        }

        return [
            'referral_json' => $referral,
            'ctwa_clid' => $ctwaClid !== '' ? mb_substr($ctwaClid, 0, 512) : null,
            'referral_source_id' => $sourceId !== '' ? mb_substr($sourceId, 0, 64) : null,
            'referral_source_type' => $sourceType !== '' ? mb_substr($sourceType, 0, 32) : null,
            'referral_source_url' => $sourceUrl !== '' ? mb_substr($sourceUrl, 0, 512) : null,
            'referral_headline' => $headline !== '' ? mb_substr($headline, 0, 512) : null,
            'referral_body' => $body !== '' ? $body : null,
        ];
    }

    /**
     * First-touch only: keep the original ad click that opened the thread.
     *
     * @param  array{
     *     referral_json?: array<string, mixed>|null,
     *     ctwa_clid?: ?string,
     *     referral_source_id?: ?string,
     *     referral_source_type?: ?string,
     *     referral_source_url?: ?string,
     *     referral_headline?: ?string,
     *     referral_body?: ?string
     * }  $attrs
     */
    public function applyFirstTouch(string $phone, array $attrs): WhatsAppUser
    {
        $usersTable = (new WhatsAppUser)->getTable();
        if (!\Illuminate\Support\Facades\Schema::hasColumn($usersTable, 'ctwa_clid')) {
            $waUser = WhatsAppUser::firstOrNew([
                'phone' => $phone,
                'channel' => SocialInboxChannel::current(),
            ]);
            if (empty($waUser->channel)) {
                $waUser->channel = SocialInboxChannel::current();
            }
            if (empty($waUser->handled_by)) {
                $waUser->handled_by = 'AI';
            }
            $waUser->save();

            return $waUser;
        }

        $waUser = WhatsAppUser::firstOrNew([
            'phone' => $phone,
            'channel' => SocialInboxChannel::current(),
        ]);
        if (empty($waUser->channel)) {
            $waUser->channel = SocialInboxChannel::current();
        }
        if (empty($waUser->handled_by)) {
            $waUser->handled_by = 'AI';
        }

        $alreadyAttributed = trim((string) ($waUser->ctwa_clid ?? '')) !== ''
            || trim((string) ($waUser->referral_source_id ?? '')) !== '';

        if (!$alreadyAttributed) {
            if (array_key_exists('referral_json', $attrs)) {
                $waUser->referral_json = $attrs['referral_json'];
            }
            if (!empty($attrs['ctwa_clid'])) {
                $waUser->ctwa_clid = $attrs['ctwa_clid'];
            }
            if (!empty($attrs['referral_source_id'])) {
                $waUser->referral_source_id = $attrs['referral_source_id'];
            }
            if (!empty($attrs['referral_source_type'])) {
                $waUser->referral_source_type = $attrs['referral_source_type'];
            }
            if (!empty($attrs['referral_source_url'])) {
                $waUser->referral_source_url = $attrs['referral_source_url'];
            }
            if (!empty($attrs['referral_headline'])) {
                $waUser->referral_headline = $attrs['referral_headline'];
            }
            if (!empty($attrs['referral_body'])) {
                $waUser->referral_body = $attrs['referral_body'];
            }
            $waUser->referral_captured_at = Carbon::now();
        }

        $waUser->save();

        return $waUser;
    }

    public function hasAdAttribution(?WhatsAppUser $waUser): bool
    {
        if (!$waUser) {
            return false;
        }

        return trim((string) ($waUser->ctwa_clid ?? '')) !== ''
            || strtolower(trim((string) ($waUser->referral_source_type ?? ''))) === 'ad'
            || trim((string) ($waUser->referral_source_id ?? '')) !== '';
    }

    /**
     * @return array{
     *     from_ad: bool,
     *     ctwa_clid: ?string,
     *     source_id: ?string,
     *     source_type: ?string,
     *     source_url: ?string,
     *     headline: ?string,
     *     body: ?string,
     *     captured_at: ?string
     * }
     */
    public function payloadForUi(?WhatsAppUser $waUser): array
    {
        $fromAd = $this->hasAdAttribution($waUser);

        return [
            'from_ad' => $fromAd,
            'ctwa_clid' => $waUser?->ctwa_clid,
            'source_id' => $waUser?->referral_source_id,
            'source_type' => $waUser?->referral_source_type,
            'source_url' => $waUser?->referral_source_url,
            'headline' => $waUser?->referral_headline,
            'body' => $waUser?->referral_body,
            'captured_at' => $waUser?->referral_captured_at
                ? $waUser->referral_captured_at->toIso8601String()
                : null,
        ];
    }
}
