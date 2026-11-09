<?php

namespace Modules\ProviderManagement\Services;

use Illuminate\Support\Collection;
use Modules\LeadManagement\Entities\Lead;
use Modules\LeadManagement\Entities\PartyAlternatePhone;
use Modules\ProviderManagement\Entities\Provider;
use Modules\ProviderManagement\Entities\ProviderRegistrationDraft;
use Modules\WhatsAppModule\Entities\ProviderLead as WhatsAppProviderLead;
use Throwable;

class WhatsAppProviderMatchService
{
    public function sourcePath(): string
    {
        return dirname(__DIR__).'/Data/whatsapp_provider_numbers.json';
    }

    /**
     * @return array{rows: array<int, array<string, mixed>>, stats: array<string, int>, categories: array<int, string>}
     */
    public function build(): array
    {
        $byCategory = $this->loadSourceNumbers();
        $phoneSet = [];
        foreach ($byCategory as $phones) {
            foreach ($phones as $phone) {
                $phoneSet[$phone] = true;
            }
        }

        $providerByPhone = $this->indexProviders(array_keys($phoneSet));
        $leadByPhone = $this->indexLeads(array_keys($phoneSet));
        $waByPhone = $this->indexWhatsAppLeads(array_keys($phoneSet));
        $draftByPhone = $this->indexDrafts(array_keys($phoneSet));

        $rows = [];
        foreach ($byCategory as $category => $phones) {
            $categoryRows = [];
            foreach ($phones as $phone) {
                $providers = $providerByPhone[$phone] ?? [];
                $list = array_values(array_filter($providers, fn ($p) => (int) $p['is_approved'] === 1));
                $onboarding = array_values(array_filter($providers, fn ($p) => (int) $p['is_approved'] === 2));
                $other = array_values(array_filter($providers, fn ($p) => ! in_array((int) $p['is_approved'], [1, 2], true)));

                $leads = array_merge(
                    $leadByPhone[$phone] ?? [],
                    $waByPhone[$phone] ?? [],
                    $draftByPhone[$phone] ?? []
                );

                $inList = $list !== [];
                $inOnboarding = $onboarding !== [];
                $inLead = $leads !== [];
                $hasAny = $inList || $inOnboarding || $inLead || $other !== [];

                $categoryRows[] = [
                    'phone' => $phone,
                    'category' => $category,
                    'provider_profile' => array_merge($list, $other),
                    'onboarding' => $onboarding,
                    'provider_lead' => $leads,
                    'in_list' => $inList,
                    'in_onboarding' => $inOnboarding,
                    'in_lead' => $inLead,
                    'has_any' => $hasAny,
                ];
            }

            usort($categoryRows, function (array $a, array $b) {
                if ($a['has_any'] !== $b['has_any']) {
                    return $a['has_any'] ? -1 : 1;
                }

                return strcmp($a['phone'], $b['phone']);
            });

            array_push($rows, ...$categoryRows);
        }

        $stats = [
            'unique_phones' => count($phoneSet),
            'rows' => count($rows),
            'matched' => count(array_filter($rows, fn ($r) => $r['has_any'])),
            'in_list' => count(array_filter($rows, fn ($r) => $r['in_list'])),
            'in_onboarding' => count(array_filter($rows, fn ($r) => $r['in_onboarding'])),
            'in_lead' => count(array_filter($rows, fn ($r) => $r['in_lead'])),
            'none' => count(array_filter($rows, fn ($r) => ! $r['has_any'])),
        ];

        return [
            'rows' => $rows,
            'stats' => $stats,
            'categories' => array_keys($byCategory),
        ];
    }

    /**
     * @return array<string, array<int, string>>
     */
    private function loadSourceNumbers(): array
    {
        $path = $this->sourcePath();
        if (! is_file($path)) {
            return [];
        }

        $decoded = json_decode((string) file_get_contents($path), true);
        if (! is_array($decoded)) {
            return [];
        }

        $out = [];
        foreach ($decoded as $category => $phones) {
            if (! is_array($phones)) {
                continue;
            }
            $clean = [];
            $seen = [];
            foreach ($phones as $phone) {
                $digits = $this->last10($phone);
                if ($digits === null || isset($seen[$digits])) {
                    continue;
                }
                $seen[$digits] = true;
                $clean[] = $digits;
            }
            $out[(string) $category] = $clean;
        }

        return $out;
    }

    /**
     * @param  array<int, string>  $phones
     * @return array<string, array<int, array<string, mixed>>>
     */
    private function indexProviders(array $phones): array
    {
        $wanted = array_fill_keys($phones, true);
        $indexed = [];

        Provider::query()
            ->with(['owner:id,phone,provider_admin_phone_digits'])
            ->select([
                'id',
                'user_id',
                'company_name',
                'contact_person_name',
                'company_phone',
                'contact_person_phone',
                'contact_phone_digits',
                'is_approved',
                'is_active',
            ])
            ->whereNull('deleted_at')
            ->chunkById(200, function (Collection $chunk) use (&$indexed, $wanted) {
                foreach ($chunk as $provider) {
                    $candidates = [
                        $this->last10($provider->company_phone),
                        $this->last10($provider->contact_person_phone),
                        $this->last10($provider->contact_phone_digits),
                        $this->last10($provider->owner?->phone),
                        $this->last10($provider->owner?->provider_admin_phone_digits),
                    ];
                    $matched = [];
                    foreach ($candidates as $candidate) {
                        if ($candidate !== null && isset($wanted[$candidate])) {
                            $matched[$candidate] = true;
                        }
                    }
                    if ($matched === []) {
                        continue;
                    }

                    $info = [
                        'id' => (string) $provider->id,
                        'name' => (string) ($provider->company_name ?: $provider->contact_person_name ?: 'Unnamed'),
                        'is_approved' => (int) $provider->is_approved,
                        'is_active' => (int) $provider->is_active === 1,
                        'kind' => 'provider',
                    ];

                    foreach (array_keys($matched) as $phone) {
                        $indexed[$phone][] = $info;
                    }
                }
            });

        return $indexed;
    }

    /**
     * @param  array<int, string>  $phones
     * @return array<string, array<int, array<string, mixed>>>
     */
    private function indexLeads(array $phones): array
    {
        $wanted = array_fill_keys($phones, true);
        $indexed = [];

        Lead::query()
            ->select(['id', 'name', 'phone_number', 'lead_type', 'lead_temperature', 'hunting_status'])
            ->whereNull('deleted_at')
            ->chunkById(500, function (Collection $chunk) use (&$indexed, $wanted) {
                foreach ($chunk as $lead) {
                    $phone = $this->last10($lead->phone_number);
                    if ($phone === null || ! isset($wanted[$phone])) {
                        continue;
                    }
                    $indexed[$phone][] = $this->formatLead($lead, 'primary');
                }
            });

        try {
            PartyAlternatePhone::query()
                ->where('owner_type', PartyAlternatePhone::OWNER_PROVIDER_LEAD)
                ->select(['id', 'phone_digits', 'owner_id'])
                ->chunkById(500, function (Collection $chunk) use (&$indexed, $wanted) {
                    $ownerIds = $chunk->pluck('owner_id')->filter()->unique()->values()->all();
                    if ($ownerIds === []) {
                        return;
                    }
                    $leads = Lead::query()
                        ->whereIn('id', $ownerIds)
                        ->whereNull('deleted_at')
                        ->get(['id', 'name', 'lead_type', 'lead_temperature', 'hunting_status'])
                        ->keyBy('id');

                    foreach ($chunk as $alt) {
                        $phone = $this->last10($alt->phone_digits);
                        if ($phone === null || ! isset($wanted[$phone])) {
                            continue;
                        }
                        $lead = $leads->get($alt->owner_id);
                        if (! $lead) {
                            continue;
                        }
                        if (collect($indexed[$phone] ?? [])->contains(fn ($row) => ($row['id'] ?? null) === (string) $lead->id)) {
                            continue;
                        }
                        $indexed[$phone][] = $this->formatLead($lead, 'alternate');
                    }
                });
        } catch (Throwable) {
            // Alternate-phone table may be unavailable on older DBs.
        }

        return $indexed;
    }

    /**
     * @param  array<int, string>  $phones
     * @return array<string, array<int, array<string, mixed>>>
     */
    private function indexWhatsAppLeads(array $phones): array
    {
        $wanted = array_fill_keys($phones, true);
        $indexed = [];

        try {
            foreach (WhatsAppProviderLead::query()->select(['lead_id', 'phone', 'name', 'service', 'status'])->get() as $lead) {
                $phone = $this->last10($lead->phone);
                if ($phone === null || ! isset($wanted[$phone])) {
                    continue;
                }
                $indexed[$phone][] = [
                    'id' => (string) $lead->lead_id,
                    'name' => (string) ($lead->name ?: $lead->lead_id),
                    'label' => trim('WA draft: '.($lead->name ?: $lead->lead_id).' ['.($lead->status ?: '').'] '.($lead->service ?: '')),
                    'kind' => 'whatsapp',
                    'url' => null,
                ];
            }
        } catch (Throwable) {
            // WhatsApp lead table optional.
        }

        return $indexed;
    }

    /**
     * @param  array<int, string>  $phones
     * @return array<string, array<int, array<string, mixed>>>
     */
    private function indexDrafts(array $phones): array
    {
        $wanted = array_fill_keys($phones, true);
        $indexed = [];

        try {
            foreach (ProviderRegistrationDraft::query()->select(['id', 'phone', 'current_step', 'provider_type'])->get() as $draft) {
                $phone = $this->last10($draft->phone);
                if ($phone === null || ! isset($wanted[$phone])) {
                    continue;
                }
                $indexed[$phone][] = [
                    'id' => (string) $draft->id,
                    'name' => 'Registration draft',
                    'label' => 'Reg draft step '.($draft->current_step ?: '?').' ('.($draft->provider_type ?: 'n/a').')',
                    'kind' => 'draft',
                    'url' => null,
                ];
            }
        } catch (Throwable) {
            // Draft table optional.
        }

        return $indexed;
    }

    private function formatLead(Lead $lead, string $via): array
    {
        $bits = array_values(array_filter([
            $lead->lead_type ?: null,
            $lead->lead_temperature ?: null,
            $via === 'alternate' ? 'alt phone' : null,
        ]));

        $name = (string) ($lead->name ?: 'Unnamed');

        return [
            'id' => (string) $lead->id,
            'name' => $name,
            'label' => $bits === [] ? $name : $name.' ('.implode(', ', $bits).')',
            'kind' => 'lead',
            'url' => route('admin.lead.show', $lead->id),
        ];
    }

    private function last10(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }
        $digits = preg_replace('/\D+/', '', (string) $value) ?? '';
        if ($digits === '') {
            return null;
        }
        if (strlen($digits) >= 10) {
            return substr($digits, -10);
        }

        return strlen($digits) >= 8 ? $digits : null;
    }
}
