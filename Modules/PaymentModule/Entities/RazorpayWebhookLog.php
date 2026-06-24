<?php

namespace Modules\PaymentModule\Entities;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;

class RazorpayWebhookLog extends Model
{
    use HasUuid;

    protected $table = 'razorpay_webhook_logs';

    protected $fillable = [
        'event',
        'razorpay_payment_id',
        'razorpay_order_id',
        'payment_request_id',
        'signature_valid',
        'result',
        'http_status',
        'booking_readable_id',
        'paid_amount',
        'error_message',
        'payload',
    ];

    protected $casts = [
        'signature_valid' => 'boolean',
        'payload' => 'array',
    ];

    public function getPaidAmountAttribute($value): ?float
    {
        if ($value !== null && $value !== '') {
            return round((float) $value, 2);
        }

        $entity = is_array($this->payload['payload']['payment']['entity'] ?? null)
            ? $this->payload['payload']['payment']['entity']
            : [];

        return isset($entity['amount'])
            ? round((int) $entity['amount'] / 100, 2)
            : null;
    }
}
