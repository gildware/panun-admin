<?php

namespace Modules\LeadManagement\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
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
            $who = $existing['owner_type'] === PartyAlternatePhone::OWNER_CUSTOMER
                ? translate('Customer')
                : translate('Provider');
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
