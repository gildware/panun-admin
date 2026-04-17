<?php

namespace Modules\BookingModule\Entities;

use Illuminate\Database\Eloquent\Model;

class BookingDisputeReason extends Model
{
    public const RESPONSIBLE_CUSTOMER = 'customer';

    public const RESPONSIBLE_PROVIDER = 'provider';

    public const RESPONSIBLE_STAFF = 'staff';

    public const RESPONSIBLE_NO_ONE = 'no_one';

    protected $fillable = [
        'name',
        'description',
        'responsible',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public static function responsibleOptions(): array
    {
        return [
            self::RESPONSIBLE_CUSTOMER,
            self::RESPONSIBLE_PROVIDER,
            self::RESPONSIBLE_STAFF,
            self::RESPONSIBLE_NO_ONE,
        ];
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}

