<?php

namespace Modules\ProviderManagement\Entities;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;

class ProviderRegistrationDraft extends Model
{
    use HasUuid;

    protected $table = 'provider_registration_drafts';

    protected $casts = [
        'completed_steps' => 'array',
        'form_data' => 'array',
        'expires_at' => 'datetime',
    ];

    protected $fillable = [
        'phone',
        'registration_token',
        'provider_type',
        'current_step',
        'completed_steps',
        'form_data',
        'expires_at',
    ];
}
