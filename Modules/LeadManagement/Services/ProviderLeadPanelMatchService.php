<?php

namespace Modules\LeadManagement\Services;

use Illuminate\Support\Facades\Cache;
use Modules\LeadManagement\Entities\Lead;
use Modules\ProviderManagement\Entities\Provider;

class ProviderLeadPanelMatchService
{
    /**
     * Match provider leads to live panel providers by normalized phone (last 10 digits).
     *
     * @param  iterable<int, Lead>  $leads
     * @return array<int, array{id: string, name: string, url: string}>
     */
    public function matchForLeads(iterable $leads): array
    {
        $phoneMap = $this->getProviderPhoneMap();
        if ($phoneMap === []) {
            return [];
        }

        $out = [];
        foreach ($leads as $lead) {
            if (!$lead instanceof Lead) {
                continue;
            }
            $match = $this->matchPhone($lead->phone_number, $phoneMap);
            if ($match !== null) {
                $out[(int) $lead->id] = $match;
            }
        }

        return $out;
    }

    /**
     * @return array{id: string, name: string, url: string}|null
     */
    public function matchForLead(Lead $lead): ?array
    {
        $matches = $this->matchForLeads([$lead]);

        return $matches[(int) $lead->id] ?? null;
    }

    /**
     * @return array<string, array{id: string, name: string}>
     */
    public function getProviderPhoneMap(): array
    {
        $ttl = (int) config('whatsappmodule.system_phone_match_cache_ttl', 300);
        if ($ttl > 0) {
            return Cache::remember(
                'lead_provider_panel_phone_map_v1',
                $ttl,
                fn () => $this->buildProviderPhoneMap()
            );
        }

        return $this->buildProviderPhoneMap();
    }

    /**
     * @return array<string, array{id: string, name: string}>
     */
    protected function buildProviderPhoneMap(): array
    {
        $map = [];
        $rows = Provider::query()
            ->where('is_approved', 1)
            ->with(['owner:id,phone'])
            ->orderBy('id')
            ->get(['id', 'company_name', 'contact_person_name', 'company_phone', 'contact_person_phone', 'user_id']);

        foreach ($rows as $provider) {
            $displayName = (string) ($provider->company_name ?: $provider->contact_person_name ?: '');
            if ($displayName === '') {
                $displayName = translate('Provider');
            }

            $phones = [
                $provider->company_phone ?? '',
                $provider->contact_person_phone ?? '',
                $provider->owner?->phone ?? '',
            ];

            foreach ($phones as $phone) {
                $normalized = $this->normalizePhone($phone);
                if ($normalized === null || isset($map[$normalized])) {
                    continue;
                }
                $map[$normalized] = [
                    'id' => (string) $provider->id,
                    'name' => $displayName,
                ];
            }
        }

        return $map;
    }

    /**
     * @param  array<string, array{id: string, name: string}>  $phoneMap
     * @return array{id: string, name: string, url: string}|null
     */
    protected function matchPhone(?string $rawPhone, array $phoneMap): ?array
    {
        $normalized = $this->normalizePhone($rawPhone);
        if ($normalized === null) {
            return null;
        }

        $hit = $phoneMap[$normalized] ?? null;
        if ($hit === null) {
            return null;
        }

        return [
            'id' => $hit['id'],
            'name' => $hit['name'],
            'url' => route('admin.provider.details', [$hit['id'], 'web_page' => 'overview']),
        ];
    }

    /**
     * Normalize phone for cross-module matching (WhatsApp, CRM, providers).
     */
    public function normalizePhone(?string $phone): ?string
    {
        if ($phone === null || $phone === '') {
            return null;
        }

        $digits = preg_replace('/\D+/', '', $phone) ?? '';
        if ($digits === '') {
            return null;
        }

        if (str_starts_with($digits, '00')) {
            $digits = substr($digits, 2);
        }

        if (strlen($digits) === 12 && str_starts_with($digits, '91')) {
            $digits = substr($digits, 2);
        }

        if (strlen($digits) < 10) {
            return null;
        }

        return substr($digits, -10);
    }
}
