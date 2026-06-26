<?php
/**
 * Provider withdrawal (cancelled by provider) E2E test.
 * Run: php scripts/provider_withdrawal_e2e_test.php
 */

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Modules\BookingModule\Entities\Booking;
use Modules\BookingModule\Entities\BookingIgnore;
use Modules\BookingModule\Entities\BookingProviderCancellationReason;
use Modules\BookingModule\Entities\BookingStatusHistory;
use Modules\ProviderManagement\Entities\Provider;
use Modules\ProviderManagement\Http\Controllers\Web\Admin\ProviderController;
use Modules\TransactionModule\Entities\Transaction;
use Modules\UserManagement\Entities\User;

const BASE = 'http://127.0.0.1:8000/api/v1';

$passed = 0;
$failed = 0;

function ok(string $name): void
{
    global $passed;
    $passed++;
    echo "  ✓ PASS: {$name}\n";
}

function fail(string $name, string $detail = ''): void
{
    global $failed;
    $failed++;
    echo "  ✗ FAIL: {$name}" . ($detail ? " — {$detail}" : '') . "\n";
}

function api(string $method, string $path, ?string $token = null, array $body = []): array
{
    $req = Http::withHeaders([
        'Accept' => 'application/json',
        'Content-Type' => 'application/json',
    ]);
    if ($token) {
        $req = $req->withToken($token);
    }
    $url = BASE . $path;
    $response = match (strtoupper($method)) {
        'GET' => $req->get($url, $body),
        'PUT' => $req->put($url, $body),
        'POST' => $req->post($url, $body),
        default => throw new InvalidArgumentException("Unsupported method {$method}"),
    };

    return [
        'status' => $response->status(),
        'body' => $response->json() ?? [],
        'raw' => $response->body(),
    ];
}

function tokenFor(User $user): string
{
    return $user->createToken('PROVIDER_WITHDRAWAL_E2E')->accessToken;
}

echo "\n=== Provider Withdrawal E2E Test ===\n\n";

// Health check
$ping = Http::get('http://127.0.0.1:8000');
if (! $ping->successful() && $ping->status() !== 302) {
    echo "API server not reachable at http://127.0.0.1:8000 — start php artisan serve\n";
    exit(1);
}
ok('Local server reachable');

$booking = Booking::query()
    ->where('booking_status', 'accepted')
    ->whereNotNull('provider_id')
    ->whereNull('provider_cancelled_at')
    ->orderByDesc('updated_at')
    ->get()
    ->first(function (Booking $candidate) {
        $owner = Provider::with('owner')->find($candidate->provider_id)?->owner;

        return $owner instanceof User;
    });

if (! $booking) {
    echo "No accepted booking with provider found. Aborting.\n";
    exit(1);
}

$provider = Provider::with('owner')->find($booking->provider_id);
$reason = BookingProviderCancellationReason::query()->active()->orderBy('id')->first();
$admin = User::query()->whereIn('user_type', ['super-admin', 'admin-employee'])->first();
$replacementProvider = Provider::query()
    ->where('is_active', 1)
    ->where('id', '!=', $provider?->id)
    ->orderBy('company_name')
    ->first();

if (! $provider?->owner || ! $reason || ! $admin || ! $replacementProvider) {
    echo "Missing test prerequisites (provider owner, cancel reason, admin, replacement provider).\n";
    exit(1);
}

echo "Booking: #{$booking->readable_id} ({$booking->id})\n";
echo "Provider: {$provider->company_name}\n";
echo "Cancel reason: {$reason->name} (#{$reason->id})\n";
echo "Replacement provider: {$replacementProvider->company_name}\n\n";

$token = tokenFor($provider->owner);
$refundCountBefore = Transaction::query()
    ->where('booking_id', $booking->id)
    ->where('trx_type', 'refund')
    ->count();

// 1) Provider fetches cancellation reasons
echo "1. Provider API — cancellation reasons\n";
$reasonsRes = api('GET', '/provider/booking/provider-cancellation-reasons', $token);
if ($reasonsRes['status'] === 200 && ($reasonsRes['body']['response_code'] ?? '') === 'default_200') {
    ok('Provider can load cancellation reasons');
} else {
    fail('Provider cancellation reasons', 'status=' . $reasonsRes['status'] . ' body=' . json_encode($reasonsRes['body']));
}

// 2) Provider withdraws from accepted booking
echo "2. Provider API — withdraw (status canceled on accepted booking)\n";
$withdrawRes = api('PUT', '/provider/booking/status-update/' . $booking->id, $token, [
    'booking_status' => 'canceled',
    'booking_provider_cancellation_reason_id' => $reason->id,
    'status_change_remarks' => 'E2E provider withdrawal test',
]);
$booking->refresh();

if ($withdrawRes['status'] === 200 && ($withdrawRes['body']['response_code'] ?? '') === 'status_update_success_200') {
    ok('Provider withdrawal API returns success');
} else {
    fail('Provider withdrawal API', 'status=' . $withdrawRes['status'] . ' body=' . json_encode($withdrawRes['body']));
}

if ($booking->booking_status === 'pending') {
    ok('Booking status is pending (not fully canceled)');
} else {
    fail('Booking status after withdrawal', 'status=' . $booking->booking_status);
}

if ($booking->provider_id === null) {
    ok('Provider removed from booking');
} else {
    fail('Provider still assigned', 'provider_id=' . $booking->provider_id);
}

if ($booking->provider_cancelled_at !== null) {
    ok('provider_cancelled_at timestamp set');
} else {
    fail('provider_cancelled_at not set');
}

if ((string) $booking->provider_cancelled_by_provider_id === (string) $provider->id) {
    ok('provider_cancelled_by_provider_id recorded');
} else {
    fail('provider_cancelled_by_provider_id mismatch');
}

$refundCountAfter = Transaction::query()
    ->where('booking_id', $booking->id)
    ->where('trx_type', 'refund')
    ->count();
if ($refundCountAfter === $refundCountBefore) {
    ok('No refund transaction created');
} else {
    fail('Refund was created', "before={$refundCountBefore} after={$refundCountAfter}");
}

$history = BookingStatusHistory::query()
    ->where('booking_id', $booking->id)
    ->whereNull('booking_repeat_id')
    ->where('booking_provider_cancellation_reason_id', $reason->id)
    ->latest('id')
    ->first();
if ($history) {
    ok('Withdrawal recorded in booking status history');
} else {
    fail('Missing provider cancellation status history');
}

$ignored = BookingIgnore::query()
    ->where('booking_id', $booking->id)
    ->where('provider_id', $provider->id)
    ->exists();
if ($ignored) {
    ok('Provider added to booking ignore list');
} else {
    fail('Provider not in booking ignore list');
}

// 3) Admin cancelled-by-provider list query
echo "3. Admin list — cancelledByProvider scope\n";
$listCount = Booking::query()->cancelledByProvider()->where('id', $booking->id)->count();
if ($listCount === 1) {
    ok('Booking appears in Cancelled by provider list');
} else {
    fail('Booking missing from Cancelled by provider list', 'count=' . $listCount);
}

$menuCount = Booking::query()->cancelledByProvider()->count();
if ($menuCount >= 1) {
    ok('Cancelled by provider menu count >= 1 (' . $menuCount . ')');
} else {
    fail('Cancelled by provider menu count', 'count=' . $menuCount);
}

// 4) Provider no longer sees booking details
echo "4. Provider API — booking no longer accessible\n";
$detailsRes = api('GET', '/provider/booking/' . $booking->id, $token);
if ($detailsRes['status'] === 200 && ($detailsRes['body']['response_code'] ?? '') === 'default_204') {
    ok('Provider receives 204 after withdrawal (removed from their list)');
} else {
    fail('Provider still has booking access', 'status=' . $detailsRes['status'] . ' code=' . ($detailsRes['body']['response_code'] ?? 'n/a'));
}

// 5) Admin reassigns a new provider
echo "5. Admin — reassign provider\n";
auth()->login($admin);
$reassignRequest = Request::create(
    '/admin/provider/reassign-provider/' . $booking->id,
    'PUT',
    ['provider_id' => $replacementProvider->id, 'booking_id' => $booking->id]
);
$reassignRequest->setUserResolver(fn () => $admin);
$reassignResponse = app(ProviderController::class)->reassignProvider($reassignRequest);
$booking->refresh();

if ($reassignResponse->getStatusCode() === 200) {
    ok('Admin reassigned provider successfully');
} else {
    fail('Admin reassign provider', 'status=' . $reassignResponse->getStatusCode());
}

if ((string) $booking->provider_id === (string) $replacementProvider->id) {
    ok('New provider assigned on booking');
} else {
    fail('New provider not assigned', 'provider_id=' . ($booking->provider_id ?? 'null'));
}

if ($booking->booking_status === 'accepted') {
    ok('Booking status is accepted after reassignment');
} else {
    fail('Booking status after reassignment', 'status=' . $booking->booking_status);
}

if ($booking->provider_cancelled_at === null && $booking->provider_cancelled_by_provider_id === null) {
    ok('Withdrawal flags cleared after reassignment');
} else {
    fail('Withdrawal flags not cleared');
}

$listCountAfter = Booking::query()->cancelledByProvider()->where('id', $booking->id)->count();
if ($listCountAfter === 0) {
    ok('Booking removed from Cancelled by provider list after reassignment');
} else {
    fail('Booking still in Cancelled by provider list', 'count=' . $listCountAfter);
}

echo "\n=== Results: {$passed} passed, {$failed} failed ===\n\n";
exit($failed > 0 ? 1 : 0);
