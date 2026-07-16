<?php

/**
 * One-time repair: post missing account_payable from booking settlement provider_owes_company.
 *
 * Usage (from panun-admin): php scripts/repair_provider_settlement_payable_ledger.php [provider_id]
 */

require __DIR__ . '/../vendor/autoload.php';

$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Modules\BookingModule\Entities\Booking;
use Modules\BookingModule\Entities\BookingRepeat;
use Modules\ProviderManagement\Entities\Provider;

$providerId = $argv[1] ?? null;

$bookingQuery = Booking::query()
    ->where('booking_status', 'completed')
    ->whereNotNull('provider_id');

$repeatQuery = BookingRepeat::query()
    ->where('booking_status', 'completed')
    ->whereNotNull('provider_id');

if ($providerId) {
    $bookingQuery->where('provider_id', $providerId);
    $repeatQuery->where('provider_id', $providerId);
}

$repaired = 0;

foreach ($bookingQuery->cursor() as $booking) {
    record_booking_completion_provider_commission_payable($booking);
    $repaired++;
}

foreach ($repeatQuery->cursor() as $repeat) {
    record_booking_completion_provider_commission_payable($repeat);
    $repaired++;
}

echo "Processed {$repaired} completed booking(s)";
if ($providerId) {
    $provider = Provider::find($providerId);
    if ($provider?->owner?->account) {
        $acct = $provider->owner->account;
        echo "\nProvider account_payable: {$acct->account_payable}, receivable: {$acct->account_receivable}";
    }
}
echo "\n";
