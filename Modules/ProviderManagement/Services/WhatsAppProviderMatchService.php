<?php

namespace Modules\ProviderManagement\Services;

use Illuminate\Support\Collection;
use Modules\LeadManagement\Entities\Lead;
use Modules\LeadManagement\Entities\PartyAlternatePhone;
use Modules\LeadManagement\Services\PartyPhoneDirectory;
use Modules\ProviderManagement\Entities\Provider;
use Modules\ProviderManagement\Entities\ProviderRegistrationDraft;
use Modules\WhatsAppModule\Entities\ProviderLead as WhatsAppProviderLead;
use Throwable;

class WhatsAppProviderMatchService
{
    public function __construct(
        private readonly PartyPhoneDirectory $phoneDirectory
    ) {
    }

    public function sourcePath(): string
    {
        return dirname(__DIR__).'/Data/whatsapp_provider_numbers.json';
    }

    /**
     * Parse pasted/typed phone input into unique last-10 digit numbers (order preserved).
     * Accepts newlines/commas/semicolons as separators, and keeps spaced numbers like +91 88250 59511.
     *
     * @return list<string>
     */
    public function parsePhones(string $raw): array
    {
        $out = [];
        $seen = [];

        // Prefer phone-shaped tokens (digits with optional + / spaces / dashes / parens).
        if (preg_match_all('/\+?\d[\d\s\-()]{6,}\d/', $raw, $matches) && ! empty($matches[0])) {
            foreach ($matches[0] as $part) {
                $digits = $this->last10($part);
                if ($digits === null || isset($seen[$digits])) {
                    continue;
                }
                $seen[$digits] = true;
                $out[] = $digits;
            }

            return $out;
        }

        // Fallback: split on newlines / commas / semicolons / pipes (not spaces).
        $parts = preg_split('/[\n\r,;|]+/', $raw) ?: [];
        foreach ($parts as $part) {
            $digits = $this->last10($part);
            if ($digits === null || isset($seen[$digits])) {
                continue;
            }
            $seen[$digits] = true;
            $out[] = $digits;
        }

        return $out;
    }

    /**
     * Lookup arbitrary phone numbers against providers, onboarding, leads, and profile relations.
     *
     * @param  list<string>  $phones
     * @return array{results: list<array<string, mixed>>, stats: array<string, int>, phones: list<string>}
     */
    public function lookup(array $phones): array
    {
        $phones = array_values(array_unique(array_filter(array_map(fn ($p) => $this->last10($p), $phones))));
        if ($phones === []) {
            return [
                'results' => [],
                'stats' => ['searched' => 0, 'matched' => 0, 'none' => 0],
                'phones' => [],
            ];
        }

        $providerByPhone = $this->indexProviders($phones);
        $leadByPhone = $this->indexLeads($phones);
        $waByPhone = $this->indexWhatsAppLeads($phones);
        $draftByPhone = $this->indexDrafts($phones);

        $results = [];
        foreach ($phones as $phone) {
            $providers = array_map(
                fn (array $p) => $this->enrichProvider($p),
                $providerByPhone[$phone] ?? []
            );
            $list = array_values(array_filter($providers, fn ($p) => (int) $p['is_approved'] === 1));
            $onboarding = array_values(array_filter($providers, fn ($p) => (int) $p['is_approved'] === 2));
            $other = array_values(array_filter($providers, fn ($p) => ! in_array((int) $p['is_approved'], [1, 2], true)));

            $leads = array_merge(
                $leadByPhone[$phone] ?? [],
                $waByPhone[$phone] ?? [],
                $draftByPhone[$phone] ?? []
            );

            $relations = $this->buildRelations($phone, $providers, $leads);

            $hasAny = $list !== [] || $onboarding !== [] || $other !== [] || $leads !== [] || $relations !== [];

            $results[] = [
                'phone' => $phone,
                'provider_list' => $list,
                'onboarding' => $onboarding,
                'other_providers' => $other,
                'leads' => $leads,
                'relations' => $relations,
                'in_list' => $list !== [],
                'in_onboarding' => $onboarding !== [],
                'in_lead' => $leads !== [],
                'has_any' => $hasAny,
            ];
        }

        return [
            'results' => $results,
            'stats' => [
                'searched' => count($results),
                'matched' => count(array_filter($results, fn ($r) => $r['has_any'])),
                'none' => count(array_filter($results, fn ($r) => ! $r['has_any'])),
            ],
            'phones' => $phones,
        ];
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
                        'contact_person_name' => (string) ($provider->contact_person_name ?: ''),
                        'company_phone' => (string) ($provider->company_phone ?: ''),
                        'contact_person_phone' => (string) ($provider->contact_person_phone ?: ''),
                        'owner_phone' => (string) ($provider->owner?->phone ?: ''),
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
     * @param  array<string, mixed>  $provider
     * @return array<string, mixed>
     */
    private function enrichProvider(array $provider): array
    {
        $approved = (int) ($provider['is_approved'] ?? 0);
        $provider['status_label'] = match ($approved) {
            1 => 'Provider list',
            2 => 'Onboarding',
            0 => 'Denied',
            default => 'Status '.$approved,
        };
        $provider['url'] = $approved === 2
            ? route('admin.provider.onboarding_details', $provider['id'])
            : route('admin.provider.details', [$provider['id'], 'web_page' => 'overview']);

        return $provider;
    }

    /**
     * @param  list<array<string, mixed>>  $providers
     * @param  list<array<string, mixed>>  $leads
     * @return list<array<string, mixed>>
     */
    private function buildRelations(string $phone, array $providers, array $leads): array
    {
        $relations = [];
        $seen = [];

        try {
            $owner = $this->phoneDirectory->findOwner($phone);
        } catch (Throwable) {
            $owner = null;
        }

        if ($owner) {
            $key = $owner['owner_type'].':'.$owner['owner_id'];
            $seen[$key] = true;
            $relatedPhones = [];
            try {
                $relatedPhones = $this->phoneDirectory->phonesForOwner($owner['owner_type'], $owner['owner_id']);
            } catch (Throwable) {
                $relatedPhones = [];
            }

            $relations[] = [
                'type' => $owner['owner_type'],
                'id' => $owner['owner_id'],
                'label' => $owner['label'],
                'via' => $owner['via'] ?? 'primary',
                'related_phones' => array_values(array_filter($relatedPhones, fn ($p) => $p !== $phone)),
                'url' => $this->relationUrl($owner['owner_type'], $owner['owner_id']),
                'note' => 'Directory owner for this number',
            ];
        }

        foreach ($providers as $provider) {
            $key = PartyAlternatePhone::OWNER_PROVIDER.':'.$provider['id'];
            if (isset($seen[$key])) {
                continue;
            }
            $seen[$key] = true;
            $relatedPhones = [];
            try {
                $relatedPhones = $this->phoneDirectory->phonesForOwner(PartyAlternatePhone::OWNER_PROVIDER, $provider['id']);
            } catch (Throwable) {
                $relatedPhones = array_values(array_filter([
                    $this->last10($provider['company_phone'] ?? null),
                    $this->last10($provider['contact_person_phone'] ?? null),
                    $this->last10($provider['owner_phone'] ?? null),
                ]));
            }
            $relations[] = [
                'type' => PartyAlternatePhone::OWNER_PROVIDER,
                'id' => $provider['id'],
                'label' => $provider['name'],
                'via' => 'provider profile',
                'related_phones' => array_values(array_filter($relatedPhones, fn ($p) => $p !== $phone)),
                'url' => $provider['url'] ?? route('admin.provider.details', [$provider['id'], 'web_page' => 'overview']),
                'note' => ($provider['status_label'] ?? 'Provider').' profile linked to this number',
            ];
        }

        foreach ($leads as $lead) {
            if (($lead['kind'] ?? '') !== 'lead') {
                continue;
            }
            $key = PartyAlternatePhone::OWNER_PROVIDER_LEAD.':'.$lead['id'];
            if (isset($seen[$key])) {
                // Still try to attach linked provider if primary owner was something else
            } else {
                $seen[$key] = true;
                $relatedPhones = [];
                try {
                    $relatedPhones = $this->phoneDirectory->phonesForOwner(PartyAlternatePhone::OWNER_PROVIDER_LEAD, $lead['id']);
                } catch (Throwable) {
                    $relatedPhones = [];
                }
                $relations[] = [
                    'type' => PartyAlternatePhone::OWNER_PROVIDER_LEAD,
                    'id' => $lead['id'],
                    'label' => $lead['name'] ?? $lead['label'] ?? $lead['id'],
                    'via' => 'lead',
                    'related_phones' => array_values(array_filter($relatedPhones, fn ($p) => $p !== $phone)),
                    'url' => $lead['url'] ?? route('admin.lead.show', $lead['id']),
                    'note' => 'Lead record for this number',
                ];
            }
        }

        // Linked-from-lead alternate rows that point at providers/customers for this phone
        try {
            $alts = PartyAlternatePhone::query()
                ->where('phone_digits', $phone)
                ->get(['owner_type', 'owner_id', 'linked_from_lead_id']);
            foreach ($alts as $alt) {
                $key = $alt->owner_type.':'.$alt->owner_id;
                if (isset($seen[$key])) {
                    if ($alt->linked_from_lead_id) {
                        foreach ($relations as &$rel) {
                            if ($rel['type'] === $alt->owner_type && (string) $rel['id'] === (string) $alt->owner_id) {
                                $rel['linked_from_lead_id'] = (string) $alt->linked_from_lead_id;
                                $rel['linked_from_lead_url'] = route('admin.lead.show', $alt->linked_from_lead_id);
                                $rel['note'] = trim(($rel['note'] ?? '').' · linked from lead #'.$alt->linked_from_lead_id);
                            }
                        }
                        unset($rel);
                    }
                    continue;
                }
                $seen[$key] = true;
                $relations[] = [
                    'type' => $alt->owner_type,
                    'id' => (string) $alt->owner_id,
                    'label' => $alt->ownerLabel(),
                    'via' => 'alternate',
                    'related_phones' => [],
                    'url' => $this->relationUrl($alt->owner_type, (string) $alt->owner_id),
                    'linked_from_lead_id' => $alt->linked_from_lead_id ? (string) $alt->linked_from_lead_id : null,
                    'linked_from_lead_url' => $alt->linked_from_lead_id ? route('admin.lead.show', $alt->linked_from_lead_id) : null,
                    'note' => 'Alternate phone link'.($alt->linked_from_lead_id ? ' from lead #'.$alt->linked_from_lead_id : ''),
                ];
            }
        } catch (Throwable) {
            // optional
        }

        return $relations;
    }

    private function relationUrl(string $ownerType, string $ownerId): ?string
    {
        return match ($ownerType) {
            PartyAlternatePhone::OWNER_PROVIDER => route('admin.provider.details', [$ownerId, 'web_page' => 'overview']),
            PartyAlternatePhone::OWNER_PROVIDER_LEAD,
            PartyAlternatePhone::OWNER_CUSTOMER_LEAD => route('admin.lead.show', $ownerId),
            PartyAlternatePhone::OWNER_CUSTOMER => route('admin.customer.detail', [$ownerId]),
            default => null,
        };
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
