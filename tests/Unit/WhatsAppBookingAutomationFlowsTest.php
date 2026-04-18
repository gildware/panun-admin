<?php

namespace Tests\Unit;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Modules\BookingModule\Entities\Booking;
use Modules\BookingModule\Entities\BookingPartialPayment;
use Modules\ProviderManagement\Entities\Provider;
use Modules\UserManagement\Entities\User;
use Modules\WhatsAppModule\Entities\WhatsAppBookingAutomationMessageLog;
use Modules\WhatsAppModule\Entities\WhatsAppMarketingTemplate;
use Modules\WhatsAppModule\Entities\WhatsAppMessage;
use Modules\WhatsAppModule\Services\BookingWhatsAppNotificationService;
use Modules\WhatsAppModule\Services\WhatsAppCloudService;
use Modules\WhatsAppModule\Services\WhatsAppMessagePersistenceService;
use Tests\TestCase;

class WhatsAppBookingAutomationFlowsTest extends TestCase
{
    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private static function stubBookingWaConfig(array $overrides = []): array
    {
        $merged = array_merge(
            [
                'enabled' => false,
                'default_phone_prefix' => '91',
                'apply_default_phone_prefix' => true,
            ],
            BookingWhatsAppNotificationService::defaultTemplateBodies()
        );
        foreach (BookingWhatsAppNotificationService::configurableMessageKeys() as $msgKey) {
            $merged[$msgKey . '_wa_tpl_id'] = null;
            $merged[$msgKey . '_wa_body_params'] = [];
            $merged[$msgKey . '_wa_header_params'] = [];
            $merged[$msgKey . '_send_enabled'] = true;
        }
        foreach (BookingWhatsAppNotificationService::statusTemplateSegmentKeys() as $segment) {
            $merged['booking_status_invoice_customer_' . $segment] = false;
            $merged['booking_status_invoice_provider_' . $segment] = false;
        }

        return array_replace($merged, $overrides);
    }

    protected function setUp(): void
    {
        parent::setUp();

        BookingWhatsAppNotificationService::resetAutomationLogChannelSchemaCache();

        config(['database.default' => 'sqlite']);
        config(['database.connections.sqlite.database' => ':memory:']);
        DB::purge('sqlite');
        DB::reconnect('sqlite');

        Schema::create('whatsapp_booking_automation_message_logs', function (Blueprint $table) {
            $table->id();
            $table->string('channel', 32)->default('whatsapp');
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

        Schema::create('whatsapp_marketing_templates', function (Blueprint $table) {
            $table->id();
            $table->string('meta_template_id')->nullable();
            $table->string('name');
            $table->string('language')->default('en');
            $table->string('category')->nullable();
            $table->string('status')->default('APPROVED');
            $table->unsignedInteger('body_parameter_count')->default(0);
            $table->json('components')->nullable();
            $table->text('preview_text')->nullable();
            $table->timestamp('synced_at')->nullable();
            $table->timestamps();
        });
    }

    protected function tearDown(): void
    {
        BookingWhatsAppNotificationService::resetAutomationLogChannelSchemaCache();
        parent::tearDown();
    }

    public function test_send_booking_status_change_dedupe_writes_two_skipped_rows(): void
    {
        $cloud = $this->createStub(WhatsAppCloudService::class);
        $persist = $this->createStub(WhatsAppMessagePersistenceService::class);
        $dedupeCfg = self::stubBookingWaConfig(['enabled' => false]);
        $svc = new class($cloud, $persist, $dedupeCfg) extends BookingWhatsAppNotificationService {
            public function __construct(
                WhatsAppCloudService $cloud,
                WhatsAppMessagePersistenceService $persist,
                private array $cfg
            ) {
                parent::__construct($cloud, $persist);
            }

            public function getConfig(): array
            {
                return $this->cfg;
            }
        };

        $booking = new Booking;
        $booking->id = 'bk-dedupe-1';
        $booking->booking_status = 'accepted';

        $svc->sendBookingStatusChange($booking, 'pending');
        $svc->sendBookingStatusChange($booking, 'pending');

        $this->assertSame(4, WhatsAppBookingAutomationMessageLog::query()->count());
        $this->assertSame(
            2,
            WhatsAppBookingAutomationMessageLog::query()
                ->where('error_detail', 'skipped_dedupe_same_status_transition')
                ->count()
        );
    }

    public function test_send_booking_status_change_meta_template_writes_sent_rows(): void
    {
        WhatsAppMarketingTemplate::query()->create([
            'name' => 'booking_status_test',
            'language' => 'en',
            'status' => 'APPROVED',
            'body_parameter_count' => 1,
            'components' => [
                ['type' => 'BODY', 'text' => 'Status {{1}}'],
            ],
        ]);

        $cloud = $this->createMock(WhatsAppCloudService::class);
        $cloud->method('normalizeRecipientPhone')->willReturn('447700900123');
        $cloud->expects($this->exactly(2))->method('sendTemplateMessage')->willReturn('wamid.testflow');

        $persist = $this->createMock(WhatsAppMessagePersistenceService::class);
        $persist->method('persistOutboundAutomation')->willReturn(new WhatsAppMessage);

        $cfg = self::stubBookingWaConfig([
            'enabled' => true,
            'booking_status_customer_accepted_wa_tpl_id' => 1,
            'booking_status_customer_accepted_wa_body_params' => ['{booking_id}'],
            'booking_status_provider_accepted_wa_tpl_id' => 1,
            'booking_status_provider_accepted_wa_body_params' => ['{booking_id}'],
        ]);

        $svc = new class($cloud, $persist, $cfg) extends BookingWhatsAppNotificationService {
            public function __construct(
                WhatsAppCloudService $cloud,
                WhatsAppMessagePersistenceService $persist,
                private array $fixedConfig
            ) {
                parent::__construct($cloud, $persist);
            }

            public function getConfig(): array
            {
                return $this->fixedConfig;
            }

            public function buildReplacements(Booking $booking, ?string $previousBookingStatus): array
            {
                return [
                    '{booking_id}' => 'BK-TEST',
                    '{service_name}' => 'Test service',
                ];
            }
        };

        $customer = new User;
        $customer->phone = '7700900123';

        $owner = new User;
        $owner->phone = '7700900456';

        $provider = new Provider;
        $provider->setRelation('owner', $owner);

        $booking = new Booking;
        $booking->id = 'bk-meta-1';
        $booking->booking_status = 'accepted';
        $booking->setRelation('customer', $customer);
        $booking->setRelation('provider', $provider);
        $booking->setRelation('detail', collect([]));
        $booking->setRelation('booking_partial_payments', collect([]));
        $booking->setRelation('service_address', null);

        $svc->sendBookingStatusChange($booking, 'pending');

        $this->assertSame(2, WhatsAppBookingAutomationMessageLog::query()->where('result', 'sent')->count());
        $this->assertSame(
            ['booking_status_customer_accepted', 'booking_status_provider_accepted'],
            WhatsAppBookingAutomationMessageLog::query()->orderBy('id')->pluck('message_key')->all()
        );
    }

    public function test_send_booking_payment_added_master_off_writes_two_skipped_rows(): void
    {
        $cloud = $this->createStub(WhatsAppCloudService::class);
        $persist = $this->createStub(WhatsAppMessagePersistenceService::class);
        $payCfg = self::stubBookingWaConfig(['enabled' => false]);
        $svc = new class($cloud, $persist, $payCfg) extends BookingWhatsAppNotificationService {
            public function __construct(
                WhatsAppCloudService $cloud,
                WhatsAppMessagePersistenceService $persist,
                private array $cfg
            ) {
                parent::__construct($cloud, $persist);
            }

            public function getConfig(): array
            {
                return $this->cfg;
            }

            public function buildReplacements(Booking $booking, ?string $previousBookingStatus): array
            {
                return [
                    '{booking_id}' => 'BK-PAY',
                    '{service_name}' => 'S',
                    '{customer_payments_total}' => '0',
                    '{customer_payments_to_company}' => '0',
                    '{customer_payments_to_provider}' => '0',
                ];
            }
        };

        $booking = new Booking;
        $booking->id = 'bk-pay-1';

        $partial = new BookingPartialPayment;
        $partial->paid_amount = 10;
        $partial->received_by = 'company';

        $svc->sendBookingPaymentAdded($booking, $partial, ['date' => '2026-01-10']);

        $this->assertSame(2, WhatsAppBookingAutomationMessageLog::query()->count());
        $this->assertSame(
            ['booking_payment_added_customer', 'booking_payment_added_provider'],
            WhatsAppBookingAutomationMessageLog::query()->orderBy('id')->pluck('message_key')->all()
        );
    }

    public function test_language_code_from_graph_template_row_handles_string_and_object_shapes(): void
    {
        $this->assertSame('en_GB', WhatsAppCloudService::languageCodeFromGraphTemplateRow([
            'language' => 'en_GB',
        ]));
        $this->assertSame('en_US', WhatsAppCloudService::languageCodeFromGraphTemplateRow([
            'language' => ['code' => 'en_US'],
        ]));
        $this->assertSame('fr', WhatsAppCloudService::languageCodeFromGraphTemplateRow([
            'language' => ['locale' => 'fr'],
        ]));
        $this->assertSame('', WhatsAppCloudService::languageCodeFromGraphTemplateRow([
            'language' => [],
        ]));
    }
}
