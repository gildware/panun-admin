<?php

namespace Modules\BookingModule\Events;

use Illuminate\Queue\SerializesModels;
use Modules\BookingModule\Entities\Booking;

class ProviderWithdrewFromBooking
{
    use SerializesModels;

    public function __construct(
        public Booking $booking,
        public string $providerId,
    ) {}
}
