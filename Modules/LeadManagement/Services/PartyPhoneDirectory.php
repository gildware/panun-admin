<?php

namespace Modules\LeadManagement\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Modules\LeadManagement\Entities\Lead;
use Modules\LeadManagement\Entities\PartyAlternatePhone;
use Modules\ProviderManagement\Entities\Provider;
use Modules\UserManagement\Entities\User;

/**
 * Primary + alternate phones for customers and providers.
 * Every phone digit suffix is owned by at most one party.
 */
class PartyPhoneDirectory
{
    public static function normalizeDigits(?string $phone): ?string
    {
        $digits = preg_replace('/\D+/', '', (string) $phone) ?? '';
        if (strlen($digits) < 8) {
            return null;
        }

        return strlen($digits) >= 10 ? substr($digits, -10) : $digits;
    }

    /**
     * @return array{owner_type: string, owner_id: string, label: string, via: string}|null
     */
    public function findOwner(string $phone, ?string $ignoreOwnerType = null, ?string $ignoreOwnerId = null): ?array
    {
        $digits = self::normalizeDigits($phone);
        if ($digits === null) {
            return null;
        }

        if (Schema::hasTable('party_alternate_phones')) {
            $alternate = PartyAlternatePhone::query()
                ->where('phone_digits', $digits)
                ->first();
            if ($alternate
                && ! ($ignoreOwnerType === $alternate->owner_type && (string) $ignoreOwnerId === (string) $alternate->owner_id)
            ) {
                return [
                    'owner_type' => $alternate->owner_type,
                    'owner_id' => (string) $alternate->owner_id,
                    'label' => $alternate->ownerLabel(),
                    'via' => 'alternate',
                ];
            }
        }

        $customer = $this->findUserByDigits($digits, CUSTOMER_USER_TYPES);
        if ($customer
            && ! ($ignoreOwnerType === PartyAlternatePhone::OWNER_CUSTOMER && (string) $ignoreOwnerId === (string) $customer->id)
        ) {
            $name = trim((string) (($customer->first_name ?? '').' '.($customer->last_name ?? '')));

            return [
                'owner_type' => PartyAlternatePhone::OWNER_CUSTOMER,
                'owner_id' => (string) $customer->id,
                'label' => $name !== '' ? $name : (string) $customer->phone,
                'via' => 'primary',
            ];
        }

        $providerUser = $this->findUserByDigits($digits, PROVIDER_USER_TYPES);
        if ($providerUser) {
            $providerId = (string) ($providerUser->provider_id ?? '');
            if ($providerId === '') {
                $providerId = (string) (Provider::query()->where('user_id', $providerUser->id)->value('id') ?? '');
            }
            if ($providerId !== ''
                && ! ($ignoreOwnerType === PartyAlternatePhone::OWNER_PROVIDER && (string) $ignoreOwnerId === $providerId)
            ) {
                $provider = Provider::query()->find($providerId);
                $label = trim((string) ($provider?->company_name ?: $provider?->contact_person_name ?: $providerUser->phone));

                return [
                    'owner_type' => PartyAlternatePhone::OWNER_PROVIDER,
                    'owner_id' => $providerId,
                    'label' => $label !== '' ? $label : $providerId,
                    'via' => 'primary',
                ];
            }
        }

        $provider = Provider::query()
            ->where(function ($query) use ($digits) {
                $this->wherePhoneEndsWith($query, 'company_phone', $digits);
                $query->orWhere(function ($inner) use ($digits) {
                    $this->wherePhoneEndsWith($inner, 'contact_person_phone', $digits);
                });
            })
            ->first();
        if ($provider
            && ! ($ignoreOwnerType === PartyAlternatePhone::OWNER_PROVIDER && (string) $ignoreOwnerId === (string) $provider->id)
        ) {
            $label = trim((string) ($provider->company_name ?: $provider->contact_person_name));

            return [
                'owner_type' => PartyAlternatePhone::OWNER_PROVIDER,
                'owner_id' => (string) $provider->id,
                'label' => $label !== '' ? $label : (string) $provider->id,
                'via' => 'primary',
            ];
        }

        return null;
    }

    /**
     * All normalized phone digit keys owned by this party (primary + extras).
     *
     * @return list<string>
     */
    public function phonesForOwner(string $ownerType, string $ownerId): array
    {
        $phones = [];

        if ($ownerType === PartyAlternatePhone::OWNER_CUSTOMER) {
            $user = User::query()->find($ownerId);
            $digits = self::normalizeDigits($user?->phone);
            if ($digits) {
                $phones[] = $digits;
            }
        } elseif ($ownerType === PartyAlternatePhone::OWNER_PROVIDER) {
            $provider = Provider::query()->with('owner')->find($ownerId);
            foreach ([
                $provider?->company_phone,
                $provider?->contact_person_phone,
                $provider?->owner?->phone,
            ] as $raw) {
                $digits = self::normalizeDigits($raw);
                if ($digits) {
                    $phones[] = $digits;
                }
            }
        } elseif ($ownerType === PartyAlternatePhone::OWNER_PROVIDER_LEAD) {
            $lead = Lead::query()->withTrashed()->find($ownerId);
            $digits = self::normalizeDigits($lead?->phone_number);
            if ($digits) {
                $phones[] = $digits;
            }
        }

        $extras = PartyAlternatePhone::query()
            ->where('owner_type', $ownerType)
            ->where('owner_id', $ownerId)
            ->pluck('phone_digits')
            ->all();

        return array_values(array_unique(array_merge($phones, $extras)));
    }

    public function addAlternate(
        string $ownerType,
        string $ownerId,
        string $phone,
        ?int $linkedFromLeadId = null,
        ?string $createdBy = null
    ): PartyAlternatePhone {
        $digits = self::normalizeDigits($phone);
        if ($digits === null) {
            throw new \InvalidArgumentException(translate('Invalid_phone_number') ?: 'Invalid phone number');
        }

        $existing = $this->findOwner($digits);
        if ($existing
            && ($existing['owner_type'] !== $ownerType || (string) $existing['owner_id'] !== (string) $ownerId)
        ) {
            $who = match ($existing['owner_type']) {
                PartyAlternatePhone::OWNER_CUSTOMER => translate('Customer'),
                PartyAlternatePhone::OWNER_PROVIDER_LEAD => translate('Provider_lead') ?: 'Provider lead',
                default => translate('Provider'),
            };
            throw new \InvalidArgumentException(
                (translate('Phone_already_belongs_to') ?: 'This number already belongs to').' '.$who.' '.$existing['label']
            );
        }

        $already = PartyAlternatePhone::query()
            ->where('owner_type', $ownerType)
            ->where('owner_id', $ownerId)
            ->where('phone_digits', $digits)
            ->first();
        if ($already) {
            return $already;
        }

        // Number is already this party's primary — still record it as an alternate row for audit/search consistency.
        return PartyAlternatePhone::query()->create([
            'owner_type' => $ownerType,
            'owner_id' => $ownerId,
            'phone_digits' => $digits,
            'linked_from_lead_id' => $linkedFromLeadId,
            'created_by' => $createdBy,
        ]);
    }

    /**
     * @return Collection<int, PartyAlternatePhone>
     */
    public function alternatesForOwner(string $ownerType, string $ownerId): Collection
    {
        return PartyAlternatePhone::query()
            ->where('owner_type', $ownerType)
            ->where('owner_id', $ownerId)
            ->orderBy('id')
            ->get();
    }

    /**
     * Display payload for a lead show page: other phones + whether this phone is linked elsewhere.
     *
     * @return array{
     *   other_phones: list<array{phone_digits: string, display: string, linked_from_lead_id: int|null, linked_from_label: string|null, created_at: string|null, created_by_name: string|null}>,
     *   linked_as: array{owner_type: string, owner_id: string, owner_label: string, via: string}|null,
     *   owner_type: string|null,
     *   owner_id: string|null,
     *   owner_label: string|null
     * }
     */
    public function displayForLead(Lead $lead): array
    {
        $empty = [
            'other_phones' => [],
            'linked_as' => null,
            'owner_type' => null,
            'owner_id' => null,
            'owner_label' => null,
        ];

        if (! Schema::hasTable('party_alternate_phones')) {
            return $empty;
        }

        try {
            $digits = self::normalizeDigits($lead->phone_number);
            $ownerType = null;
            $ownerId = null;
            $ownerLabel = null;

            $asProviderLead = PartyAlternatePhone::query()
                ->where('owner_type', PartyAlternatePhone::OWNER_PROVIDER_LEAD)
                ->where('owner_id', (string) $lead->id)
                ->exists();
            if ($asProviderLead) {
                $ownerType = PartyAlternatePhone::OWNER_PROVIDER_LEAD;
                $ownerId = (string) $lead->id;
                $ownerLabel = trim((string) ($lead->name ?? '')) ?: ('Lead #'.$lead->id);
            }

            if ($digits !== null) {
                $owner = $this->findOwner($digits);
                if ($owner) {
                    // Prefer registered customer/provider over treating this lead as only itself.
                    if ($ownerType === null
                        || in_array($owner['owner_type'], [PartyAlternatePhone::OWNER_CUSTOMER, PartyAlternatePhone::OWNER_PROVIDER], true)
                    ) {
                        $ownerType = $owner['owner_type'];
                        $ownerId = $owner['owner_id'];
                        $ownerLabel = $owner['label'];
                    }
                }
            }

            if ($ownerType === null || $ownerId === null) {
                return $empty;
            }

            $alts = $this->alternatesForOwner($ownerType, $ownerId)
                ->load(['linkedFromLead', 'createdByUser']);

            $otherPhones = [];
            foreach ($alts as $alt) {
                // On the lead that owns the party, list every linked other number.
                // On the source lead whose phone IS the alternate, skip listing itself here
                // (shown instead via linked_as).
                if ($digits !== null && $alt->phone_digits === $digits) {
                    continue;
                }
                $fromLead = $alt->linkedFromLead;
                $by = $alt->createdByUser;
                $byName = null;
                if ($by) {
                    $byName = trim((string) (($by->first_name ?? '').' '.($by->last_name ?? ''))) ?: ($by->email ?? null);
                }
                $otherPhones[] = [
                    'phone_digits' => (string) $alt->phone_digits,
                    'display' => $this->formatDigitsDisplay((string) $alt->phone_digits),
                    'linked_from_lead_id' => $fromLead?->id ? (int) $fromLead->id : ($alt->linked_from_lead_id ? (int) $alt->linked_from_lead_id : null),
                    'linked_from_label' => $fromLead
                        ? ('#'.$fromLead->id.($fromLead->name ? ' — '.$fromLead->name : ''))
                        : ($alt->linked_from_lead_id ? ('#'.$alt->linked_from_lead_id) : null),
                    'created_at' => $alt->created_at?->format('d M Y, h:i A'),
                    'created_by_name' => $byName,
                ];
            }

            $linkedAs = null;
            if ($digits !== null) {
                $altRow = PartyAlternatePhone::query()->where('phone_digits', $digits)->first();
                if ($altRow
                    && ! ($altRow->owner_type === PartyAlternatePhone::OWNER_PROVIDER_LEAD
                        && (string) $altRow->owner_id === (string) $lead->id)
                ) {
                    $linkedAs = [
                        'owner_type' => $altRow->owner_type,
                        'owner_id' => (string) $altRow->owner_id,
                        'owner_label' => $altRow->ownerLabel(),
                        'via' => 'alternate',
                    ];
                }
            }

            return [
                'other_phones' => array_values($otherPhones),
                'linked_as' => $linkedAs,
                'owner_type' => $ownerType,
                'owner_id' => $ownerId,
                'owner_label' => $ownerLabel,
            ];
        } catch (\Throwable $e) {
            Log::warning('PartyPhoneDirectory::displayForLead failed', [
                'lead_id' => $lead->id ?? null,
                'error' => $e->getMessage(),
            ]);

            return $empty;
        }
    }

    /**
     * Extra lead IDs that should match a phone/name search because of other-phone links.
     *
     * @return list<int>
     */
    public function relatedLeadIdsForSearch(string $search): array
    {
        $digits = self::normalizeDigits($search);
        if ($digits === null || ! Schema::hasTable('party_alternate_phones')) {
            return [];
        }

        try {
            return $this->relatedLeadIdsForSearchUnsafe($digits);
        } catch (\Throwable $e) {
            Log::warning('PartyPhoneDirectory::relatedLeadIdsForSearch failed', ['error' => $e->getMessage()]);

            return [];
        }
    }

    /**
     * @return list<int>
     */
    private function relatedLeadIdsForSearchUnsafe(string $digits): array
    {
        $ids = [];
        $owner = $this->findOwner($digits);
        if ($owner) {
            if ($owner['owner_type'] === PartyAlternatePhone::OWNER_PROVIDER_LEAD) {
                $ids[] = (int) $owner['owner_id'];
            }
            foreach ($this->phonesForOwner($owner['owner_type'], $owner['owner_id']) as $key) {
                $matched = Lead::query()
                    ->withTrashed()
                    ->where(function ($query) use ($key) {
                        $this->wherePhoneEndsWith($query, 'phone_number', $key);
                    })
                    ->pluck('id')
                    ->all();
                foreach ($matched as $id) {
                    $ids[] = (int) $id;
                }
            }
            $fromAlts = PartyAlternatePhone::query()
                ->where('owner_type', $owner['owner_type'])
                ->where('owner_id', $owner['owner_id'])
                ->whereNotNull('linked_from_lead_id')
                ->pluck('linked_from_lead_id')
                ->all();
            foreach ($fromAlts as $id) {
                $ids[] = (int) $id;
            }
        }

        $alt = PartyAlternatePhone::query()->where('phone_digits', $digits)->first();
        if ($alt) {
            if ($alt->owner_type === PartyAlternatePhone::OWNER_PROVIDER_LEAD) {
                $ids[] = (int) $alt->owner_id;
            }
            if ($alt->linked_from_lead_id) {
                $ids[] = (int) $alt->linked_from_lead_id;
            }
        }

        return array_values(array_unique(array_filter($ids)));
    }

    /**
     * @param  list<int>  $leadIds
     * @return array<int, list<string>> lead_id => other phone display strings
     */
    public function otherPhoneDisplaysForLeadIds(array $leadIds): array
    {
        if ($leadIds === [] || ! Schema::hasTable('party_alternate_phones')) {
            return [];
        }

        try {
            $leads = Lead::query()->withTrashed()->whereIn('id', $leadIds)->get(['id', 'phone_number', 'name', 'lead_type']);
            $map = [];
            foreach ($leads as $lead) {
                $info = $this->displayForLead($lead);
                $map[(int) $lead->id] = array_values(array_unique(array_map(
                    fn ($row) => $row['display'],
                    $info['other_phones']
                )));
            }

            return $map;
        } catch (\Throwable $e) {
            Log::warning('PartyPhoneDirectory::otherPhoneDisplaysForLeadIds failed', ['error' => $e->getMessage()]);

            return [];
        }
    }

    public function formatDigitsDisplay(string $digits): string
    {
        $digits = preg_replace('/\D+/', '', $digits) ?? $digits;
        if (strlen($digits) === 10) {
            return '+91'.$digits;
        }

        return $digits;
    }

    private function findUserByDigits(string $digits, array $userTypes): ?User
    {
        $query = User::query()->whereIn('user_type', $userTypes);
        $this->wherePhoneEndsWith($query, 'phone', $digits);

        return $query->first();
    }

    private function wherePhoneEndsWith($query, string $column, string $digits): void
    {
        $query->where(function ($inner) use ($column, $digits) {
            if (DB::connection()->getDriverName() === 'mysql') {
                $inner->whereRaw(
                    "RIGHT(REGEXP_REPLACE(COALESCE({$column}, ''), '[^0-9]', ''), ?) = ?",
                    [strlen($digits), $digits]
                );

                return;
            }

            $inner->where($column, $digits)->orWhere($column, 'like', '%'.$digits);
        });
    }
}
