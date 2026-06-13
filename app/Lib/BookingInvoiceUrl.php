<?php

namespace App\Lib;

use Illuminate\Support\Facades\URL;

class BookingInvoiceUrl
{
    public static function customer(string $bookingId, string $lang, string $variant = 'regular'): string
    {
        $route = match ($variant) {
            'repeat' => 'admin.booking.customer-fullbooking-invoice',
            'single' => 'admin.booking.customer-fullbooking-single-invoice',
            default => 'admin.booking.customer-invoice',
        };

        return URL::temporarySignedRoute($route, now()->addHours(24), [
            'id' => $bookingId,
            'lang' => $lang,
        ]);
    }

    public static function provider(string $bookingId, string $lang, string $variant = 'regular'): string
    {
        $route = match ($variant) {
            'repeat' => 'admin.booking.provider-fullbooking-invoice',
            'single' => 'admin.booking.provider-fullbooking-single-invoice',
            default => 'admin.booking.provider-invoice',
        };

        return URL::temporarySignedRoute($route, now()->addHours(24), [
            'id' => $bookingId,
            'lang' => $lang,
        ]);
    }
}
