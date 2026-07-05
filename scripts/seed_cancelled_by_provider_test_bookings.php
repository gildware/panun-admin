<?php
/**
 * Seed dummy bookings for "Cancelled by provider" admin list testing.
 * Run: php scripts/seed_cancelled_by_provider_test_bookings.php
 */

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Modules\BookingModule\Entities\Booking;
use Modules\BookingModule\Entities\BookingProviderCancellationReason;
use Modules\BookingModule\Entities\BookingStatusHistory;
use Modules\ProviderManagement\Entities\Provider;
use Modules\UserManagement\Entities\User;

$marker = '[TEST-CANCELLED-BY-PROVIDER]';

$providers = Provider::query()
    ->where('is_active', 1)
    ->orderBy('company_name')
    ->take(2)
    ->get();

$customers = User::query()->inCustomerDirectory()->orderBy('first_name')->take(2)->get();
$reason = BookingProviderCancellationReason::query()->active()->orderBy('id')->first();
$admin = User::query()->whereIn('user_type', ['super-admin', 'admin-employee'])->first();

if ($providers->count() < 1 || $customers->count() < 1 || ! $reason || ! $admin) {
    echo "Missing prerequisites (provider, customer, cancellation reason, or admin user).\n";
    exit(1);
}

$template = Booking::query()
    ->whereNotNull('provider_id')
    ->whereNotNull('zone_id')
    ->orderByDesc('updated_at')
    ->first();

if (! $template) {
    echo "No existing booking to copy zone/category from. Create any booking first.\n";
    exit(1);
}

$created = [];

DB::transaction(function () use ($providers, $customers, $reason, $admin, $template, $marker, &$created) {
    $now = now();
    $mk = fn (string $tag) => "{$marker} {$tag} — seeded " . $now->format('Y-m-d H:i');

    // 1) Pending admin approval — provider still assigned
    $pendingProvider = $providers[0];
    $pendingCustomer = $customers[0];
    $pendingBooking = Booking::query()->create([
        'customer_id' => $pendingCustomer->id,
        'provider_id' => $pendingProvider->id,
        'zone_id' => $template->zone_id,
        'category_id' => $template->category_id,
        'sub_category_id' => $template->sub_category_id,
        'booking_status' => 'pending_cancellation',
        'payment_method' => 'cash_after_service',
        'is_paid' => 0,
        'is_verified' => 1,
        'is_checked' => 1,
        'service_location' => 'customer',
        'service_schedule' => Carbon::now()->addDay(),
        'service_description' => $mk('pending-cancellation-A'),
        'total_booking_amount' => 500,
        'total_tax_amount' => 0,
        'total_discount_amount' => 0,
        'booking_source' => 'admin_seed',
    ]);

    BookingStatusHistory::query()->create([
        'booking_id' => $pendingBooking->id,
        'changed_by' => $pendingProvider->user_id,
        'booking_status' => 'accepted',
        'status_change_remarks' => $mk('was-accepted'),
    ]);
    BookingStatusHistory::query()->create([
        'booking_id' => $pendingBooking->id,
        'changed_by' => $pendingProvider->user_id,
        'booking_status' => 'pending_cancellation',
        'booking_provider_cancellation_reason_id' => $reason->id,
        'status_change_remarks' => 'Provider test note: customer denied service at door.',
    ]);

    $created[] = [
        'type' => 'pending_cancellation',
        'readable_id' => $pendingBooking->readable_id,
        'id' => $pendingBooking->id,
        'provider' => $pendingProvider->company_name,
        'customer' => trim("{$pendingCustomer->first_name} {$pendingCustomer->last_name}"),
    ];

    // 2) Second pending request (different provider/customer if available)
    $pendingProvider2 = $providers->count() > 1 ? $providers[1] : $providers[0];
    $pendingCustomer2 = $customers->count() > 1 ? $customers[1] : $customers[0];
    $pendingBooking2 = Booking::query()->create([
        'customer_id' => $pendingCustomer2->id,
        'provider_id' => $pendingProvider2->id,
        'zone_id' => $template->zone_id,
        'category_id' => $template->category_id,
        'sub_category_id' => $template->sub_category_id,
        'booking_status' => 'pending_cancellation',
        'payment_method' => 'cash_after_service',
        'is_paid' => 0,
        'is_verified' => 1,
        'is_checked' => 1,
        'service_location' => 'customer',
        'service_schedule' => Carbon::now()->addDays(2),
        'service_description' => $mk('pending-cancellation-B'),
        'total_booking_amount' => 750,
        'total_tax_amount' => 0,
        'total_discount_amount' => 0,
        'booking_source' => 'admin_seed',
    ]);

    BookingStatusHistory::query()->create([
        'booking_id' => $pendingBooking2->id,
        'changed_by' => $pendingProvider2->user_id,
        'booking_status' => 'pending_cancellation',
        'booking_provider_cancellation_reason_id' => $reason->id,
        'status_change_remarks' => 'Cannot reach customer — test seed.',
    ]);

    $created[] = [
        'type' => 'pending_cancellation',
        'readable_id' => $pendingBooking2->readable_id,
        'id' => $pendingBooking2->id,
        'provider' => $pendingProvider2->company_name,
        'customer' => trim("{$pendingCustomer2->first_name} {$pendingCustomer2->last_name}"),
    ];
});

$count = Booking::query()->cancelledByProvider()->count();

echo "\n=== Cancelled-by-provider test bookings seeded ===\n\n";
foreach ($created as $row) {
    echo sprintf(
        "  • [%s] #%s — %s / %s\n    %s\n",
        $row['type'],
        $row['readable_id'],
        $row['provider'],
        $row['customer'],
        $row['id'],
    );
}
echo "\nCancelled-by-provider list count (scope): {$count}\n";
echo "Admin: Operations → Booking management → Cancelled by provider\n";
echo "Filter marker in description: {$marker}\n\n";
