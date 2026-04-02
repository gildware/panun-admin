<?php

namespace Modules\BusinessSettingsModule\Entities;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
class AdditionalChargeType extends Model
{
    use HasUuid;

    protected $table = 'additional_charge_types';

    protected $casts = [
        'sort_order' => 'integer',
        'is_active' => 'boolean',
        'customizable_at_booking' => 'boolean',
        'is_commissionable' => 'boolean',
        'charge_setup' => 'array',
    ];

    protected $fillable = [
        'name',
        'sort_order',
        'is_active',
        'customizable_at_booking',
        'is_commissionable',
        'charge_setup',
    ];

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order')->orderBy('name');
    }
}
