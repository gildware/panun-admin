<?php

/**
 * Smoke-test mobile app AI chat. Run: php scripts/ai_chat_smoke_test.php
 */

require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Modules\BusinessSettingsModule\Entities\MobileAppAiConversation;
use Modules\BusinessSettingsModule\Services\MobileAppAiOrchestrator;
use Modules\BusinessSettingsModule\Services\MobileAppAiSupportService;
use Modules\UserManagement\Entities\User;

$user = User::query()->where('user_type', 'customer')->orderBy('id')->first();
if (! $user) {
    fwrite(STDERR, "No customer user in DB.\n");
    exit(1);
}

$support = app(MobileAppAiSupportService::class);

echo "Customer: {$user->id}\n";
echo 'AI enabled: '.($support->isEnabled(true) ? 'yes' : 'no')."\n\n";

if (! $support->isEnabled(true)) {
    fwrite(STDERR, "Gemini health check failed — chat is correctly blocked.\n");
    exit(1);
}

function resetDraft(User $user): void
{
    $conv = MobileAppAiConversation::query()->firstOrCreate(
        ['user_id' => $user->id],
        ['last_message_at' => now()]
    );
    $conv->booking_draft = ['step' => 'idle', 'options' => [], 'choices' => []];
    $conv->save();
}

/** @var list<array{label: string, input: string, expect_step?: string, expect_contains?: list<string>, expect_not_contains?: list<string>}> */
$scenarios = [
    [
        'label' => 'start booking',
        'input' => 'Book a service',
        'expect_step' => 'service_query',
        'expect_contains' => ['What service'],
        'expect_not_contains' => ['options for **Plumbing**', 'Geyser Installation'],
    ],
    [
        'label' => 'plumbing problem (idle)',
        'input' => 'my tap is leaking',
        'expect_step' => 'service_triage',
        'expect_contains' => ['Sorry', 'hear', 'Have you tried', 'troubleshoot', 'booking'],
        'expect_not_contains' => ["didn't catch", 'Geyser Installation', 'Pick the service', 'Sounds like **plumbing**'],
    ],
    [
        'label' => 'unsupported laptop',
        'input' => 'Laptop Not Working',
        'expect_contains' => ["don't offer", 'laptop'],
        'expect_not_contains' => ['booking tab', "didn't catch"],
    ],
    [
        'label' => 'AC repair triage',
        'input' => 'AC repair',
        'expect_step' => 'service_triage',
        'expect_contains' => ['AC', 'cooling', 'wrong'],
        'expect_not_contains' => ['Pick the service', 'service_confirm', 'Book **'],
    ],
    [
        'label' => 'booking status',
        'input' => 'my booking status',
        'expect_contains' => ['booking'],
        'expect_not_contains' => ['What service do you need'],
    ],
    [
        'label' => 'hello',
        'input' => 'hello',
        'expect_contains' => ['Panun Kaergar'],
    ],
    [
        'label' => 'cart pricing',
        'input' => 'what will be total charges',
        'expect_contains' => ['cart', 'Visiting', '₹'],
        'expect_not_contains' => ["didn't catch", 'Something went wrong'],
    ],
    [
        'label' => 'coupon confirm',
        'input' => 'apply coupon TESTCODE',
        'expect_step' => 'coupon_confirm',
        'expect_contains' => ['TESTCODE', 'confirm', 'Apply'],
    ],
    [
        'label' => 'bidding list',
        'input' => 'my biddings',
        'expect_contains' => ['bidding', 'post'],
        'expect_not_contains' => ["didn't catch", 'What app issue'],
    ],
    [
        'label' => 'service details',
        'input' => 'tell me about AC repair',
        'expect_contains' => ['AC', 'book'],
        'expect_not_contains' => ["didn't catch", 'Something went wrong'],
    ],
    [
        'label' => 'cancel booking',
        'input' => 'cancel my booking',
        'expect_contains' => ['booking'],
        'expect_not_contains' => ["didn't catch", 'Something went wrong'],
    ],
    [
        'label' => 'cart view hinglish',
        'input' => 'cart mein kya hai',
        'expect_contains' => ['cart'],
        'expect_not_contains' => ["didn't catch", 'Something went wrong'],
    ],
    [
        'label' => 'keep one ac repair',
        'input' => 'ek hi AC repair rakho cart mein rakho baki remove karo',
        'expect_not_contains' => ["didn't catch", 'Something went wrong'],
        'expect_any_contains' => ['AC', 'cart', 'empty', 'khali', 'confirm', 'Haan'],
    ],
    [
        'label' => 'past date remove hinglish',
        'input' => 'jo past date ki services hai unko remove karo',
        'expect_not_contains' => ["didn't catch", 'Something went wrong'],
        'expect_any_contains' => ['past', 'remove', 'cart', 'empty', 'khali', 'confirm'],
    ],
    [
        'label' => 'inverter ko hatao',
        'input' => 'inverter installation ko hatao wahan se',
        'expect_not_contains' => ["didn't catch", 'nahi mila'],
        'expect_any_contains' => ['Inverter', 'inverter', 'remove', 'confirm', 'empty', 'khali', 'Haan'],
    ],
    [
        'label' => 'inverter wali delete',
        'input' => 'inverter wali ko delete karo',
        'expect_not_contains' => ["didn't catch", 'nahi mila'],
        'expect_any_contains' => ['Inverter', 'inverter', 'remove', 'confirm', 'empty', 'khali', 'Haan'],
    ],
];

$failed = 0;
$passed = 0;

foreach ($scenarios as $scenario) {
    resetDraft($user);
    $conv = MobileAppAiConversation::query()->where('user_id', $user->id)->first();
    $out = $support->sendMessage($user, $scenario['input']);
    $conv = $conv->fresh();
    $draft = is_array($conv->booking_draft) ? $conv->booking_draft : [];
    $step = (string) ($draft['step'] ?? 'idle');
    $reply = (string) ($out['reply'] ?? '');
    $ok = true;
    $reasons = [];

    if (isset($scenario['expect_step']) && $step !== $scenario['expect_step']) {
        $ok = false;
        $reasons[] = "step expected {$scenario['expect_step']}, got {$step}";
    }
    foreach ($scenario['expect_contains'] ?? [] as $needle) {
        if (! str_contains($reply, $needle) && ! str_contains($step, $needle)) {
            $ok = false;
            $reasons[] = "missing: {$needle}";
        }
    }
    foreach ($scenario['expect_not_contains'] ?? [] as $needle) {
        if (str_contains($reply, $needle)) {
            $ok = false;
            $reasons[] = "should not contain: {$needle}";
        }
    }
    if (isset($scenario['expect_any_contains'])) {
        $any = false;
        foreach ($scenario['expect_any_contains'] as $needle) {
            if (str_contains($reply, $needle) || str_contains($step, $needle)) {
                $any = true;
                break;
            }
        }
        if (! $any) {
            $ok = false;
            $reasons[] = 'missing any of: '.implode(', ', $scenario['expect_any_contains']);
        }
    }

    if ($ok) {
        $passed++;
        echo "[PASS] {$scenario['label']}\n";
    } else {
        $failed++;
        echo "[FAIL] {$scenario['label']}: ".implode('; ', $reasons)."\n";
        echo '  reply: '.mb_substr($reply, 0, 180)."\n";
        echo "  step: {$step}\n";
    }
}

// Chain: book -> tap leak
resetDraft($user);
$conv = MobileAppAiConversation::query()->where('user_id', $user->id)->first();
$support->sendMessage($user, 'Book a service');
$out2 = $support->sendMessage($user, 'Tap Leaking Water');
$reply2 = (string) ($out2['reply'] ?? '');
$conv = MobileAppAiConversation::query()->where('user_id', $user->id)->first();
$step2 = (string) (($conv?->fresh()->booking_draft ?? [])['step'] ?? '');
$chainOk = str_contains($reply2, 'plumbing')
    || str_contains($reply2, 'Book this service')
    || str_contains($reply2, 'Water Leakage')
    || str_contains($reply2, 'leak');
if ($chainOk) {
    $passed++;
    echo "[PASS] chain book then tap leak\n";
} else {
    $failed++;
    echo "[FAIL] chain: {$reply2} (step {$step2})\n";
}

echo "\n{$passed} passed, {$failed} failed\n";
exit($failed > 0 ? 1 : 0);
