<?php

namespace Modules\LeadManagement\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\ProviderManagement\Entities\Provider;
use Modules\UserManagement\Entities\User;

class PartyAlternatePhone extends Model
{
    public const OWNER_CUSTOMER = 'customer';

    public const OWNER_PROVIDER = 'provider';

    protected $fillable = [
        'owner_type',
        'owner_id',
        'phone_digits',
        'linked_from_lead_id',
        'created_by',
    ];

    public function linkedFromLead(): BelongsTo
    {
        return $this->belongsTo(Lead::class, 'linked_from_lead_id')->withTrashed();
    }

    public function createdByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function provider(): BelongsTo
    {
        return $this->belongsTo(Provider::class, 'owner_id');
    }

    public function ownerLabel(): string
    {
        if ($this->owner_type === self::OWNER_CUSTOMER) {
            $user = $this->customer;
            $name = trim((string) (($user->first_name ?? '').' '.($user->last_name ?? '')));

            return $name !== '' ? $name : (string) ($user->phone ?? $this->owner_id);
        }

        $provider = $this->provider;
        $name = trim((string) ($provider->company_name ?: $provider->contact_person_name ?? ''));

        return $name !== '' ? $name : (string) $this->owner_id;
    }
}
