<?php

namespace Modules\CallCenterModule\Transformers;

use Modules\CallCenterModule\Entities\CustomerProfile;
use Modules\CallCenterModule\Services\PhoneNormalizer;
use Modules\UserManagement\Entities\User;

class CustomerTransformer
{
    public function __construct(private readonly PhoneNormalizer $phoneNormalizer)
    {
    }

    public function transform(User $user, CustomerProfile $profile): array
    {
        $user->loadMissing(['addresses']);

        $primaryAddress = $user->addresses->first();

        $phone = $this->phoneNormalizer->normalize($user->phone);
        $alternatePhones = collect($profile->alternate_phones ?? [])
            ->map(fn ($p) => $this->phoneNormalizer->normalize((string) $p))
            ->filter()
            ->values()
            ->all();

        return [
            'id' => $profile->id,
            'customer_id' => $profile->customer_ref,
            'name' => $this->displayName($user, $profile, $primaryAddress),
            'phone' => $phone,
            'alternate_phones' => $alternatePhones,
            'email' => $user->email,
            'customer_type' => $profile->customer_type,
            'tags' => $profile->tags ?? [],
            'location' => [
                'city' => $primaryAddress?->city,
                'state' => null,
                'country' => $primaryAddress?->country ?? 'IN',
            ],
            'assigned_agent_id' => $profile->assigned_agent_id,
            'assigned_agent_name' => $profile->assigned_agent_name,
            'priority' => $profile->priority,
            'total_calls' => $profile->total_calls,
            'last_call_at' => $profile->last_call_at?->utc()->toIso8601String(),
            'ai_summary' => $profile->ai_summary,
            'created_at' => $user->created_at?->utc()->toIso8601String(),
            'updated_at' => ($profile->updated_at && $user->updated_at && $profile->updated_at->gt($user->updated_at)
                ? $profile->updated_at
                : ($user->updated_at ?? $profile->updated_at))?->utc()->toIso8601String(),
        ];
    }

    public function transformSearchItem(User $user, CustomerProfile $profile): array
    {
        return [
            'id' => $profile->id,
            'customer_id' => $profile->customer_ref,
            'name' => $this->displayName($user, $profile),
            'phone' => $this->phoneNormalizer->normalize($user->phone),
            'customer_type' => $profile->customer_type,
            'priority' => $profile->priority,
        ];
    }

    private function displayName(User $user, CustomerProfile $profile, ?object $primaryAddress = null): string
    {
        $fromNames = trim(trim((string) ($user->first_name ?? '')) . ' ' . trim((string) ($user->last_name ?? '')));
        if ($fromNames !== '') {
            return $fromNames;
        }

        $contactName = trim((string) ($primaryAddress?->contact_person_name ?? ''));
        if ($contactName !== '') {
            return $contactName;
        }

        if ($user->email) {
            return (string) str($user->email)->before('@');
        }

        if ($profile->customer_ref) {
            return $profile->customer_ref;
        }

        return 'Unknown';
    }
}
