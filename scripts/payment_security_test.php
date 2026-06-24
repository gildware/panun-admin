<?php
/**
 * Payment & Security Test Suite
 * Run: php scripts/payment_security_test.php
 */

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Modules\PaymentModule\Entities\PaymentRequest;
use Modules\ProviderManagement\Entities\Provider;
use Modules\UserManagement\Entities\User;

const BASE = 'http://127.0.0.1:8000/api/v1';
const WEB = 'http://127.0.0.1:8000';

$passed = 0;
$failed = 0;
$warnings = [];

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

function warn(string $msg): void
{
    global $warnings;
    $warnings[] = $msg;
    echo "  ⚠ WARN: {$msg}\n";
}

function api(string $method, string $path, ?string $token = null, array $body = [], array $headers = []): array
{
    $req = Http::withHeaders(array_merge([
        'Accept' => 'application/json',
        'Content-Type' => 'application/json',
    ], $headers));
    if ($token) {
        $req = $req->withToken($token);
    }
    $url = str_starts_with($path, 'http') ? $path : BASE . $path;
    $response = match (strtoupper($method)) {
        'GET' => $req->get($url, $body),
        'POST' => $req->post($url, $body),
        'ANY' => Http::withHeaders(array_merge(['Accept' => 'application/json'], $headers))
            ->send('POST', $url, ['json' => $body]),
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
    return $user->createToken('SEC_TEST')->accessToken;
}

echo "\n=== Payment & Security Test Suite ===\n\n";

// ── 1. Payment access token auth ─────────────────────────────────
echo "1. Payment Access Token Endpoints\n";

$noAuthCustomerToken = api('POST', '/customer/payment/access-token', null, []);
if (in_array($noAuthCustomerToken['status'], [401, 403])) {
    ok('Customer payment token requires guest_id when unauthenticated');
} else {
    warn('Customer payment token issued without auth/guest_id (status=' . $noAuthCustomerToken['status'] . ')');
}

$noAuthProviderToken = api('POST', '/provider/payment/access-token', null, []);
if (in_array($noAuthProviderToken['status'], [401, 403])) {
    ok('Provider payment token requires auth');
} else {
    fail('Provider payment token should require auth', 'status=' . $noAuthProviderToken['status']);
}

$customer = User::where('user_type', 'customer')->first();
$providerUser = User::where('user_type', 'provider')->whereHas('provider')->first();

if ($customer) {
    $custToken = tokenFor($customer);
    $authCustomerToken = api('POST', '/customer/payment/access-token', $custToken, []);
    if ($authCustomerToken['status'] === 200 && !empty($authCustomerToken['body']['content']['access_token'])) {
        ok('Authenticated customer receives payment access token');
    } else {
        fail('Authenticated customer payment token', 'status=' . $authCustomerToken['status']);
    }
}

if ($providerUser) {
    $provToken = tokenFor($providerUser);
    $authProviderToken = api('POST', '/provider/payment/access-token', $provToken, []);
    if ($authProviderToken['status'] === 200 && !empty($authProviderToken['body']['content']['access_token'])) {
        ok('Authenticated provider receives payment access token');
    } else {
        fail('Authenticated provider payment token', 'status=' . $authProviderToken['status']);
    }
}

// ── 2. Guest session security ────────────────────────────────────
echo "\n2. Guest Session Security\n";

$guestId = (string) Str::uuid();
$attackerSecret = bin2hex(random_bytes(32));
$legitSecret = bin2hex(random_bytes(32));

// Attacker cannot register without calling guest/session first — payment token should fail
$firstRegister = api('POST', '/customer/payment/access-token', null, ['guest_id' => $guestId], [
    'X-Guest-Secret' => $attackerSecret,
]);
if (in_array($firstRegister['status'], [401, 403])) {
    ok('Payment access token rejected for unregistered guest session');
} else {
    fail('Payment access token should require pre-registered guest session', 'status=' . $firstRegister['status']);
}

// Legitimate registration via guest/session endpoint
$register = api('POST', '/customer/guest/session', null, [
    'guest_id' => $guestId,
    'guest_secret' => $legitSecret,
]);
if ($register['status'] === 200) {
    ok('Guest session registers via dedicated endpoint');
} else {
    fail('Guest session registration failed', 'status=' . $register['status']);
}

$registeredToken = api('POST', '/customer/payment/access-token', null, ['guest_id' => $guestId], [
    'X-Guest-Secret' => $legitSecret,
]);
if ($registeredToken['status'] === 200) {
    ok('Payment access token issued after guest session registration');
} else {
    fail('Payment access token should work after registration', 'status=' . $registeredToken['status']);
}

// Wrong secret rejected after registration
$secondAttempt = api('POST', '/customer/payment/access-token', null, ['guest_id' => $guestId], [
    'X-Guest-Secret' => $attackerSecret,
]);
if ($secondAttempt['status'] === 403) {
    ok('Guest session rejects wrong secret after registration');
} else {
    fail('Guest session should reject wrong secret', 'status=' . $secondAttempt['status']);
}

$shortSecret = api('POST', '/customer/payment/access-token', null, ['guest_id' => (string) Str::uuid()], [
    'X-Guest-Secret' => 'tooshort',
]);
if (in_array($shortSecret['status'], [401, 403])) {
    ok('Guest session rejects secret shorter than 32 chars');
} else {
    fail('Guest session should reject short secret', 'status=' . $shortSecret['status']);
}

// ── 3. Digital payment booking response (unauthenticated) ────────
echo "\n3. Digital Payment Booking Response (Public Endpoint)\n";

$noTxId = api('POST', '/digital-payment-booking-response', null, []);
if ($noTxId['status'] === 400) {
    ok('Digital payment response requires transaction_id');
} else {
    fail('Digital payment response should validate transaction_id', 'status=' . $noTxId['status']);
}

$fakeTx = api('POST', '/digital-payment-booking-response', null, ['transaction_id' => 'nonexistent-tx-' . time()]);
if ($fakeTx['status'] === 204) {
    ok('Unknown transaction_id returns 204 (no data leak)');
} else {
    warn('Unknown transaction_id returned status=' . $fakeTx['status']);
}

$paymentRequest = PaymentRequest::latest()->first();
if ($paymentRequest) {
    $txResponseNoAuth = api('POST', '/digital-payment-booking-response', null, [
        'transaction_id' => $paymentRequest->transaction_id,
    ]);
    if (in_array($txResponseNoAuth['status'], [401, 403])) {
        ok('Digital payment response requires access_token or auth');
    } else {
        fail('Digital payment response should require authorization', 'status=' . $txResponseNoAuth['status']);
    }

    $payerToken = \App\Lib\PaymentAccessToken::issue((string) $paymentRequest->payer_id);
    $txResponse = api('POST', '/digital-payment-booking-response', null, [
        'transaction_id' => $paymentRequest->transaction_id,
        'access_token' => $payerToken,
    ]);
    $content = $txResponse['body']['content'] ?? [];
    if ($txResponse['status'] === 200) {
        $additional = json_decode($paymentRequest->additional_data ?? '{}', true);
        $registerNew = (int) ($additional['register_new_customer'] ?? 0);
        if ($registerNew === 1 && !empty($content['login_token'])) {
            warn('login_token returned to authorized payer for register_new_customer flow (expected for payer only)');
        } elseif (empty($content['login_token'])) {
            ok('No login_token leaked for standard payment when properly authorized');
        }
    } else {
        warn('Authorized digital payment response returned status=' . $txResponse['status']);
    }
} else {
    warn('No payment requests in DB — skipped transaction_id authorization test');
}

// ── 4. Provider withdraw/payment auth ────────────────────────────
echo "\n4. Provider Financial Endpoints Auth\n";

$withdrawNoAuth = api('GET', '/provider/withdraw', null, ['limit' => 10, 'offset' => 1]);
if (in_array($withdrawNoAuth['status'], [401, 403])) {
    ok('Provider withdraw list requires auth');
} else {
    fail('Provider withdraw should require auth', 'status=' . $withdrawNoAuth['status']);
}

$paymentOverviewNoAuth = api('GET', '/provider/payment/overview', null, []);
if (in_array($paymentOverviewNoAuth['status'], [401, 403])) {
    ok('Provider payment overview requires auth');
} else {
    fail('Provider payment overview should require auth', 'status=' . $paymentOverviewNoAuth['status']);
}

$paymentInfoNoAuth = api('GET', '/provider/payment-information/index', null, []);
if (in_array($paymentInfoNoAuth['status'], [401, 403])) {
    ok('Provider payment information requires auth');
} else {
    fail('Provider payment information should require auth', 'status=' . $paymentInfoNoAuth['status']);
}

// Cross-provider isolation
if ($providerUser && $customer) {
    $customerAsProvider = api('GET', '/provider/withdraw', tokenFor($customer), ['limit' => 10, 'offset' => 1]);
    if (in_array($customerAsProvider['status'], [401, 403])) {
        ok('Customer token cannot access provider withdraw');
    } else {
        warn('Customer token accessed provider withdraw (status=' . $customerAsProvider['status'] . ')');
    }
}

// ── 5. Payment initiation without access token ───────────────────
echo "\n5. Payment Initiation Guards\n";

$noTokenPayment = Http::asForm()->post(WEB . '/payment', [
    'is_add_fund' => 1,
    'amount' => 100,
    'payment_method' => 'razor_pay',
    'payment_platform' => 'app',
]);
if (in_array($noTokenPayment->status(), [401, 400, 302])) {
    ok('Add-fund payment rejected without access_token');
} else {
    warn('Add-fund without access_token returned status=' . $noTokenPayment->status());
}

$provider = Provider::first();
if ($provider) {
    $payToAdminNoAuth = Http::withoutRedirecting()->asForm()->post(WEB . '/payment', [
        'is_pay_to_admin' => true,
        'provider_id' => $provider->id,
        'payment_method' => 'razor_pay',
        'payment_platform' => 'app',
    ]);
    if (in_array($payToAdminNoAuth->status(), [401, 403, 302])) {
        ok('Pay-to-admin rejected without provider auth or access token');
    } else {
        fail('Pay-to-admin should require provider auth or access token', 'status=' . $payToAdminNoAuth->status());
    }

    if ($providerUser) {
        $payToAdminAuth = Http::withoutRedirecting()->asForm()->post(WEB . '/payment', [
            'is_pay_to_admin' => true,
            'provider_id' => $provider->id,
            'payment_method' => 'razor_pay',
            'payment_platform' => 'app',
            'access_token' => \App\Lib\PaymentAccessToken::issue($providerUser->id),
        ]);
        if (in_array($payToAdminAuth->status(), [200, 302])) {
            ok('Pay-to-admin allowed with valid provider access token');
        } else {
            warn('Pay-to-admin with valid token returned status=' . $payToAdminAuth->status());
        }
    }
}

// ── 6. Razorpay order endpoint guards ────────────────────────────
echo "\n6. Razorpay Order Endpoint Security\n";

$paymentRequest = PaymentRequest::latest()->first();
if ($paymentRequest) {
    $createNoAuth = Http::withHeaders(['Content-Type' => 'application/json'])
        ->post(WEB . '/payment/razor-pay/create-order', [
            'payment_request_id' => $paymentRequest->id,
            'payment_amount' => $paymentRequest->payment_amount,
            'currency_code' => $paymentRequest->currency_code,
        ]);
    if (in_array($createNoAuth->status(), [401, 403])) {
        ok('Razorpay create-order rejects request without access token');
    } else {
        fail('Razorpay create-order should reject unsigned requests', 'status=' . $createNoAuth->status());
    }

    $payerToken = \App\Lib\PaymentAccessToken::issue((string) $paymentRequest->payer_id);
    $createAuth = Http::withHeaders(['Content-Type' => 'application/json'])
        ->post(WEB . '/payment/razor-pay/create-order', [
            'payment_request_id' => $paymentRequest->id,
            'payment_amount' => $paymentRequest->payment_amount,
            'currency_code' => $paymentRequest->currency_code,
            'access_token' => $payerToken,
        ]);
    if ($createAuth->status() === 200 && ($createAuth->json('status') === true)) {
        ok('Razorpay create-order succeeds with valid payer access token');
    } elseif ((int) ($paymentRequest->is_paid ?? 0) === 1) {
        ok('Razorpay create-order auth check passed (paid request may not create order)');
    } else {
        warn('Razorpay create-order with valid token returned status=' . $createAuth->status());
    }
} else {
    warn('No payment requests in DB — skipped Razorpay create-order test');
}

// ── 7. Razorpay webhook signature ────────────────────────────────
echo "\n7. Razorpay Webhook Security\n";

$webhookNoSig = Http::withHeaders(['Content-Type' => 'application/json'])
    ->post(WEB . '/payment/razor-pay/webhook', ['event' => 'payment.captured', 'payload' => []]);
if (in_array($webhookNoSig->status(), [400, 401, 403, 422])) {
    ok('Razorpay webhook rejects request without valid signature');
} else {
    fail('Razorpay webhook should reject unsigned requests', 'status=' . $webhookNoSig->status());
}

// ── 8. Mass assignment surface check ─────────────────────────────
echo "\n8. Model Security Surface\n";

$userFillable = (new User())->getFillable();
if (in_array('wallet_balance', $userFillable) || in_array('loyalty_point', $userFillable)) {
    fail('User model should not have wallet_balance/loyalty_point in $fillable');
} else {
    ok('User wallet fields not mass-assignable');
}

// ── 9. Wallet payment auth (customer) ────────────────────────────
echo "\n9. Customer Wallet Payment Auth\n";

if ($customer) {
    $walletSwitch = api('POST', '/customer/booking/switch-payment-method', tokenFor($customer), [
        'booking_id' => '00000000-0000-0000-0000-000000000000',
        'payment_method' => 'wallet_payment',
        'is_partial' => 0,
    ]);
    if (in_array($walletSwitch['status'], [400, 403, 404, 204])) {
        ok('Wallet payment switch rejects invalid/foreign booking');
    } else {
        warn('Wallet payment switch returned status=' . $walletSwitch['status'] . ' for fake booking');
    }
}

// ── Summary ───────────────────────────────────────────────────────
echo "\n=== SUMMARY ===\n";
echo "Passed: {$passed}\n";
echo "Failed: {$failed}\n";
echo "Warnings: " . count($warnings) . "\n";

if (count($warnings) > 0) {
    echo "\nWarnings:\n";
    foreach ($warnings as $w) {
        echo "  - {$w}\n";
    }
}

echo "\n";
exit($failed > 0 ? 1 : 0);
