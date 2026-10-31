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
        'commission_included' => 'boolean',
        'commission_amount' => 'float',
    ];

    protected $fillable = [
        'booking_id',
        'booking_repeat_id',
        'title',
        'details',
        'type',
        'quantity',
        'price',
        'discount',
        'total',
        'commission_included',
        'commission_amount_type',
        'commission_amount',
    ];

    public const TYPE_SERVICE = 'service';
    public const TYPE_SPARE_PART = 'spare_part';

    public const COMMISSION_AMOUNT_TYPE_PERCENTAGE = 'percentage';
    public const COMMISSION_AMOUNT_TYPE_AMOUNT = 'amount';

    public static function typeOptions(): array
    {
        return [
            self::TYPE_SERVICE => translate('Service'),
            self::TYPE_SPARE_PART => translate('Spare_Part'),
        ];
    }

    /**
     * Normalize spare-part commission fields from request/cart input.
     * Service lines always clear commission. Spare parts default to excluded.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public static function normalizeCommissionAttributes(array $data): array
    {
        $type = ($data['type'] ?? self::TYPE_SERVICE) === self::TYPE_SPARE_PART
            ? self::TYPE_SPARE_PART
            : self::TYPE_SERVICE;
        $data['type'] = $type;

        if ($type !== self::TYPE_SPARE_PART) {
            $data['commission_included'] = false;
            $data['commission_amount_type'] = null;
            $data['commission_amount'] = 0.0;

            return $data;
        }

        $rawIncluded = $data['commission_included'] ?? 'exclude';
        $included = $rawIncluded === true
            || $rawIncluded === 1
            || $rawIncluded === '1'
            || $rawIncluded === 'include'
            || $rawIncluded === 'yes';

        if (! $included) {
            $data['commission_included'] = false;
            $data['commission_amount_type'] = null;
            $data['commission_amount'] = 0.0;

            return $data;
        }

        $amountType = ($data['commission_amount_type'] ?? self::COMMISSION_AMOUNT_TYPE_PERCENTAGE) === self::COMMISSION_AMOUNT_TYPE_AMOUNT
            ? self::COMMISSION_AMOUNT_TYPE_AMOUNT
            : self::COMMISSION_AMOUNT_TYPE_PERCENTAGE;
        $amount = max(0.0, (float) ($data['commission_amount'] ?? 0));
        if ($amountType === self::COMMISSION_AMOUNT_TYPE_PERCENTAGE) {
            $amount = min(100.0, $amount);
        }

        $data['commission_included'] = true;
        $data['commission_amount_type'] = $amountType;
        $data['commission_amount'] = $amount;

        return $data;
    }

    /**
     * Admin commission owed on this spare-part line (0 when excluded or type is service).
     */
    public function adminCommissionAmount(): float
    {
        if (($this->type ?? '') !== self::TYPE_SPARE_PART) {
            return 0.0;
        }
        if (! (bool) ($this->commission_included ?? false)) {
            return 0.0;
        }

        $lineTotal = max(0.0, (float) ($this->total ?? 0));
        $value = max(0.0, (float) ($this->commission_amount ?? 0));
        $amountType = $this->commission_amount_type ?? self::COMMISSION_AMOUNT_TYPE_PERCENTAGE;

        if ($amountType === self::COMMISSION_AMOUNT_TYPE_AMOUNT) {
            return round(min($value, $lineTotal), 2);
        }

        return round($lineTotal * min(100.0, $value) / 100.0, 2);
    }

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    public function repeat(): BelongsTo
    {
        return $this->belongsTo(BookingRepeat::class, 'booking_repeat_id');
    }

    /**
     * Recalculate total from quantity * price - discount.
     */
    public function recalculateTotal(): void
    {
        $this->total = max(0, ($this->quantity * $this->price) - $this->discount);
    }
}
