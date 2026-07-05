<?php

namespace Modules\CallCenterModule\Services;

use Illuminate\Support\Facades\DB;
use Modules\CallCenterModule\Entities\CustomerProfile;
use Modules\CallCenterModule\Transformers\CustomerTransformer;
use Modules\UserManagement\Entities\User;
use Modules\UserManagement\Entities\UserAddress;

class CustomerProfileService
{
    public function __construct(
        private readonly PhoneNormalizer $phoneNormalizer,
        private readonly CustomerTransformer $customerTransformer,
    ) {
    }

    public function findUserByPhone(string $phone): ?User
    {
        $normalized = $this->phoneNormalizer->normalize($phone);
        $digits = $this->phoneNormalizer->digitsOnly($normalized);

        $user = User::query()
            ->ofType(CUSTOMER_USER_TYPES)
            ->where(function ($q) use ($normalized, $digits) {
                if ($normalized !== '') {
                    $q->where('phone', $normalized)
                        ->orWhere('phone', ltrim($normalized, '+'))
                        ->orWhere('phone', $digits);
                }
                if ($digits !== '') {
                    $q->orWhereRaw('REGEXP_REPLACE(COALESCE(phone, \'\'), \'[^0-9]\', \'\') = ?', [$digits]);
                }
            })
            ->first();

        if ($user) {
            return $user;
        }

        return User::query()
            ->ofType(CUSTOMER_USER_TYPES)
            ->whereIn('id', function ($sub) use ($normalized, $digits) {
                $sub->select('user_id')
                    ->from('user_addresses')
                    ->where(function ($w) use ($normalized, $digits) {
                        if ($normalized !== '') {
                            $w->where('contact_person_number', $normalized)
                                ->orWhere('contact_person_number', ltrim($normalized, '+'))
                                ->orWhere('contact_person_number', $digits);
                        }
                    });
            })
            ->first();
    }

    public function getProfileForUser(User $user): CustomerProfile
    {
        $profile = CustomerProfile::query()->where('user_id', $user->id)->first();
        if ($profile) {
            return $profile;
        }

        return $this->createProfileForUser($user);
    }

    public function getProfileByNumericId(int $id): ?CustomerProfile
    {
        return CustomerProfile::query()->with('user')->find($id);
    }

    public function getProfileByRef(string $customerRef): ?CustomerProfile
    {
        return CustomerProfile::query()
            ->with('user')
            ->where('customer_ref', $customerRef)
            ->first();
    }

    public function createProfileForUser(User $user, array $overrides = []): CustomerProfile
    {
        return DB::transaction(function () use ($user, $overrides) {
            $existing = CustomerProfile::query()->where('user_id', $user->id)->lockForUpdate()->first();
            if ($existing) {
                return $existing;
            }

            $profile = CustomerProfile::query()->create(array_merge([
                'user_id' => $user->id,
                'customer_ref' => $this->nextCustomerRef(),
                'customer_type' => 'standard',
                'tags' => [],
                'alternate_phones' => [],
                'priority' => 'normal',
            ], $overrides));

            return $profile;
        });
    }

    public function createCustomer(array $data): array
    {
        return DB::transaction(function () use ($data) {
            $phone = $this->phoneNormalizer->normalize($data['phone'] ?? '');
            $nameParts = preg_split('/\s+/', trim((string) ($data['name'] ?? 'Unknown')), 2) ?: ['Unknown'];

            $user = User::query()->create([
                'first_name' => $nameParts[0],
                'last_name' => $nameParts[1] ?? '',
                'phone' => $phone,
                'email' => $data['email'] ?? null,
                'user_type' => 'customer',
                'is_active' => 1,
                'is_phone_verified' => 0,
                'password' => bcrypt(str()->random(32)),
            ]);

            if (!empty($data['location'])) {
                $address = new UserAddress();
                $address->user_id = $user->id;
                $address->address = trim(($data['location']['city'] ?? '') . ', ' . ($data['location']['state'] ?? ''));
                $address->city = $data['location']['city'] ?? null;
                $address->country = $data['location']['country'] ?? 'IN';
                $address->address_type = 'service';
                $address->contact_person_name = trim($user->first_name . ' ' . $user->last_name);
                $address->contact_person_number = $phone;
                $address->save();
            }

            $profile = $this->createProfileForUser($user, [
                'customer_type' => $data['customer_type'] ?? 'standard',
                'tags' => $data['tags'] ?? [],
                'priority' => $data['priority'] ?? 'normal',
            ]);

            return $this->customerTransformer->transform($user->fresh(['addresses']), $profile);
        });
    }

    public function updateCustomer(CustomerProfile $profile, array $data): array
    {
        $user = $profile->user;
        $user->loadMissing(['addresses']);

        if (array_key_exists('name', $data)) {
            $parts = preg_split('/\s+/', trim((string) $data['name']), 2) ?: ['Unknown'];
            $user->first_name = $parts[0];
            $user->last_name = $parts[1] ?? '';
        }

        if (array_key_exists('phone', $data)) {
            $user->phone = $this->phoneNormalizer->normalize($data['phone']);
        }

        if (array_key_exists('email', $data)) {
            $user->email = $data['email'];
        }

        $user->save();

        $profileUpdates = array_filter([
            'customer_type' => $data['customer_type'] ?? null,
            'tags' => $data['tags'] ?? null,
            'priority' => $data['priority'] ?? null,
            'assigned_agent_id' => $data['assigned_agent_id'] ?? null,
            'assigned_agent_name' => $data['assigned_agent_name'] ?? null,
        ], fn ($v) => $v !== null);

        if (!empty($profileUpdates)) {
            $profile->fill($profileUpdates)->save();
        }

        if (!empty($data['location'])) {
            $address = $user->addresses()->first();
            if ($address) {
                $address->update([
                    'city' => $data['location']['city'] ?? $address->city,
                    'country' => $data['location']['country'] ?? $address->country,
                    'address' => trim(($data['location']['city'] ?? '') . ', ' . ($data['location']['state'] ?? '')),
                ]);
            }
        }

        return $this->customerTransformer->transform($user->fresh(['addresses']), $profile->fresh());
    }

    public function toApi(CustomerProfile $profile): array
    {
        $profile->loadMissing('user.addresses');

        return $this->customerTransformer->transform($profile->user, $profile);
    }

    private function nextCustomerRef(): string
    {
        $nextId = (int) CustomerProfile::query()->max('id') + 1;

        return 'CUS-' . str_pad((string) $nextId, 4, '0', STR_PAD_LEFT);
    }
}
