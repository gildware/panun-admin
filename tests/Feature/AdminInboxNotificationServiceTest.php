<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Str;
use Modules\AdminModule\Entities\UserNotification;
use Modules\AdminModule\Services\AdminInboxNotificationService;
use Tests\TestCase;

class AdminInboxNotificationServiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        if (! Schema::hasTable('user_notifications')) {
            Schema::create('user_notifications', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->uuid('user_id');
                $table->string('type', 64);
                $table->string('category', 16)->default(UserNotification::CATEGORY_EXTERNAL);
                $table->string('title');
                $table->text('body')->nullable();
                $table->string('action_url')->nullable();
                $table->string('reference_type')->nullable();
                $table->string('reference_id')->nullable();
                $table->timestamp('read_at')->nullable();
                $table->timestamps();
            });
        }
    }

    public function test_notify_user_assigns_category_from_type(): void
    {
        $userId = (string) Str::uuid();
        $service = app(AdminInboxNotificationService::class);

        $external = $service->notifyUser(
            $userId,
            UserNotification::TYPE_CHAT_MESSAGE,
            'External chat',
            'Customer message',
            '/admin/chat',
            'channel_conversation',
            'conv-1',
        );

        $internal = $service->notifyUser(
            $userId,
            UserNotification::TYPE_LEAD_ASSIGNED,
            'Lead assigned',
            'Staff assignment',
            '/admin/lead/1',
            'lead_assigned',
            '1:'.$userId,
        );

        $this->assertNotNull($external);
        $this->assertNotNull($internal);
        $this->assertSame(UserNotification::CATEGORY_EXTERNAL, $external->category);
        $this->assertSame(UserNotification::CATEGORY_INTERNAL, $internal->category);
    }

    public function test_unread_counts_are_split_by_category(): void
    {
        $userId = (string) Str::uuid();
        $service = app(AdminInboxNotificationService::class);

        $service->notifyUser($userId, UserNotification::TYPE_LEAD, 'Lead', null, null, 'lead_created', 'lead-1');
        $service->notifyUser($userId, UserNotification::TYPE_LEAD, 'Lead 2', null, null, 'lead_created', 'lead-2');
        $service->notifyUser($userId, UserNotification::TYPE_TICKET_ASSIGNED, 'Ticket', null, null, 'ticket_assigned', 't-1:'.$userId);

        $this->assertSame(2, $service->unreadCount($userId, UserNotification::CATEGORY_EXTERNAL));
        $this->assertSame(1, $service->unreadCount($userId, UserNotification::CATEGORY_INTERNAL));
        $this->assertSame(3, $service->unreadCount($userId));
    }

    public function test_mark_all_as_read_respects_category_filter(): void
    {
        $userId = (string) Str::uuid();
        $service = app(AdminInboxNotificationService::class);

        $service->notifyUser($userId, UserNotification::TYPE_BOOKING, 'Booking', null, null, 'booking', 'b-1');
        $service->notifyUser($userId, UserNotification::TYPE_LEAD_ASSIGNED, 'Assigned', null, null, 'lead_assigned', 'l-1:'.$userId);

        $service->markAllAsRead($userId, UserNotification::CATEGORY_EXTERNAL);

        $this->assertSame(0, $service->unreadCount($userId, UserNotification::CATEGORY_EXTERNAL));
        $this->assertSame(1, $service->unreadCount($userId, UserNotification::CATEGORY_INTERNAL));
    }
}
