<?php

namespace Modules\BookingModule\Entities;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class BookingPartialPayment extends Model
{
    use HasFactory, HasUuid;

    protected $fillable = [
        'booking_id',
        'paid_with',
        'transaction_id',
        'paid_amount',
        'due_amount',
        'received_by', // company | provider - who received this payment
    ];

    protected static function newFactory()
    {
        return \Modules\BookingModule\Database\factories\BookingPartialPaymentFactory::new();
    }
}
