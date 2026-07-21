<?php

namespace Modules\WhatsAppModule\Services;

use Carbon\Carbon;
use Modules\LeadManagement\Entities\AdSource;
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
     *     referral_body: ?string,
     *     referral_image_url: ?string,
     *     referral_platform: ?string
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
        $imageUrl = AdSource::creativeImageUrlFromReferral($referral) ?? '';
        $platform = self::detectPlatform($sourceUrl, $referral);

        if ($ctwaClid === '' && $sourceId === '' && $sourceType === '' && $sourceUrl === '' && $headline === '' && $body === '') {
            return null;
        }

        // Never treat a bare deep-link host as the ad "headline".
        if (AdSource::isBadAdName($headline)) {
            $headline = '';
        }

        return [
            'referral_json' => $referral,
            'ctwa_clid' => $ctwaClid !== '' ? mb_substr($ctwaClid, 0, 512) : null,
            'referral_source_id' => $sourceId !== '' ? mb_substr($sourceId, 0, 64) : null,
            'referral_source_type' => $sourceType !== '' ? mb_substr($sourceType, 0, 32) : null,
            'referral_source_url' => $sourceUrl !== '' ? mb_substr($sourceUrl, 0, 512) : null,
            'referral_headline' => $headline !== '' ? mb_substr($headline, 0, 512) : null,
            'referral_body' => $body !== '' ? $body : null,
            'referral_image_url' => $imageUrl !== '' ? mb_substr($imageUrl, 0, 1024) : null,
            'referral_platform' => $platform,
        ];
    }

    /**
     * @param  array<string, mixed>|null  $referral
     */
    public static function detectPlatform(?string $sourceUrl, ?array $referral = null): string
    {
        $hay = strtolower(trim((string) $sourceUrl).' '.json_encode($referral ?? []));
        if (str_contains($hay, 'instagram.com') || str_contains($hay, 'ig.me') || str_contains($hay, '"instagram"')) {
            return 'instagram';
        }
        if (
            str_contains($hay, 'facebook.com')
            || str_contains($hay, 'fb.me')
            || str_contains($hay, 'fb.com')
            || str_contains($hay, 'fb.watch')
            || str_contains($hay, 'l.facebook.com')
        ) {
            return 'facebook';
        }

        return 'whatsapp';
    }

    public static function platformLabel(string $platform): string
    {
        return match (strtolower($platform)) {
            'instagram' => 'Instagram Ad',
            'facebook' => 'Facebook Ad',
            default => 'WhatsApp Ad',
        };
    }

    /**
     * First-touch only: keep the original ad click that opened the thread.
     *
     * @param  array<string, mixed>  $attrs
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
     * @return array<string, mixed>
     */
    public function payloadForUi(?WhatsAppUser $waUser): array
    {
        $fromAd = $this->hasAdAttribution($waUser);
        $json = is_array($waUser?->referral_json) ? $waUser->referral_json : [];
        $referralImageUrl = AdSource::creativeImageUrlFromReferral($json);
        $platform = self::detectPlatform($waUser?->referral_source_url, $json);
        $headline = trim((string) ($waUser?->referral_headline ?? ''));
        $body = trim((string) ($waUser?->referral_body ?? ''));
        if (AdSource::isBadAdName($headline)) {
            $headline = '';
        }
        $bodyLine = trim((string) preg_split('/\r\n|\r|\n/', $body)[0]);
        $displayName = $headline !== ''
            ? $headline
            : (!AdSource::isBadAdName($bodyLine) && $bodyLine !== '' ? $bodyLine : null);
        if ($displayName === null || $displayName === '') {
            $sid = trim((string) ($waUser?->referral_source_id ?? ''));
            $displayName = $sid !== '' ? 'WhatsApp Ad '.$sid : self::platformLabel($platform);
        }

        $adSourceImage = null;
        $adSourceId = null;
        // Thread referral is source of truth; Ad Source only supplements when it matches this Meta ad id.
        if ($fromAd && $waUser) {
            $ad = AdSource::findByMetaSourceId($waUser->referral_source_id);
            if ($ad) {
                $adSourceId = $ad->id;
                $adSourceImage = $ad->imagePublicUrl();
                if (preg_match('/^WhatsApp Ad(\s+\d+)?$/i', (string) $displayName) && !AdSource::isBadAdName($ad->name)) {
                    $displayName = $ad->name;
                }
            }
        }

        $viewAdUrl = AdSource::viewAdUrl($waUser?->referral_source_url, $json);

        return [
            'from_ad' => $fromAd,
            'platform' => $platform,
            'platform_label' => self::platformLabel($platform),
            'ctwa_clid' => $waUser?->ctwa_clid,
            'source_id' => $waUser?->referral_source_id,
            'source_type' => $waUser?->referral_source_type,
            'source_url' => $waUser?->referral_source_url,
            'view_ad_url' => $viewAdUrl,
            'headline' => $headline !== '' ? $headline : null,
            'body' => $body !== '' ? $body : null,
            'display_name' => $displayName,
            // Prefer live Meta creative URL for this thread, then stored Ad Source image.
            'image_url' => $referralImageUrl ?: $adSourceImage,
            'ad_source_id' => $adSourceId,
            'captured_at' => $waUser?->referral_captured_at
                ? $waUser->referral_captured_at->toIso8601String()
                : null,
        ];
    }
}
