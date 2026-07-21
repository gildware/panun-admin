<?php

namespace Modules\LeadManagement\Services;

use Illuminate\Support\Collection;
use Modules\LeadManagement\Entities\AdSource;
use Modules\WhatsAppModule\Entities\WhatsAppUser;
use Modules\WhatsAppModule\Services\WhatsAppCtwaAttributionService;

/**
 * Resolve CTWA creative display for leads from the WhatsApp thread (source of truth),
 * not only from the possibly-merged Ad Source row.
 */
class LeadCtwaDisplayService
{
    public function __construct(
        protected WhatsAppCtwaAttributionService $ctwa
    ) {}

    /**
     * @param  Collection<int, object>|iterable<int, object>  $leads
     * @return array<string, array{display_name:?string,image_url:?string,view_ad_url:?string,source_id:?string}>
     *         keyed by last-10 phone digits
     */
    public function mapByLeadPhones(iterable $leads): array
    {
        $phones = [];
        foreach ($leads as $lead) {
            $digits = $this->last10((string) ($lead->phone_number ?? ''));
            if ($digits !== null) {
                $phones[$digits] = true;
            }
        }
        $phones = array_keys($phones);
        if ($phones === []) {
            return [];
        }

        $users = WhatsAppUser::query()
            ->where(function ($q) use ($phones) {
                foreach ($phones as $p) {
                    $q->orWhere('phone', 'like', '%'.$p);
                }
            })
            ->where(function ($q) {
                $q->whereNotNull('referral_json')
                    ->orWhereNotNull('ctwa_clid')
                    ->orWhereNotNull('referral_source_id')
                    ->orWhereNotNull('referral_headline');
            })
            ->get();

        $out = [];
        foreach ($users as $user) {
            $key = $this->last10((string) $user->phone);
            if ($key === null || isset($out[$key])) {
                continue;
            }
            $payload = $this->ctwa->payloadForUi($user);
            if (empty($payload['from_ad'])) {
                continue;
            }
            $out[$key] = [
                'display_name' => $payload['display_name'] ?? null,
                'image_url' => $payload['image_url'] ?? null,
                'view_ad_url' => $payload['view_ad_url'] ?? null,
                'source_id' => $payload['source_id'] ?? null,
            ];
        }

        return $out;
    }

    /**
     * @return array{display_name:?string,image_url:?string,view_ad_url:?string,source_id:?string}|null
     */
    public function forLeadPhone(?string $phone): ?array
    {
        $digits = $this->last10((string) $phone);
        if ($digits === null) {
            return null;
        }
        $map = $this->mapByLeadPhones([(object) ['phone_number' => $digits]]);

        return $map[$digits] ?? null;
    }

    /**
     * Prefer thread referral for display; fall back to Ad Source.
     *
     * @param  array{display_name:?string,image_url:?string,view_ad_url:?string,source_id:?string}|null  $ctwa
     * @return array{name:?string,image_url:?string,view_ad_url:?string}
     */
    public function resolveDisplay(?AdSource $adSource, ?array $ctwa): array
    {
        $name = null;
        $imageUrl = null;
        $viewAdUrl = null;

        if ($ctwa) {
            $name = trim((string) ($ctwa['display_name'] ?? '')) ?: null;
            $imageUrl = trim((string) ($ctwa['image_url'] ?? '')) ?: null;
            $viewAdUrl = trim((string) ($ctwa['view_ad_url'] ?? '')) ?: null;
        }

        if ($adSource) {
            if ($name === null && !AdSource::isBadAdName($adSource->name)) {
                $name = $adSource->name;
            }
            if ($imageUrl === null) {
                $imageUrl = $adSource->imagePublicUrl();
            }
            if ($viewAdUrl === null && preg_match('/meta_source_url=(\S+)/', (string) $adSource->description, $um)) {
                $viewAdUrl = AdSource::viewAdUrl(trim($um[1]));
            }
        }

        return [
            'name' => $name,
            'image_url' => $imageUrl,
            'view_ad_url' => $viewAdUrl,
        ];
    }

    protected function last10(string $phone): ?string
    {
        $digits = preg_replace('/\D+/', '', $phone) ?? '';
        if (strlen($digits) < 10) {
            return null;
        }

        return substr($digits, -10);
    }
}
