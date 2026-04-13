<?php

namespace Tests\Unit;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Modules\BookingModule\Entities\Booking;
use Modules\WhatsAppModule\Entities\WhatsAppBookingAutomationMessageLog;
use Modules\WhatsAppModule\Services\BookingWhatsAppNotificationService;
use ReflectionMethod;
use Tests\TestCase;

class WhatsAppBookingAutomationLogTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config(['database.default' => 'sqlite']);
        config(['database.connections.sqlite.database' => ':memory:']);
        DB::purge('sqlite');
        DB::reconnect('sqlite');

        Schema::create('whatsapp_booking_automation_message_logs', function (Blueprint $table) {
            $table->id();
            $table->string('message_key', 96)->index();
            $table->string('trigger_event', 190)->nullable();
            $table->unsignedBigInteger('template_id')->nullable();
            $table->string('template_name', 255)->nullable();
            $table->string('recipient_party', 32)->default('unknown')->index();
            $table->string('recipient_phone', 64)->nullable()->index();
            $table->string('booking_id', 64)->nullable()->index();
            $table->string('booking_repeat_id', 64)->nullable()->index();
            $table->string('wa_message_id', 255)->nullable()->index();
            $table->unsignedBigInteger('local_whatsapp_message_id')->nullable()->index();
            $table->string('result', 24)->index();
            $table->text('error_detail')->nullable();
            $table->unsignedBigInteger('acting_admin_user_id')->nullable();
            $table->json('context_json')->nullable();
            $table->timestamp('created_at')->useCurrent();
        });
    }

    public function test_write_automation_log_persists_row(): void
    {
        $cloud = $this->createStub(\Modules\WhatsAppModule\Services\WhatsAppCloudService::class);
        $persist = $this->createStub(\Modules\WhatsAppModule\Services\WhatsAppMessagePersistenceService::class);
        $svc = new BookingWhatsAppNotificationService($cloud, $persist);

        $m = new ReflectionMethod($svc, 'writeAutomationLog');
        $m->setAccessible(true);
        $m->invoke(
            $svc,
            ['booking_id' => '42', 'entity_id' => '42'],
            'booking_confirmation_customer',
            '919876543210',
            'booking confirm (customer)',
            'sent',
            null,
            null,
            'wamid.HBgM',
            7
        );

        $this->assertSame(1, WhatsAppBookingAutomationMessageLog::query()->count());
        $log = WhatsAppBookingAutomationMessageLog::query()->first();
        $this->assertNotNull($log);
        $this->assertSame('booking_confirmation_customer', $log->message_key);
        $this->assertSame('sent', $log->result);
        $this->assertSame('customer', $log->recipient_party);
        $this->assertSame('wamid.HBgM', $log->wa_message_id);
        $this->assertSame('42', (string) $log->booking_id);
    }

    public function test_log_automation_master_disabled_writes_one_row_per_slot(): void
    {
        $cloud = $this->createStub(\Modules\WhatsAppModule\Services\WhatsAppCloudService::class);
        $persist = $this->createStub(\Modules\WhatsAppModule\Services\WhatsAppMessagePersistenceService::class);
        $svc = new BookingWhatsAppNotificationService($cloud, $persist);

        $booking = new Booking;
        $booking->id = 99;

        $m = new ReflectionMethod($svc, 'logAutomationMasterDisabled');
        $m->setAccessible(true);
        $m->invoke($svc, $booking, null, [
            ['key' => 'booking_confirmation_customer', 'label' => 'booking confirm (customer)'],
            ['key' => 'booking_confirmation_provider', 'label' => 'booking confirm (provider)'],
        ]);

        $this->assertSame(2, WhatsAppBookingAutomationMessageLog::query()->count());
        $this->assertSame(
            ['booking_confirmation_customer', 'booking_confirmation_provider'],
            WhatsAppBookingAutomationMessageLog::query()->orderBy('id')->pluck('message_key')->all()
        );
        $skipped = WhatsAppBookingAutomationMessageLog::query()->first();
        $this->assertNotNull($skipped);
        $this->assertSame('skipped', $skipped->result);
        $this->assertNotEmpty($skipped->error_detail);
    }
}
