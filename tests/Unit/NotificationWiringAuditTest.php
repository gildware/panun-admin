<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RegexIterator;

class NotificationWiringAuditTest extends TestCase
{
    private static ?string $codebase = null;

    private static function codebase(): string
    {
        if (self::$codebase === null) {
            $root = dirname(__DIR__, 2);
            $dirs = ['app', 'Modules'];
            $chunks = [];
            foreach ($dirs as $dir) {
                $path = $root . '/' . $dir;
                if (! is_dir($path)) {
                    continue;
                }
                $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($path));
                $php = new RegexIterator($iterator, '/\.php$/');
                foreach ($php as $file) {
                    $chunks[] = (string) file_get_contents($file->getPathname());
                }
            }
            self::$codebase = implode("\n", $chunks);
        }

        return self::$codebase;
    }

    private function assertKeyWiredInCode(string $key, string $audience): void
    {
        $haystack = self::codebase();
        $patterns = [
            "'{$key}'",
            "\"{$key}\"",
            "'key' => '{$key}'",
        ];

        $found = false;
        foreach ($patterns as $pattern) {
            if (str_contains($haystack, $pattern)) {
                $found = true;
                break;
            }
        }

        $this->assertTrue(
            $found,
            "Notification key [{$key}] for {$audience} has no matching trigger reference in app/ or Modules/."
        );
    }

    public function test_all_customer_notification_keys_are_wired_in_code(): void
    {
        foreach (NOTIFICATION_FOR_USER as $notification) {
            $this->assertKeyWiredInCode($notification['key'], 'customer');
        }
    }

    public function test_all_provider_notification_keys_are_wired_in_code(): void
    {
        foreach (NOTIFICATION_FOR_PROVIDER as $notification) {
            $this->assertKeyWiredInCode($notification['key'], 'provider');
        }
    }

    public function test_payment_and_wallet_helpers_exist(): void
    {
        $this->assertTrue(function_exists('send_booking_payment_collected_notifications'));
        $this->assertTrue(function_exists('send_customer_payment_failed_notification'));
        $this->assertTrue(function_exists('send_customer_wallet_deducted_notification'));
    }

    public function test_failure_hooks_call_payment_failed_sender(): void
    {
        $paymentSuccess = file_get_contents(dirname(__DIR__, 2) . '/Modules/PaymentModule/Lib/PaymentSuccess.php');
        $addFundHook = file_get_contents(dirname(__DIR__, 2) . '/Modules/PaymentModule/Lib/AddFundHook.php');

        $this->assertStringContainsString('send_customer_payment_failed_notification', $paymentSuccess);
        $this->assertStringContainsString('send_customer_payment_failed_notification', $addFundHook);
    }

    public function test_new_notification_helpers_exist(): void
    {
        $this->assertTrue(function_exists('send_provider_settlement_received_notification'));
        $this->assertTrue(function_exists('send_booking_reminder_notification'));
        $this->assertTrue(function_exists('send_chat_message_push_notification'));
    }
}
