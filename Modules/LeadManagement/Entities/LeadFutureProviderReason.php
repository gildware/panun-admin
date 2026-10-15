<?php

namespace Modules\LeadManagement\Entities;

use Illuminate\Database\Eloquent\Model;
use Modules\LeadManagement\Entities\Concerns\HasReasonFollowupOptions;

class LeadFutureProviderReason extends Model
{
    use HasReasonFollowupOptions;

    protected $fillable = [
        'name',
        'description',
        'followup_label',
        'is_active',
    ];

    public function followupReasonType(): string
    {
        return 'future_provider_reason';
    }

    protected $casts = [
        'is_active' => 'boolean',
    ];
}
