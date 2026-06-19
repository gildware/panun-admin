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
        'error_message',
        'payload',
    ];

    protected $casts = [
        'signature_valid' => 'boolean',
        'payload' => 'array',
    ];
}
