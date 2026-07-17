<?php

namespace Modules\UserManagement\Entities;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class UserAddress extends Model
{
    use HasFactory;

    protected $fillable = [];

    /**
     * Addresses usable for selection in the customer app (map pin + zone required).
     */
    public function scopeCompleteForSelection(Builder $query): Builder
    {
        return $query
            ->whereNotNull('lat')
            ->where('lat', '!=', '')
            ->whereNotNull('lon')
            ->where('lon', '!=', '')
            ->whereNotNull('zone_id')
            ->where('zone_id', '!=', '');
    }
    
    protected static function newFactory()
    {
        return \Modules\UserManagement\Database\factories\UserAddressFactory::new();
    }
}
