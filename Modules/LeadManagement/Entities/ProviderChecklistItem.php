<?php

namespace Modules\LeadManagement\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProviderChecklistItem extends Model
{
    protected $fillable = [
        'name',
        'description',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function leadChecklistEntries(): HasMany
    {
        return $this->hasMany(LeadProviderChecklist::class, 'provider_checklist_item_id');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
