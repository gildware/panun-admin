<?php

/**
 * Full user-journey E2E for mobile app AI chat.
 * Simulates a real customer using short / Hinglish messages (not full sentences).
 *
 * Run: php scripts/ai_chat_e2e_test.php
 */

require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Carbon\Carbon;
use Modules\BusinessSettingsModule\Entities\MobileAppAiConversation;
use Modules\BusinessSettingsModule\Services\MobileAppAiCartService;
use Modules\BusinessSettingsModule\Services\MobileAppAiChatBookingService;
use Modules\BusinessSettingsModule\Services\MobileAppAiSupportService;
use Modules\CartModule\Entities\Cart;
use Modules\CartModule\Entities\CartServiceInfo;
use Modules\ServiceManagement\Entities\Service;
use Modules\UserManagement\Entities\User;

// ── Harness ──────────────────────────────────────────────────────────────────

final class AiChatE2eHarness
{
    private int $passed = 0;

    private int $failed = 0;

    /** @var list<string> */
    private array $transcript = [];

    public function __construct(
        private readonly User $user,
        private readonly MobileAppAiSupportService $support,
        private readonly MobileAppAiChatBookingService $booking,
        private readonly MobileAppAiCartService $cartService,
    ) {}

    public function resetConversation(): void
    {
        $this->support->clearConversation($this->user);
    }

    public function resetDraft(): void
    {
        $conv = MobileAppAiConversation::query()->firstOrCreate(
            ['user_id' => $this->user->id],
            ['last_message_at' => now()]
        );
        $conv->booking_draft = ['step' => 'idle', 'choices' => [], 'options' => []];
        $conv->save();
    }

    /**
     * @return array{reply: string, ui: mixed, cart_updated: bool, step: string}
     */
    public function say(string $message): array
    {
        $out = $this->support->sendMessage($this->user, $message);
        $conv = MobileAppAiConversation::query()->where('user_id', $this->user->id)->first();
        $draft = is_array($conv?->booking_draft) ? $conv->booking_draft : [];
        $step = (string) ($draft['step'] ?? 'idle');
        $reply = (string) ($out['reply'] ?? '');

        $this->transcript[] = '👤 '.$message;
        $this->transcript[] = '🤖 '.mb_substr($reply, 0, 320).($step !== 'idle' ? " [step={$step}]" : '');

        return [
            'reply' => $reply,
            'ui' => $out['ui'] ?? null,
            'cart_updated' => ($out['cart_updated'] ?? false) === true,
            'step' => $step,
        ];
    }

    /**
     * @return array{reply: string, ui: mixed, cart_updated: bool, ok: bool}
     */
    public function tap(string $action, array $extra = []): array
    {
        $payload = array_merge(['action' => $action, 'persist_chat_messages' => false], $extra);
        $out = $this->booking->handleAction($this->user, $payload);
        $reply = (string) ($out['reply'] ?? '');

        $this->transcript[] = '👆 tap:'.$action;
        $this->transcript[] = '🤖 '.mb_substr($reply, 0, 320);

        return [
            'reply' => $reply,
            'ui' => $out['ui'] ?? null,
            'cart_updated' => ($out['cart_updated'] ?? false) === true,
            'ok' => ($out['ok'] ?? false) === true,
        ];
    }

    /**
     * @param  callable(array{reply: string, ui: mixed, cart_updated: bool, step: string}): void  $fn
     */
    public function phase(string $title, callable $fn): void
    {
        echo "\n══ {$title} ══\n";
        try {
            $fn($this);
            $this->pass($title);
        } catch (Throwable $e) {
            $this->fail($title, $e->getMessage());
        }
    }

    public function assert(bool $ok, string $message): void
    {
        if (! $ok) {
            throw new RuntimeException($message);
        }
    }

    public function assertContains(string $haystack, string $needle, string $label): void
    {
        if (! str_contains(mb_strtolower($haystack), mb_strtolower($needle))) {
            throw new RuntimeException("{$label}: expected reply to contain \"{$needle}\"");
        }
    }

    public function assertNotContains(string $haystack, string $needle, string $label): void
    {
        if (str_contains(mb_strtolower($haystack), mb_strtolower($needle))) {
            throw new RuntimeException("{$label}: reply must not contain \"{$needle}\"");
        }
    }

    public function assertStep(string $expected, string $actual, string $label): void
    {
        if ($expected !== $actual) {
            throw new RuntimeException("{$label}: expected step \"{$expected}\", got \"{$actual}\"");
        }
    }

    /**
     * @return list<string>
     */
    public function cartServiceNames(): array
    {
        $summary = $this->cartService->cartSummaryForUser($this->user);

        return array_values(array_map(
            static fn (array $item): string => (string) ($item['service_name'] ?? ''),
            $summary['items'] ?? []
        ));
    }

    public function pass(string $label): void
    {
        $this->passed++;
        echo "[PASS] {$label}\n";
    }

    public function fail(string $label, string $reason): void
    {
        $this->failed++;
        echo "[FAIL] {$label}: {$reason}\n";
    }

    public function summary(): int
    {
        echo "\n── Transcript ──\n";
        foreach ($this->transcript as $line) {
            echo $line."\n";
        }
        echo "\n{$this->passed} passed, {$this->failed} failed\n";

        return $this->failed;
    }
}

// ── Cart fixture ─────────────────────────────────────────────────────────────

/**
 * @return array{zone_id: string, address_id: int, ac: array<string, string>, inverter: array<string, string>}
 */
function e2eCartCatalog(): array
{
    $zoneId = 'a7885552-f5b2-41b5-94c9-2a197b7b2e80';

    return [
        'zone_id' => $zoneId,
        'address_id' => 5,
        'ac' => [
            'service_id' => 'e228f94a-9461-4b93-b5f7-6f1da920ddd0',
            'variant_key' => 'Book-at-Home-Consultation',
            'category_id' => '028602bc-174a-41f9-b583-ae8f4850e646',
            'sub_category_id' => '716233b9-7954-4262-a79e-8df58a6a3090',
            'name' => 'AC Repair',
        ],
        'inverter' => [
            'service_id' => '74b7ef0d-a3ff-4770-825a-87f295b7a9e7',
            'variant_key' => 'Inverter-Installation',
            'category_id' => '028602bc-174a-41f9-b583-ae8f4850e646',
            'sub_category_id' => '305eba7c-9de0-473f-b46a-0d22a0517ff0',
            'name' => 'Inverter Installation',
        ],
    ];
}

function seedCart(
    User $user,
    MobileAppAiCartService $cartService,
    bool $duplicateAc = false,
): void {
    $cat = e2eCartCatalog();
    $customerId = (string) $user->id;

    Cart::query()->where('customer_id', $customerId)->delete();
    CartServiceInfo::query()->where('customer_id', $customerId)->delete();

    $base = [
        'zone_id' => $cat['zone_id'],
        'service_address_id' => $cat['address_id'],
        'schedule_type' => 'custom',
        'quantity' => 1,
    ];

    $add = static function (array $svc, int $daysAhead) use ($cartService, $user, $base, $cat): void {
        $result = $cartService->addServiceForUser($user, array_merge($base, [
            'service_id' => $svc['service_id'],
            'variant_key' => $svc['variant_key'],
            'category_id' => $cat['ac']['category_id'],
            'sub_category_id' => $svc['sub_category_id'],
            'service_schedule' => Carbon::now()->addDays($daysAhead)->format('Y-m-d H:i:s'),
        ]));
        if (! ($result['ok'] ?? false)) {
            throw new RuntimeException('Failed to seed '.$svc['name'].': '.json_encode($result));
        }
    };

    $add($cat['ac'], 3);
    if ($duplicateAc) {
        $add($cat['ac'], 5);
    }
    $add($cat['inverter'], 10);
}

// ── Main ─────────────────────────────────────────────────────────────────────

$user = User::query()->where('user_type', 'customer')->orderBy('id')->first();
if (! $user) {
    fwrite(STDERR, "No customer user in DB.\n");
    exit(1);
}

$support = app(MobileAppAiSupportService::class);
if (! $support->isEnabled(true)) {
    fwrite(STDERR, "AI chat blocked — Gemini health check failed.\n");
    exit(1);
}

$booking = app(MobileAppAiChatBookingService::class);
$cartService = app(MobileAppAiCartService::class);
$h = new AiChatE2eHarness($user, $support, $booking, $cartService);

echo "Customer: {$user->id}\n";
echo "AI enabled: yes\n";
echo "Mode: short / Hinglish user messages (realistic typing)\n";

// ── Phase 1: Greeting ────────────────────────────────────────────────────────

$h->phase('1 · casual hello', function (AiChatE2eHarness $t) {
    $t->resetConversation();
    $out = $t->say('hi');
    $t->assertNotContains($out['reply'], "didn't catch", 'hello');
    $t->assertNotContains($out['reply'], 'Something went wrong', 'hello');
    $t->assertContains($out['reply'], 'Panun', 'hello');
});

// ── Phase 2: Booking (short messages) ────────────────────────────────────────

$h->phase('2 · book with short Hinglish', function (AiChatE2eHarness $t) {
    $t->resetConversation();
    $t->resetDraft();

    $out = $t->say('book karo');
    $t->assertStep('service_query', $out['step'], 'book karo');
    $t->assertContains($out['reply'], 'service', 'book karo');

    $out = $t->say('AC repair');
    $t->assertNotContains($out['reply'], "didn't catch", 'AC repair');
    $t->assert(
        str_contains(mb_strtolower($out['reply']), 'ac')
            || $out['step'] === 'service_triage'
            || $out['step'] === 'service',
        'AC repair should start triage or service options'
    );
});

$h->phase('3 · problem description (not full sentence)', function (AiChatE2eHarness $t) {
    $t->resetConversation();
    $t->resetDraft();

    $t->say('book karo');
    $out = $t->say('tap leak');
    $t->assertNotContains($out['reply'], "didn't catch", 'tap leak');
    $t->assert(
        str_contains(mb_strtolower($out['reply']), 'plumb')
            || str_contains(mb_strtolower($out['reply']), 'leak')
            || str_contains(mb_strtolower($out['reply']), 'water')
            || $out['step'] === 'service',
        'tap leak should route to plumbing / leak help'
    );
});

// ── Phase 3: Booking status ──────────────────────────────────────────────────

$h->phase('4 · booking status (English short)', function (AiChatE2eHarness $t) {
    $t->resetConversation();
    $t->resetDraft();

    $out = $t->say('booking status');
    $t->assertNotContains($out['reply'], "didn't catch", 'booking status');
    $t->assertNotContains($out['reply'], 'What service do you need', 'booking status');
    $t->assert(
        str_contains(mb_strtolower($out['reply']), 'booking')
            || str_contains(mb_strtolower($out['reply']), 'book a service'),
        'booking status should mention bookings or offer to book'
    );
});

// ── Phase 4: Cart view & pricing (seeded cart) ───────────────────────────────

$h->phase('5 · view cart (cart mein kya)', function (AiChatE2eHarness $t) use ($user, $cartService) {
    seedCart($user, $cartService);
    $t->resetConversation();
    $t->resetDraft();

    $out = $t->say('cart mein kya');
    $t->assertNotContains($out['reply'], "didn't catch", 'cart view');
    $t->assertContains($out['reply'], 'AC Repair', 'cart view');
    $t->assertContains($out['reply'], 'Inverter Installation', 'cart view');
    $t->assert(is_array($out['ui']) && ($out['ui']['type'] ?? '') === 'cart_summary', 'cart view UI');
});

$h->phase('6 · cart total (kitna hoga)', function (AiChatE2eHarness $t) use ($user, $cartService) {
    seedCart($user, $cartService);
    $t->resetConversation();
    $t->resetDraft();

    $out = $t->say('kitna hoga');
    $t->assertContains($out['reply'], '₹', 'pricing');
    $t->assertContains($out['reply'], 'AC Repair', 'pricing');
    $t->assertContains($out['reply'], 'Inverter', 'pricing');
});

// ── Phase 5: Cart remove (Hinglish short) ────────────────────────────────────

$h->phase('7 · remove inverter wali (short)', function (AiChatE2eHarness $t) use ($user, $cartService) {
    seedCart($user, $cartService);
    $t->resetConversation();
    $t->resetDraft();

    $out = $t->say('inverter wali hatao');
    $t->assertStep('cart_confirm', $out['step'], 'remove inverter');
    $t->assertNotContains($out['reply'], 'nahi mila', 'remove inverter');
    $t->assertContains($out['reply'], 'Inverter', 'remove inverter confirm');
    $t->assert(is_array($out['ui']) && ($out['ui']['type'] ?? '') === 'cart_confirm', 'confirm UI');
});

$h->phase('8 · confirm remove via Haan button', function (AiChatE2eHarness $t) use ($user, $cartService) {
    seedCart($user, $cartService);
    $t->resetConversation();
    $t->resetDraft();

    $pending = $t->say('inverter wali delete karo');
    $t->assertStep('cart_confirm', $pending['step'], 'before confirm');
    $t->assertNotContains($pending['reply'], 'nahi mila', 'before confirm');

    $out = $t->tap('confirm_cart_action');
    $t->assert($out['cart_updated'], 'cart should update after confirm');
    $t->assertContains($out['reply'], 'removed', 'after confirm');

    $names = $t->cartServiceNames();
    $t->assert(! in_array('Inverter Installation', $names, true), 'inverter should be gone');
    $t->assert(in_array('AC Repair', $names, true), 'AC should remain');
});

$h->phase('9 · cancel remove via Nahi', function (AiChatE2eHarness $t) use ($user, $cartService) {
    seedCart($user, $cartService);
    $t->resetConversation();
    $t->resetDraft();

    $before = $t->cartServiceNames();
    $t->say('inverter ko hatao');
    $out = $t->tap('cancel_cart_action');
    $t->assert(!$out['cart_updated'], 'cancel should not update cart');
    $after = $t->cartServiceNames();
    $t->assert($before === $after, 'cart unchanged after cancel');
});

// ── Phase 6: Keep-one duplicate AC ───────────────────────────────────────────

$h->phase('10 · duplicate AC remove (screenshot phrases)', function (AiChatE2eHarness $t) use ($user, $cartService) {
    seedCart($user, $cartService, duplicateAc: true);
    $t->resetConversation();
    $t->resetDraft();

    $out = $t->say('AC wale ko remove karo');
    $t->assertNotContains($out['reply'], 'nahi mila', 'AC wale remove');
    $t->assert(
        ($out['ui']['type'] ?? '') === 'cart_line_pick'
            || str_contains(mb_strtolower($out['reply']), 'which')
            || str_contains($out['reply'], 'Kaunsi')
            || $out['step'] === 'cart_confirm',
        'duplicate AC should ask which line or confirm remove'
    );

    $t->resetConversation();
    $t->resetDraft();
    seedCart($user, $cartService, duplicateAc: true);

    $out2 = $t->say('AC wali serviceko remove karo');
    $t->assertNotContains($out2['reply'], 'nahi mila', 'AC wali serviceko');
    $t->assert($out2['reply'] !== '', 'AC wali serviceko should get a real response');
});

$h->phase('11 · keep one AC, remove rest (short)', function (AiChatE2eHarness $t) use ($user, $cartService) {
    seedCart($user, $cartService, duplicateAc: true);
    $t->resetConversation();
    $t->resetDraft();

    $out = $t->say('ek hi ac rakho baki hata');
    $t->assertStep('cart_confirm', $out['step'], 'keep one');
    $t->assertNotContains($out['reply'], 'nahi mila', 'keep one');
    $t->assertContains($out['reply'], 'AC', 'keep one confirm');
});

// ── Phase 7: Service info short ──────────────────────────────────────────────

$h->phase('12 · service info (AC repair?)', function (AiChatE2eHarness $t) {
    $t->resetConversation();
    $t->resetDraft();

    $out = $t->say('AC repair?');
    $t->assertNotContains($out['reply'], "didn't catch", 'service info');
    $t->assert(
        str_contains(mb_strtolower($out['reply']), 'ac')
            || str_contains(mb_strtolower($out['reply']), 'book'),
        'AC repair? should explain or offer booking'
    );
});

// ── Phase 8: Unsupported request ─────────────────────────────────────────────

$h->phase('13 · unsupported (laptop)', function (AiChatE2eHarness $t) {
    $t->resetConversation();
    $t->resetDraft();

    $out = $t->say('laptop kharab');
    $t->assertNotContains($out['reply'], "didn't catch", 'unsupported');
    $t->assert(
        str_contains(mb_strtolower($out['reply']), "don't offer")
            || str_contains(mb_strtolower($out['reply']), 'not offer')
            || str_contains(mb_strtolower($out['reply']), 'laptop'),
        'should decline laptop politely'
    );
});

exit($h->summary() > 0 ? 1 : 0);
