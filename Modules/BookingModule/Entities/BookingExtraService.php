<?php

namespace Modules\BookingModule\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BookingExtraService extends Model
{
    protected $table = 'booking_extra_services';

    protected $casts = [
        'quantity' => 'integer',
        'price' => 'float',
        'discount' => 'float',
        'total' => 'float',
    ];

    protected $fillable = [
        'booking_id',
        'title',
        'details',
        'type',
        'quantity',
        'price',
        'discount',
        'total',
    ];

    public const TYPE_SERVICE = 'service';
    public const TYPE_SPARE_PART = 'spare_part';

    public static function typeOptions(): array
    {
        return [
            self::TYPE_SERVICE => translate('Service'),
            self::TYPE_SPARE_PART => translate('Spare_Part'),
        ];
    }

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    /**
     * Recalculate total from quantity * price - discount.
     */
    public function recalculateTotal(): void
    {
        $this->total = max(0, ($this->quantity * $this->price) - $this->discount);
    }
}
