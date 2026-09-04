<?php

namespace Tests\Unit;

use Modules\AdminModule\Entities\UserNotification;
use PHPUnit\Framework\TestCase;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RegexIterator;

class AdminInboxNotificationCategoryTest extends TestCase
{
    private static ?string $codebase = null;

    private static function codebase(): string
    {
        if (self::$codebase === null) {
            $root = dirname(__DIR__, 2);
            $chunks = [];
            foreach (['app', 'Modules'] as $dir) {
                $path = $root.'/'.$dir;
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

    /**
     * @return array<string, array{0: string, 1: string}>
     */
    public static function notificationCategoryProvider(): array
    {
        $external = [
            UserNotification::TYPE_BOOKING,
            UserNotification::TYPE_CHAT_MESSAGE,
            UserNotification::TYPE_PROVIDER_REQUEST,
            UserNotification::TYPE_WITHDRAW_REQUEST,
            UserNotification::TYPE_PROVIDER_WITHDRAWAL,
            UserNotification::TYPE_ADVERTISEMENT,
            UserNotification::TYPE_SERVICE_REQUEST,
            UserNotification::TYPE_SHOWCASE,
            UserNotification::TYPE_PROFILE_CHANGE_REQUEST,
            UserNotification::TYPE_WELCOME_BONUS,
            UserNotification::TYPE_REVIEW,
            UserNotification::TYPE_WEB_BOOKING,
            UserNotification::TYPE_WEB_PROVIDER_REQUEST,
            UserNotification::TYPE_APP_CUSTOM_REQUEST,
            UserNotification::TYPE_LEAD,
            UserNotification::TYPE_WHATSAPP_HUMAN_SUPPORT,
            UserNotification::TYPE_HUNTING_INTEREST,
        ];

        $internal = [
            UserNotification::TYPE_LEAD_COMMENT,
            UserNotification::TYPE_BOOKING_COMMENT,
            UserNotification::TYPE_TICKET_ASSIGNED,
            UserNotification::TYPE_TICKET_COMMENT,
            UserNotification::TYPE_LEAD_ASSIGNED,
            UserNotification::TYPE_BOOKING_ASSIGNED,
            UserNotification::TYPE_WHATSAPP_ASSIGNED,
            UserNotification::TYPE_LEAD_FOLLOWUP_DUE,
        ];

        $cases = [];
        foreach ($external as $type) {
            $cases["{$type} is external"] = [$type, UserNotification::CATEGORY_EXTERNAL];
        }
        foreach ($internal as $type) {
            $cases["{$type} is internal"] = [$type, UserNotification::CATEGORY_INTERNAL];
        }

        return $cases;
    }

    /**
     * @dataProvider notificationCategoryProvider
     */
    public function test_category_for_type(string $type, string $expectedCategory): void
    {
        $this->assertSame($expectedCategory, UserNotification::categoryForType($type));
    }

    public function test_all_declared_types_have_category_mapping(): void
    {
        $reflection = new \ReflectionClass(UserNotification::class);
        $types = [];
        foreach ($reflection->getConstants() as $name => $value) {
            if (str_starts_with($name, 'TYPE_') && is_string($value)) {
                $types[] = $value;
            }
        }

        $this->assertNotEmpty($types);

        foreach ($types as $type) {
            $category = UserNotification::categoryForType($type);
            $this->assertContains(
                $category,
                [UserNotification::CATEGORY_EXTERNAL, UserNotification::CATEGORY_INTERNAL],
                "Type {$type} must map to external or internal"
            );
        }
    }

    public function test_admin_inbox_helper_functions_exist(): void
    {
        $helpers = [
            'admin_inbox_notify_all',
            'admin_inbox_notify_chat_message',
            'admin_inbox_notify_provider_request',
            'admin_inbox_notify_withdraw_request',
            'admin_inbox_notify_booking_payment',
            'admin_inbox_notify_booking_ongoing',
            'admin_inbox_notify_booking_reopened',
            'admin_inbox_notify_booking_customer_canceled',
            'admin_inbox_notify_advertisement_submitted',
            'admin_inbox_notify_advertisement_paused_by_provider',
            'admin_inbox_notify_advertisement_resumed_by_provider',
            'admin_inbox_notify_service_request_submitted',
            'admin_inbox_notify_showcase_submitted',
            'admin_inbox_notify_welcome_bonus',
            'admin_inbox_notify_customer_review_submitted',
            'admin_inbox_notify_provider_customer_review_submitted',
            'admin_inbox_notify_profile_change_request',
            'admin_inbox_notify_web_booking_submitted',
            'admin_inbox_notify_web_provider_request_submitted',
            'admin_inbox_notify_app_custom_request_submitted',
            'admin_inbox_notify_lead_created',
            'admin_inbox_notify_lead_assigned',
            'admin_inbox_notify_booking_assigned',
            'admin_inbox_notify_ticket_assigned',
            'admin_inbox_notify_whatsapp_assigned',
            'admin_inbox_notify_booking_pending_cancellation',
            'admin_inbox_notify_whatsapp_human_support_requested',
            'admin_inbox_notify_lead_followup_due',
            'admin_inbox_notify_hunting_interest',
            'admin_inbox_notify_hunting_interest_revoked',
            'admin_inbox_notify_hunting_rejected',
        ];

        foreach ($helpers as $helper) {
            $this->assertTrue(function_exists($helper), "Missing helper: {$helper}");
        }
    }

    /**
     * @return array<string, array{0: string, 1: list<string>}>
     */
    public static function triggerWiringProvider(): array
    {
        return [
            'new booking' => ['external', ['CreateAdminBookingNotification', 'BookingRequested']],
            'provider withdrawal' => ['external', ['CreateAdminProviderWithdrawalNotification', 'ProviderWithdrewFromBooking']],
            'chat message inbox' => ['external', ['admin_inbox_notify_chat_message']],
            'provider registration' => ['external', ['admin_inbox_notify_provider_request']],
            'withdraw request' => ['external', ['admin_inbox_notify_withdraw_request']],
            'booking payment' => ['external', ['admin_inbox_notify_booking_payment']],
            'booking ongoing' => ['external', ['admin_inbox_notify_booking_ongoing']],
            'booking reopened' => ['external', ['admin_inbox_notify_booking_reopened']],
            'booking customer canceled' => ['external', ['admin_inbox_notify_booking_customer_canceled']],
            'advertisement submitted' => ['external', ['admin_inbox_notify_advertisement_submitted']],
            'service request' => ['external', ['admin_inbox_notify_service_request_submitted']],
            'showcase' => ['external', ['admin_inbox_notify_showcase_submitted']],
            'welcome bonus' => ['external', ['admin_inbox_notify_welcome_bonus']],
            'customer review' => ['external', ['admin_inbox_notify_customer_review_submitted']],
            'provider review' => ['external', ['admin_inbox_notify_provider_customer_review_submitted']],
            'profile change' => ['external', ['admin_inbox_notify_profile_change_request']],
            'web booking' => ['external', ['admin_inbox_notify_web_booking_submitted']],
            'web provider request' => ['external', ['admin_inbox_notify_web_provider_request_submitted']],
            'app custom request' => ['external', ['admin_inbox_notify_app_custom_request_submitted']],
            'lead created' => ['external', ['admin_inbox_notify_lead_created']],
            'lead comment' => ['internal', ['LeadCommentService', 'TYPE_LEAD_COMMENT']],
            'booking comment' => ['internal', ['BookingCommentService', 'TYPE_BOOKING_COMMENT']],
            'lead assigned' => ['internal', ['admin_inbox_notify_lead_assigned', 'StaffActivityLogger']],
            'booking assigned' => ['internal', ['admin_inbox_notify_booking_assigned']],
            'ticket assigned' => ['internal', ['admin_inbox_notify_ticket_assigned', 'TaskBoardService']],
            'ticket comment' => ['internal', ['TaskBoardService', 'TYPE_TICKET_COMMENT', 'notifyCommentRecipients']],
            'whatsapp assigned' => ['internal', ['admin_inbox_notify_whatsapp_assigned', 'StaffActivityLogger']],
            'booking pending cancellation' => ['external', ['admin_inbox_notify_booking_pending_cancellation', 'ProviderBookingWithdrawalService']],
            'whatsapp human support' => ['external', ['admin_inbox_notify_whatsapp_human_support_requested', 'markHumanSupportRequested']],
            'lead followup due' => ['internal', ['admin_inbox_notify_lead_followup_due', 'LeadFollowupReminderNotifier']],
            'hunting interest' => ['external', ['admin_inbox_notify_hunting_interest', 'TYPE_HUNTING_INTEREST']],
            'hunting rejected' => ['external', ['admin_inbox_notify_hunting_rejected', 'TYPE_HUNTING_INTEREST']],
        ];
    }

    /**
     * @dataProvider triggerWiringProvider
     *
     * @param  list<string>  $needles
     */
    public function test_trigger_is_wired_in_codebase(string $expectedCategory, array $needles): void
    {
        $haystack = self::codebase();

        foreach ($needles as $needle) {
            $this->assertStringContainsString(
                $needle,
                $haystack,
                "Expected trigger reference [{$needle}] for {$expectedCategory} notification"
            );
        }
    }

    public function test_header_poll_returns_split_notification_templates(): void
    {
        $path = dirname(__DIR__, 2).'/Modules/AdminModule/Http/Controllers/Web/Admin/AdminController.php';
        $source = (string) file_get_contents($path);

        $this->assertStringContainsString('notification_external_template', $source);
        $this->assertStringContainsString('notification_internal_template', $source);
        $this->assertStringContainsString('notification_external_unread_count', $source);
        $this->assertStringContainsString('notification_internal_unread_count', $source);
    }

    public function test_header_ui_has_separate_external_and_internal_icons(): void
    {
        $header = (string) file_get_contents(dirname(__DIR__, 2).'/Modules/AdminModule/Resources/views/layouts/partials/_header.blade.php');
        $chrome = (string) file_get_contents(dirname(__DIR__, 2).'/Modules/AdminModule/Resources/views/layouts/partials/_top-chrome.blade.php');
        $dropdown = (string) file_get_contents(dirname(__DIR__, 2).'/Modules/AdminModule/Resources/views/layouts/partials/_notification-dropdown.blade.php');

        $this->assertStringContainsString('CATEGORY_EXTERNAL', $header);
        $this->assertStringContainsString('CATEGORY_INTERNAL', $header);
        $this->assertStringContainsString('CATEGORY_EXTERNAL', $chrome);
        $this->assertStringContainsString('CATEGORY_INTERNAL', $chrome);
        $this->assertStringContainsString('notification_external_count', $dropdown);
        $this->assertStringContainsString('notification_internal_count', $dropdown);
        $this->assertStringContainsString('show-notification-list-external', $dropdown);
        $this->assertStringContainsString('show-notification-list-internal', $dropdown);
    }

    public function test_chat_message_migration_reclassifies_as_external(): void
    {
        $path = dirname(__DIR__, 2).'/Modules/AdminModule/Database/Migrations/2026_08_03_120000_reclassify_chat_message_notifications_as_external.php';
        $source = (string) file_get_contents($path);

        $this->assertStringContainsString('TYPE_CHAT_MESSAGE', $source);
        $this->assertStringContainsString('CATEGORY_EXTERNAL', $source);
    }

    public function test_hunting_interest_migration_reclassifies_as_external(): void
    {
        $path = dirname(__DIR__, 2).'/Modules/AdminModule/Database/Migrations/2026_09_04_200000_reclassify_hunting_interest_notifications_as_external.php';
        $source = (string) file_get_contents($path);

        $this->assertStringContainsString('TYPE_HUNTING_INTEREST', $source);
        $this->assertStringContainsString('CATEGORY_EXTERNAL', $source);
    }
}
