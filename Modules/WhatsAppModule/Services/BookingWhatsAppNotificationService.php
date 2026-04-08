<?php

namespace Modules\WhatsAppModule\Services;

use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Modules\BookingModule\Entities\Booking;
use Modules\BookingModule\Entities\BookingRepeat;
use Modules\BusinessSettingsModule\Entities\AdditionalChargeType;
use Modules\ProviderManagement\Entities\Provider;
use Modules\UserManagement\Entities\Serviceman;
use Modules\WhatsAppModule\Entities\WhatsAppMarketingTemplate;
use Modules\WhatsAppModule\Services\WhatsAppCloudService;

class BookingWhatsAppNotificationService
{
    public const SETTINGS_KEY = 'whatsapp_booking_templates';

    public const SETTINGS_TYPE = 'whatsapp';

    public const CACHE_PREVIOUS_STATUS_PREFIX = 'wa:booking_prev_status:';

    public const CACHE_CONFIRM_LOCK_PREFIX = 'wa:lock:bcf:';

    public const CACHE_CONFIRM_SENT_PREFIX = 'wa:bcf:sent:';

    public const CACHE_STATUS_LOCK_PREFIX = 'wa:lock:bst:';

    public const CACHE_STATUS_SENT_PREFIX = 'wa:bst:sent:';

    public const CACHE_PROVIDER_CHANGE_LOCK_PREFIX = 'wa:lock:bpc:';

    public const CACHE_PROVIDER_CHANGE_SENT_PREFIX = 'wa:bpc:sent:';

    public const CACHE_SCHEDULE_LOCK_PREFIX = 'wa:lock:bsc:';

    public const CACHE_SCHEDULE_SENT_PREFIX = 'wa:bsc:sent:';

    public const CACHE_PAYMENT_LOCK_PREFIX = 'wa:lock:bpy:';

    public const CACHE_PAYMENT_SENT_PREFIX = 'wa:bpy:sent:';

    public const CACHE_SERVICEMAN_LOCK_PREFIX = 'wa:lock:bsm:';

    public const CACHE_SERVICEMAN_SENT_PREFIX = 'wa:bsm:sent:';

    public const CACHE_VERIFICATION_LOCK_PREFIX = 'wa:lock:bver:';

    public const CACHE_VERIFICATION_SENT_PREFIX = 'wa:bver:sent:';

    public const CACHE_REOPEN_RESOLVED_LOCK_PREFIX = 'wa:lock:brv:';

    public const CACHE_REOPEN_RESOLVED_SENT_PREFIX = 'wa:brv:sent:';

    private const WHATSAPP_DOC_CAPTION_MAX = 1024;

    /** @var array<string, string> */
    public const PLACEHOLDER_HINTS = [
        '{service_name}' => 'Service name(s)',
        '{customer_address}' => 'Customer address',
        '{customer_name}' => 'Customer name',
        '{customer_phone}' => 'Customer phone',
        '{provider_name}' => 'Provider / company name',
        '{provider_phone}' => 'Provider phone',
        '{booking_id}' => 'Booking reference (readable id)',
        '{booking_datetime}' => 'Scheduled service date & time (e.g. 4th April 2026 11:33 AM)',
        '{service_where}' => 'Where service will be provided',
        '{total_bill}' => 'Total bill',
        '{amount_paid}' => 'Amount paid so far',
        '{due_amount}' => 'Amount due',
        '{booking_status}' => 'Current booking status',
        '{previous_booking_status}' => 'Previous booking status (status updates only)',
        '{previous_provider_name}' => 'Previous provider name (provider change only)',
        '{previous_provider_phone}' => 'Previous provider phone (provider change only)',
        '{previous_service_schedule}' => 'Previous service date & time (schedule change only; same format as booking date/time)',
        '{payment_status}' => 'Payment status (Paid / Unpaid)',
        '{previous_payment_status}' => 'Previous payment status (payment updates only)',
        '{serviceman_name}' => 'Assigned serviceman name',
        '{serviceman_phone}' => 'Assigned serviceman phone',
        '{previous_serviceman_name}' => 'Previous serviceman name (assignment change only)',
        '{previous_serviceman_phone}' => 'Previous serviceman phone (assignment change only)',
        '{verification_status}' => 'Booking verification state (e.g. Approved, Denied)',
        '{previous_verification_status}' => 'Previous verification state',
        '{verification_action}' => 'Last verification action: approve, deny, or cancel',
        '{reopen_resolve_remarks}' => 'Remarks when a reopen case is marked resolved (reopen resolved template only)',
        '{booking_cancellation_reason}' => 'Cancellation reason (from the latest cancel action on this booking)',
        '{on_hold_reason}' => 'Put on hold — reason (from the latest hold action on this booking)',
        '{reopen_from_completed_reason}' => 'Reopen from completed — reason or notes (from reopen flow)',
    ];

    /**
     * Example values for the booking-template preview in admin (Meta variable mapping UI).
     *
     * @var array<string, string>
     */
    public const PLACEHOLDER_PREVIEW_SAMPLES = [
        '{service_name}' => 'AC repair, gas refill',
        '{customer_address}' => '12 MG Road, Bengaluru 560001',
        '{customer_name}' => 'Priya Sharma',
        '{customer_phone}' => '+91 98765 43210',
        '{provider_name}' => 'CoolAir Services Pvt Ltd',
        '{provider_phone}' => '+91 91234 56789',
        '{booking_id}' => 'BK-2026-10492',
        '{booking_datetime}' => '7 Apr 2026, 2:30 PM',
        '{service_where}' => 'Customer address',
        '{total_bill}' => '4,500.00',
        '{amount_paid}' => '1,000.00',
        '{due_amount}' => '3,500.00',
        '{booking_status}' => 'Accepted',
        '{previous_booking_status}' => 'Pending',
        '{previous_provider_name}' => 'Old Care HVAC',
        '{previous_provider_phone}' => '+91 90000 00001',
        '{previous_service_schedule}' => '5 Apr 2026, 10:00 AM',
        '{payment_status}' => 'Paid',
        '{previous_payment_status}' => 'Unpaid',
        '{serviceman_name}' => 'Ravi Kumar',
        '{serviceman_phone}' => '+91 99887 76655',
        '{previous_serviceman_name}' => 'Amit Singh',
        '{previous_serviceman_phone}' => '+91 88776 65544',
        '{verification_status}' => 'Approved',
        '{previous_verification_status}' => 'Pending',
        '{verification_action}' => 'approve',
        '{reopen_resolve_remarks}' => 'Technician visit completed; case closed.',
        '{booking_cancellation_reason}' => 'Customer requested reschedule',
        '{on_hold_reason}' => 'Awaiting parts',
        '{reopen_from_completed_reason}' => 'Issue recurred after visit',
    ];

    /**
     * Preview sample strings for admin UI: static tokens plus one sample per additional charge type.
     *
     * @return array<string, string>
     */
    public static function allPlaceholderPreviewSamplesForAdmin(): array
    {
        $acSamples = [];
        foreach (self::buildAdditionalChargePlaceholderData()['hints'] as $token => $_label) {
            $acSamples[$token] = '350.00';
        }

        return array_merge(self::PLACEHOLDER_PREVIEW_SAMPLES, $acSamples);
    }

    /**
     * Static booking tokens plus one token per active additional charge type (amount for that line).
     *
     * @return array<string, string>
     */
    public static function allPlaceholderHintsForAdmin(): array
    {
        $ac = self::buildAdditionalChargePlaceholderData();

        return array_merge(self::PLACEHOLDER_HINTS, $ac['hints']);
    }

    /**
     * @return array{hints: array<string, string>, id_to_key: array<string, string>}
     */
    private static function buildAdditionalChargePlaceholderData(): array
    {
        $hints = [];
        $idToKey = [];
        $types = AdditionalChargeType::query()->active()->ordered()->get();
        $seenBaseSlugs = [];
        foreach ($types as $t) {
            $id = (string) $t->id;
            $base = Str::slug((string) $t->name, '_');
            if ($base === '') {
                $base = 'charge';
            }
            $slug = $base;
            if (isset($seenBaseSlugs[$base])) {
                $slug = $base . '_' . substr(str_replace('-', '', $id), 0, 8);
            } else {
                $seenBaseSlugs[$base] = true;
            }
            $key = '{additional_charge_' . $slug . '}';
            $idToKey[$id] = $key;
            $hints[$key] = 'Additional charge amount: ' . $t->name;
        }

        return ['hints' => $hints, 'id_to_key' => $idToKey];
    }

    private static function orphanAdditionalChargeTokenKey(string $typeId, string $name): string
    {
        $base = Str::slug($name, '_');
        if ($base === '') {
            $base = 'charge';
        }

        return '{additional_charge_' . $base . '_' . substr(str_replace('-', '', $typeId), 0, 8) . '}';
    }

    /**
     * Sub-keys for per-status WhatsApp templates (booking_status_customer_{segment}, etc.).
     *
     * @return list<string>
     */
    public static function statusTemplateSegmentKeys(): array
    {
        return array_merge(
            array_column(BOOKING_STATUSES, 'key'),
            ['reopened', 'reopen_resolved']
        );
    }

    /**
     * Keys for free-text template bodies and optional Meta template bindings (same key + _wa_tpl_id / _wa_body_params).
     *
     * @return list<string>
     */
    public static function configurableMessageKeys(): array
    {
        $keys = [
            'booking_confirmation_customer',
            'booking_confirmation_provider',
            'booking_status_customer',
            'booking_status_provider',
            'provider_change_customer',
            'provider_change_previous_provider',
            'provider_change_new_provider',
            'booking_schedule_customer',
            'booking_schedule_provider',
            'booking_payment_customer',
            'booking_payment_provider',
            'booking_serviceman_customer',
            'booking_serviceman_provider',
            'booking_verification_customer',
            'booking_verification_provider',
        ];
        foreach (self::statusTemplateSegmentKeys() as $segment) {
            $keys[] = 'booking_status_customer_' . $segment;
            $keys[] = 'booking_status_provider_' . $segment;
        }

        return $keys;
    }

    /**
     * Default bodies for new installs / migrations (customer: service + provider; provider: customer + service).
     *
     * @return array<string, string>
     */
    public static function defaultTemplateBodies(): array
    {
        $statusCustomerDefault = "Booking update\n\nBooking *{booking_id}* status changed:\n{previous_booking_status} → *{booking_status}*\n\n*Service*\n{service_name}\nWhen: {booking_datetime}\n\n*Provider*\n{provider_name}\nPhone: {provider_phone}\n\nIf anything looks wrong, contact us.";
        $statusProviderDefault = "Booking update\n\nBooking *{booking_id}* status changed:\n{previous_booking_status} → *{booking_status}*\n\n*Customer*\n{customer_name}\nPhone: {customer_phone}\n\n*Service*\n{service_name}\nWhen: {booking_datetime}\n\nCheck your app for full details.";
        $reopenedCustomer = "Booking reopened\n\nBooking *{booking_id}* was completed and has been *reopened*.\nNew status: *{booking_status}*\n\n*Service*\n{service_name}\nWhen: {booking_datetime}\n\n*Provider*\n{provider_name}\nPhone: {provider_phone}\n\nWe will follow up on your case.";
        $reopenedProvider = "Booking reopened\n\nBooking *{booking_id}* status is now *{booking_status}* (reopened from completed).\n\n*Customer*\n{customer_name}\nPhone: {customer_phone}\n\n*Service*\n{service_name}\nWhen: {booking_datetime}";
        $resolvedCustomer = "Reopen case resolved\n\nBooking *{booking_id}* — your reopen request is marked *resolved*.\n\nRemarks: {reopen_resolve_remarks}\n\n*Service*\n{service_name}\nWhen: {booking_datetime}";
        $resolvedProvider = "Reopen case resolved\n\nBooking *{booking_id}* reopen case marked resolved.\n\nRemarks: {reopen_resolve_remarks}\n\n*Customer*\n{customer_name}\nPhone: {customer_phone}\n\n*Service*\n{service_name}";

        $perStatus = [];
        foreach (self::statusTemplateSegmentKeys() as $segment) {
            if ($segment === 'reopened') {
                $perStatus['booking_status_customer_reopened'] = $reopenedCustomer;
                $perStatus['booking_status_provider_reopened'] = $reopenedProvider;

                continue;
            }
            if ($segment === 'reopen_resolved') {
                $perStatus['booking_status_customer_reopen_resolved'] = $resolvedCustomer;
                $perStatus['booking_status_provider_reopen_resolved'] = $resolvedProvider;

                continue;
            }
            $perStatus['booking_status_customer_' . $segment] = $statusCustomerDefault;
            $perStatus['booking_status_provider_' . $segment] = $statusProviderDefault;
        }

        return array_merge([
            'booking_confirmation_customer' => "Hello {customer_name},\n\nYour booking *{booking_id}* is confirmed.\n\n*Service*\n{service_name}\nWhen: {booking_datetime}\nWhere: {service_where}\n\n*Provider*\n{provider_name}\nPhone: {provider_phone}\n\n*Payment*\nTotal: {total_bill}\nPaid: {amount_paid}\nDue: {due_amount}\n\nThank you for choosing us. Reply here if you have questions.",
            'booking_confirmation_provider' => "New booking *{booking_id}*\n\n*Customer*\n{customer_name}\nPhone: {customer_phone}\nAddress: {customer_address}\n\n*Service*\n{service_name}\nWhen: {booking_datetime}\nService at: {service_where}\n\n*Payment*\nTotal: {total_bill}\nPaid: {amount_paid}\nDue: {due_amount}\n\nPlease review the booking in your app or dashboard.",
            'booking_status_customer' => $statusCustomerDefault,
            'booking_status_provider' => $statusProviderDefault,
            'provider_change_customer' => "Hello {customer_name},\n\nYour booking *{booking_id}* now has a different service provider.\n\n*New provider*\n{provider_name}\nPhone: {provider_phone}\n\n*Previous provider*\n{previous_provider_name}\nPhone: {previous_provider_phone}\n\n*Service*\n{service_name}\nWhen: {booking_datetime}\nWhere: {service_where}\n\n*Payment*\nTotal: {total_bill}\nPaid: {amount_paid}\nDue: {due_amount}\n\nPlease save the new provider's contact details for your records.",
            'provider_change_previous_provider' => "Booking reassigned\n\nBooking *{booking_id}* has been reassigned and is *no longer on your list*.\n\n*Customer*\n{customer_name}\nPhone: {customer_phone}\n\n*Service*\n{service_name}\nWhen: {booking_datetime}\n\n*New provider*\n{provider_name}\nPhone: {provider_phone}\n\nThank you for your work on this booking.",
            'provider_change_new_provider' => "You have been assigned booking *{booking_id}*\n\n*Customer*\n{customer_name}\nPhone: {customer_phone}\nAddress: {customer_address}\n\n*Service*\n{service_name}\nWhen: {booking_datetime}\nService at: {service_where}\n\n*Payment*\nTotal: {total_bill}\nPaid: {amount_paid}\nDue: {due_amount}\n\n*Previous provider*\n{previous_provider_name}\nPhone: {previous_provider_phone}\n\nPlease accept and prepare in your app.",
            'booking_schedule_customer' => "Schedule update\n\nBooking *{booking_id}* has a new service time.\n\nBefore: {previous_service_schedule}\nNow: {booking_datetime}\n\nService: {service_name}\nProvider: {provider_name} ({provider_phone})",
            'booking_schedule_provider' => "Schedule update\n\nBooking *{booking_id}* rescheduled.\n\nBefore: {previous_service_schedule}\nNow: {booking_datetime}\n\nCustomer: {customer_name} ({customer_phone})\nService: {service_name}",
            'booking_payment_customer' => "Payment update\n\nBooking *{booking_id}* payment status: {previous_payment_status} → *{payment_status}*\n\nService: {service_name}\nWhen: {booking_datetime}\nTotal: {total_bill} | Paid: {amount_paid} | Due: {due_amount}",
            'booking_payment_provider' => "Payment update\n\nBooking *{booking_id}* payment status: {previous_payment_status} → *{payment_status}*\n\nCustomer: {customer_name} ({customer_phone})\nService: {service_name}",
            'booking_serviceman_customer' => "Serviceman update\n\nBooking *{booking_id}* — your assigned serviceman changed.\n\nBefore: {previous_serviceman_name} ({previous_serviceman_phone})\nNow: {serviceman_name} ({serviceman_phone})\n\nWhen: {booking_datetime}\nProvider: {provider_name}",
            'booking_serviceman_provider' => "Serviceman update\n\nBooking *{booking_id}*\n\nServiceman: {previous_serviceman_name} → *{serviceman_name}*\nPhone: {serviceman_phone}\n\nCustomer: {customer_name} ({customer_phone})\nService: {service_name}",
            'booking_verification_customer' => "Booking verification\n\nBooking *{booking_id}* — action: *{verification_action}*\n\nStatus was: {previous_verification_status}\nNow: {verification_status}\n\nService: {service_name}\nWhen: {booking_datetime}",
            'booking_verification_provider' => "Booking verification\n\nBooking *{booking_id}* — *{verification_action}*\n\nVerification: {previous_verification_status} → {verification_status}\n\nCustomer: {customer_name} ({customer_phone})\nService: {service_name}",
        ], $perStatus);
    }

    public function __construct(
        protected WhatsAppCloudService $cloud,
        protected WhatsAppMessagePersistenceService $messagePersistence
    ) {}

    public function sendBookingConfirmation(?Booking $booking): void
    {
        if (!$booking) {
            return;
        }
        $config = $this->getConfig();
        if (empty($config['enabled'])) {
            return;
        }

        $lock = Cache::lock(self::CACHE_CONFIRM_LOCK_PREFIX . $booking->id, 30);
        if (!$lock->get()) {
            return;
        }
        try {
            if (!Cache::add(self::CACHE_CONFIRM_SENT_PREFIX . $booking->id, 1, now()->addYears(10))) {
                return;
            }

            $booking->loadMissing(['customer', 'provider.owner', 'service_address', 'detail', 'booking_partial_payments']);
            $vars = $this->buildReplacements($booking, null);
            $customerPhone = $this->normalizePhone($booking->customer?->phone, $config);
            $this->trySendBookingMetaOnly(
                $config,
                'booking_confirmation_customer',
                $vars,
                $customerPhone,
                'booking confirm (customer)',
                ['booking_id' => $booking->id]
            );

            $providerPhone = $this->resolveProviderPhone($booking->provider, $config);
            $this->trySendBookingMetaOnly(
                $config,
                'booking_confirmation_provider',
                $vars,
                $providerPhone,
                'booking confirm (provider)',
                ['booking_id' => $booking->id]
            );
        } finally {
            $lock->release();
        }
    }

    public function sendBookingStatusChange(Booking $booking, string $previousBookingStatus): void
    {
        $config = $this->getConfig();
        if (empty($config['enabled'])) {
            return;
        }

        $newStatus = (string) $booking->booking_status;
        if ($previousBookingStatus === $newStatus) {
            return;
        }

        $transitionKey = self::CACHE_STATUS_SENT_PREFIX . $booking->id . ':' . $previousBookingStatus . '>' . $newStatus;
        $lock = Cache::lock(self::CACHE_STATUS_LOCK_PREFIX . $booking->id, 30);
        if (!$lock->get()) {
            return;
        }
        try {
            if (!Cache::add($transitionKey, 1, now()->addYears(5))) {
                return;
            }

            $booking->loadMissing(['customer', 'provider.owner', 'service_address', 'detail', 'booking_partial_payments']);
            $vars = $this->buildReplacements($booking, $previousBookingStatus);
            $segment = $this->resolveStatusTemplateSegment($previousBookingStatus, $newStatus);

            $customerPhone = $this->normalizePhone($booking->customer?->phone, $config);
            $this->deliverStatusTemplateMessage(
                $config,
                $segment,
                'customer',
                $vars,
                $customerPhone,
                $booking,
                null,
                'booking status',
                (string) $booking->id
            );

            $providerPhone = $this->resolveProviderPhone($booking->provider, $config);
            $this->deliverStatusTemplateMessage(
                $config,
                $segment,
                'provider',
                $vars,
                $providerPhone,
                $booking,
                null,
                'booking status',
                (string) $booking->id
            );
        } finally {
            $lock->release();
        }
    }

    /**
     * When an admin marks a reopen case resolved (no booking_status change).
     */
    public function sendReopenCaseResolved(Booking $booking): void
    {
        $config = $this->getConfig();
        if (empty($config['enabled'])) {
            return;
        }

        $dedupKey = self::CACHE_REOPEN_RESOLVED_SENT_PREFIX . $booking->id;
        $lock = Cache::lock(self::CACHE_REOPEN_RESOLVED_LOCK_PREFIX . $booking->id, 30);
        if (!$lock->get()) {
            return;
        }
        try {
            if (!Cache::add($dedupKey, 1, now()->addYears(5))) {
                return;
            }

            $booking->loadMissing(['customer', 'provider.owner', 'service_address', 'detail', 'booking_partial_payments']);
            $vars = array_merge($this->buildReplacements($booking, null), [
                '{reopen_resolve_remarks}' => trim((string) ($booking->reopen_resolve_remarks ?? '')) !== ''
                    ? (string) $booking->reopen_resolve_remarks
                    : '—',
            ]);
            $segment = 'reopen_resolved';

            $customerPhone = $this->normalizePhone($booking->customer?->phone, $config);
            $this->deliverStatusTemplateMessage(
                $config,
                $segment,
                'customer',
                $vars,
                $customerPhone,
                $booking,
                null,
                'reopen resolved',
                (string) $booking->id
            );

            $providerPhone = $this->resolveProviderPhone($booking->provider, $config);
            $this->deliverStatusTemplateMessage(
                $config,
                $segment,
                'provider',
                $vars,
                $providerPhone,
                $booking,
                null,
                'reopen resolved',
                (string) $booking->id
            );
        } finally {
            $lock->release();
        }
    }

    /**
     * Status templates for one repeat occurrence (readable id, schedule, amounts from the repeat row).
     */
    public function sendBookingRepeatStatusChange(BookingRepeat $repeat, string $previousBookingStatus): void
    {
        $config = $this->getConfig();
        if (empty($config['enabled'])) {
            return;
        }

        $newStatus = (string) $repeat->booking_status;
        if ($previousBookingStatus === $newStatus) {
            return;
        }

        $transitionKey = self::CACHE_STATUS_SENT_PREFIX . 'repeat:' . $repeat->id . ':' . $previousBookingStatus . '>' . $newStatus;
        $lock = Cache::lock(self::CACHE_STATUS_LOCK_PREFIX . 'repeat:' . $repeat->id, 30);
        if (!$lock->get()) {
            return;
        }
        try {
            if (!Cache::add($transitionKey, 1, now()->addYears(5))) {
                return;
            }

            $repeat->loadMissing([
                'booking.customer',
                'booking.service_address',
                'booking.detail',
                'booking.booking_partial_payments',
                'detail',
                'provider.owner',
            ]);
            $parent = $repeat->booking;
            if (!$parent) {
                return;
            }

            $vars = $this->buildRepeatStatusReplacements($repeat, $parent, $previousBookingStatus);
            $segment = $this->resolveStatusTemplateSegment($previousBookingStatus, $newStatus);

            $customerPhone = $this->normalizePhone($parent->customer?->phone, $config);
            $this->deliverStatusTemplateMessage(
                $config,
                $segment,
                'customer',
                $vars,
                $customerPhone,
                $parent,
                $repeat,
                'repeat booking status',
                (string) $repeat->id
            );

            $provider = $repeat->provider ?? $parent->provider;
            $providerPhone = $this->resolveProviderPhone($provider, $config);
            $this->deliverStatusTemplateMessage(
                $config,
                $segment,
                'provider',
                $vars,
                $providerPhone,
                $parent,
                $repeat,
                'repeat booking status',
                (string) $repeat->id
            );
        } finally {
            $lock->release();
        }
    }

    /**
     * @return array<string, string>
     */
    protected function buildRepeatRowReplacements(BookingRepeat $repeat, Booking $parent): array
    {
        $parent->loadMissing(['customer', 'service_address', 'detail', 'booking_partial_payments']);
        $repeat->loadMissing(['detail', 'provider.owner', 'serviceman.user']);

        $vars = $this->buildReplacements($parent, null);

        $newKey = (string) ($repeat->booking_status ?? '');
        $vars['{booking_status}'] = $newKey !== '' ? ucwords(str_replace('_', ' ', $newKey)) : '—';
        $vars['{previous_booking_status}'] = '—';
        $vars['{booking_id}'] = (string) ($repeat->readable_id ?? $parent->readable_id ?? $parent->id);

        $vars['{booking_datetime}'] = $this->formatServiceDateTimeForMessages(
            $repeat->service_schedule ? (string) $repeat->service_schedule : null
        );

        $provider = $repeat->provider ?? $parent->provider;
        $providerName = $provider?->company_name
            ?: ($provider?->contact_person_name ?? '—');
        $vars['{provider_name}'] = $providerName;
        $vars['{provider_phone}'] = $this->formatProviderPhoneDisplay($provider);

        $serviceNames = $repeat->detail && $repeat->detail->isNotEmpty()
            ? $repeat->detail->pluck('service_name')->filter()->unique()->implode(', ')
            : '';
        if ($serviceNames === '') {
            $serviceNames = $parent->detail->pluck('service_name')->filter()->unique()->implode(', ');
        }
        if ($serviceNames === '') {
            $serviceNames = '—';
        }
        $vars['{service_name}'] = $serviceNames;

        $totalBill = get_booking_total_amount($repeat);
        $amountPaid = get_booking_total_paid($repeat);
        $due = max(0, round($totalBill - $amountPaid, 2));
        $vars['{total_bill}'] = function_exists('with_currency_symbol') ? with_currency_symbol($totalBill) : (string) $totalBill;
        $vars['{amount_paid}'] = function_exists('with_currency_symbol') ? with_currency_symbol($amountPaid) : (string) $amountPaid;
        $vars['{due_amount}'] = function_exists('with_currency_symbol') ? with_currency_symbol($due) : (string) $due;

        $customerAddress = $vars['{customer_address}'];
        $vars['{service_where}'] = $this->serviceWhereLabel($parent, $customerAddress, $provider);

        $vars['{payment_status}'] = $this->paymentPaidLabel((int) ($repeat->is_paid ?? 0));
        $sm = $this->servicemanDisplayPair($repeat->serviceman);
        $vars['{serviceman_name}'] = $sm['name'];
        $vars['{serviceman_phone}'] = $sm['phone'];
        $vars['{verification_status}'] = $this->verificationStateLabel((int) ($parent->is_verified ?? 0));
        $vars['{reopen_resolve_remarks}'] = '—';

        return $vars;
    }

    /**
     * @return array<string, string>
     */
    protected function buildRepeatStatusReplacements(BookingRepeat $repeat, Booking $parent, string $previousBookingStatus): array
    {
        $vars = $this->buildRepeatRowReplacements($repeat, $parent);
        $vars['{previous_booking_status}'] = $previousBookingStatus !== ''
            ? ucwords(str_replace('_', ' ', $previousBookingStatus))
            : '—';

        return $vars;
    }

    public function sendBookingScheduleChange(Booking $booking, ?string $previousServiceScheduleRaw): void
    {
        $config = $this->getConfig();
        if (empty($config['enabled'])) {
            return;
        }

        $prevFormatted = $this->formatScheduleToken($previousServiceScheduleRaw);
        $newFormatted = $this->formatScheduleToken($booking->service_schedule);
        if ($prevFormatted === $newFormatted) {
            return;
        }

        $dedupKey = self::CACHE_SCHEDULE_SENT_PREFIX . $booking->id . ':' . $prevFormatted . '>' . $newFormatted;
        $lock = Cache::lock(self::CACHE_SCHEDULE_LOCK_PREFIX . $booking->id, 30);
        if (!$lock->get()) {
            return;
        }
        try {
            if (!Cache::add($dedupKey, 1, now()->addYears(3))) {
                return;
            }

            $booking->loadMissing(['customer', 'provider.owner', 'service_address', 'detail', 'booking_partial_payments']);
            $vars = array_merge($this->buildReplacements($booking, null), [
                '{previous_service_schedule}' => $prevFormatted,
            ]);

            $this->sendTemplatePair($config, $vars, $booking->customer?->phone, $booking->provider, 'booking_schedule_customer', 'booking_schedule_provider', 'schedule', $booking->id);
        } finally {
            $lock->release();
        }
    }

    public function sendBookingRepeatScheduleChange(BookingRepeat $repeat, ?string $previousServiceScheduleRaw): void
    {
        $config = $this->getConfig();
        if (empty($config['enabled'])) {
            return;
        }

        $repeat->loadMissing(['booking.customer', 'booking.service_address', 'booking.detail', 'booking.booking_partial_payments', 'booking', 'detail', 'provider.owner', 'serviceman.user']);
        $parent = $repeat->booking;
        if (!$parent) {
            return;
        }

        $prevFormatted = $this->formatScheduleToken($previousServiceScheduleRaw);
        $newFormatted = $this->formatScheduleToken($repeat->service_schedule);
        if ($prevFormatted === $newFormatted) {
            return;
        }

        $dedupKey = self::CACHE_SCHEDULE_SENT_PREFIX . 'repeat:' . $repeat->id . ':' . $prevFormatted . '>' . $newFormatted;
        $lock = Cache::lock(self::CACHE_SCHEDULE_LOCK_PREFIX . 'repeat:' . $repeat->id, 30);
        if (!$lock->get()) {
            return;
        }
        try {
            if (!Cache::add($dedupKey, 1, now()->addYears(3))) {
                return;
            }

            $vars = array_merge($this->buildRepeatRowReplacements($repeat, $parent), [
                '{previous_service_schedule}' => $prevFormatted,
            ]);

            $provider = $repeat->provider ?? $parent->provider;
            $this->sendTemplatePair($config, $vars, $parent->customer?->phone, $provider, 'booking_schedule_customer', 'booking_schedule_provider', 'repeat_schedule', $repeat->id);
        } finally {
            $lock->release();
        }
    }

    public function sendBookingPaymentChange(Booking $booking, int $previousIsPaid): void
    {
        $config = $this->getConfig();
        if (empty($config['enabled'])) {
            return;
        }

        $newIsPaid = (int) ($booking->is_paid ?? 0);
        if ($previousIsPaid === $newIsPaid) {
            return;
        }

        $dedupKey = self::CACHE_PAYMENT_SENT_PREFIX . $booking->id . ':' . $previousIsPaid . '>' . $newIsPaid;
        $lock = Cache::lock(self::CACHE_PAYMENT_LOCK_PREFIX . $booking->id, 30);
        if (!$lock->get()) {
            return;
        }
        try {
            if (!Cache::add($dedupKey, 1, now()->addYears(3))) {
                return;
            }

            $booking->loadMissing(['customer', 'provider.owner', 'service_address', 'detail', 'booking_partial_payments']);
            $vars = array_merge($this->buildReplacements($booking, null), [
                '{previous_payment_status}' => $this->paymentPaidLabel($previousIsPaid),
                '{payment_status}' => $this->paymentPaidLabel($newIsPaid),
            ]);

            $this->sendTemplatePair($config, $vars, $booking->customer?->phone, $booking->provider, 'booking_payment_customer', 'booking_payment_provider', 'payment', $booking->id);
        } finally {
            $lock->release();
        }
    }

    public function sendBookingRepeatPaymentChange(BookingRepeat $repeat, int $previousIsPaid): void
    {
        $config = $this->getConfig();
        if (empty($config['enabled'])) {
            return;
        }

        $newIsPaid = (int) ($repeat->is_paid ?? 0);
        if ($previousIsPaid === $newIsPaid) {
            return;
        }

        $dedupKey = self::CACHE_PAYMENT_SENT_PREFIX . 'repeat:' . $repeat->id . ':' . $previousIsPaid . '>' . $newIsPaid;
        $lock = Cache::lock(self::CACHE_PAYMENT_LOCK_PREFIX . 'repeat:' . $repeat->id, 30);
        if (!$lock->get()) {
            return;
        }
        try {
            if (!Cache::add($dedupKey, 1, now()->addYears(3))) {
                return;
            }

            $repeat->loadMissing(['booking.customer', 'booking.service_address', 'booking.detail', 'booking.booking_partial_payments', 'booking', 'detail', 'provider.owner', 'serviceman.user']);
            $parent = $repeat->booking;
            if (!$parent) {
                return;
            }

            $vars = array_merge($this->buildRepeatRowReplacements($repeat, $parent), [
                '{previous_payment_status}' => $this->paymentPaidLabel($previousIsPaid),
                '{payment_status}' => $this->paymentPaidLabel($newIsPaid),
            ]);

            $provider = $repeat->provider ?? $parent->provider;
            $this->sendTemplatePair($config, $vars, $parent->customer?->phone, $provider, 'booking_payment_customer', 'booking_payment_provider', 'repeat_payment', $repeat->id);
        } finally {
            $lock->release();
        }
    }

    public function sendBookingServicemanChange(Booking $booking, ?string $previousServicemanId): void
    {
        $config = $this->getConfig();
        if (empty($config['enabled'])) {
            return;
        }

        $newId = $booking->serviceman_id ? (string) $booking->serviceman_id : '';
        $prevId = $previousServicemanId ? (string) $previousServicemanId : '';
        if ($prevId === $newId) {
            return;
        }

        $dedupKey = self::CACHE_SERVICEMAN_SENT_PREFIX . $booking->id . ':' . ($prevId ?: 'none') . '>' . ($newId ?: 'none');
        $lock = Cache::lock(self::CACHE_SERVICEMAN_LOCK_PREFIX . $booking->id, 30);
        if (!$lock->get()) {
            return;
        }
        try {
            if (!Cache::add($dedupKey, 1, now()->addYears(3))) {
                return;
            }

            $booking->loadMissing(['customer', 'provider.owner', 'service_address', 'detail', 'booking_partial_payments', 'serviceman.user']);
            $prevSm = $prevId !== '' ? Serviceman::with('user')->find($prevId) : null;
            $prevPair = $this->servicemanDisplayPair($prevSm);
            $curPair = $this->servicemanDisplayPair($booking->serviceman);

            $vars = array_merge($this->buildReplacements($booking, null), [
                '{previous_serviceman_name}' => $prevPair['name'],
                '{previous_serviceman_phone}' => $prevPair['phone'],
                '{serviceman_name}' => $curPair['name'],
                '{serviceman_phone}' => $curPair['phone'],
            ]);

            $this->sendTemplatePair($config, $vars, $booking->customer?->phone, $booking->provider, 'booking_serviceman_customer', 'booking_serviceman_provider', 'serviceman', $booking->id);
        } finally {
            $lock->release();
        }
    }

    public function sendBookingRepeatServicemanChange(BookingRepeat $repeat, ?string $previousServicemanId): void
    {
        $config = $this->getConfig();
        if (empty($config['enabled'])) {
            return;
        }

        $newId = $repeat->serviceman_id ? (string) $repeat->serviceman_id : '';
        $prevId = $previousServicemanId ? (string) $previousServicemanId : '';
        if ($prevId === $newId) {
            return;
        }

        $dedupKey = self::CACHE_SERVICEMAN_SENT_PREFIX . 'repeat:' . $repeat->id . ':' . ($prevId ?: 'none') . '>' . ($newId ?: 'none');
        $lock = Cache::lock(self::CACHE_SERVICEMAN_LOCK_PREFIX . 'repeat:' . $repeat->id, 30);
        if (!$lock->get()) {
            return;
        }
        try {
            if (!Cache::add($dedupKey, 1, now()->addYears(3))) {
                return;
            }

            $repeat->loadMissing(['booking.customer', 'booking.service_address', 'booking.detail', 'booking.booking_partial_payments', 'booking', 'detail', 'provider.owner', 'serviceman.user']);
            $parent = $repeat->booking;
            if (!$parent) {
                return;
            }

            $prevSm = $prevId !== '' ? Serviceman::with('user')->find($prevId) : null;
            $prevPair = $this->servicemanDisplayPair($prevSm);
            $curPair = $this->servicemanDisplayPair($repeat->serviceman);

            $vars = array_merge($this->buildRepeatRowReplacements($repeat, $parent), [
                '{previous_serviceman_name}' => $prevPair['name'],
                '{previous_serviceman_phone}' => $prevPair['phone'],
                '{serviceman_name}' => $curPair['name'],
                '{serviceman_phone}' => $curPair['phone'],
            ]);

            $provider = $repeat->provider ?? $parent->provider;
            $this->sendTemplatePair($config, $vars, $parent->customer?->phone, $provider, 'booking_serviceman_customer', 'booking_serviceman_provider', 'repeat_serviceman', $repeat->id);
        } finally {
            $lock->release();
        }
    }

    public function sendBookingVerificationChange(Booking $booking, int $previousIsVerified, string $action): void
    {
        $config = $this->getConfig();
        if (empty($config['enabled'])) {
            return;
        }

        $newV = (int) ($booking->is_verified ?? 0);
        $actionKey = strtolower(trim($action));
        $dedupKey = self::CACHE_VERIFICATION_SENT_PREFIX . $booking->id . ':' . $previousIsVerified . '>' . $newV . ':' . $actionKey;
        $lock = Cache::lock(self::CACHE_VERIFICATION_LOCK_PREFIX . $booking->id, 30);
        if (!$lock->get()) {
            return;
        }
        try {
            if (!Cache::add($dedupKey, 1, now()->addYears(3))) {
                return;
            }

            $booking->loadMissing(['customer', 'provider.owner', 'service_address', 'detail', 'booking_partial_payments']);
            $vars = array_merge($this->buildReplacements($booking, null), [
                '{previous_verification_status}' => $this->verificationStateLabel($previousIsVerified),
                '{verification_status}' => $this->verificationStateLabel($newV),
                '{verification_action}' => $this->verificationActionLabel($actionKey),
            ]);

            $this->sendTemplatePair($config, $vars, $booking->customer?->phone, $booking->provider, 'booking_verification_customer', 'booking_verification_provider', 'verification', $booking->id);
        } finally {
            $lock->release();
        }
    }

    /**
     * @param  array<string, string>  $vars
     */
    protected function sendTemplatePair(
        array $config,
        array $vars,
        ?string $customerPhoneRaw,
        ?Provider $provider,
        string $customerKey,
        string $providerKey,
        string $logContext,
        string $entityId
    ): void {
        $customerPhone = $this->normalizePhone($customerPhoneRaw, $config);
        $this->trySendBookingMetaOnly(
            $config,
            $customerKey,
            $vars,
            $customerPhone,
            'booking ' . $logContext . ' (customer)',
            ['id' => $entityId]
        );

        $providerPhone = $this->resolveProviderPhone($provider, $config);
        $this->trySendBookingMetaOnly(
            $config,
            $providerKey,
            $vars,
            $providerPhone,
            'booking ' . $logContext . ' (provider)',
            ['id' => $entityId]
        );
    }

    /**
     * Human-readable service date/time for all booking WhatsApp placeholders (booking_datetime, previous_service_schedule, dedupe keys).
     */
    protected function formatServiceDateTimeForMessages(?string $raw): string
    {
        if (!$raw) {
            return '—';
        }
        try {
            $dt = \Carbon\Carbon::parse($raw)->timezone(config('app.timezone'));

            return $dt->format('jS F Y g:i A');
        } catch (\Throwable) {
            return '—';
        }
    }

    protected function formatScheduleToken(?string $raw): string
    {
        return $this->formatServiceDateTimeForMessages($raw);
    }

    protected function paymentPaidLabel(int $isPaid): string
    {
        return $isPaid ? translate('Paid') : translate('Unpaid');
    }

    /**
     * @return array{name: string, phone: string}
     */
    protected function servicemanDisplayPair(?Serviceman $serviceman): array
    {
        if (!$serviceman) {
            return ['name' => '—', 'phone' => '—'];
        }
        $serviceman->loadMissing('user');
        $user = $serviceman->user;
        $name = $user
            ? trim(($user->first_name ?? '') . ' ' . ($user->last_name ?? ''))
            : '';
        if ($name === '') {
            $name = '—';
        }

        return [
            'name' => $name,
            'phone' => $user?->phone ? (string) $user->phone : '—',
        ];
    }

    protected function verificationStateLabel(int $code): string
    {
        return match ($code) {
            1 => translate('Approved'),
            2 => translate('Denied'),
            3 => translate('Canceled'),
            default => translate('Pending'),
        };
    }

    protected function verificationActionLabel(string $action): string
    {
        return match ($action) {
            'approve' => translate('Approved'),
            'deny' => translate('Denied'),
            'cancel' => translate('Canceled'),
            default => ucfirst($action),
        };
    }

    /**
     * Notify customer, previous provider, and new provider after admin reassignment.
     * Skips parties when the corresponding template is empty or no phone is available.
     */
    public function sendBookingProviderChange(Booking $booking, ?Provider $previousProvider): void
    {
        if (!$booking->provider_id) {
            return;
        }

        $config = $this->getConfig();
        if (empty($config['enabled'])) {
            return;
        }

        $prevId = $previousProvider?->id;
        if ($prevId !== null && (string) $prevId === (string) $booking->provider_id) {
            return;
        }

        $dedupKey = self::CACHE_PROVIDER_CHANGE_SENT_PREFIX . $booking->id . ':'
            . ($prevId ?? 'none') . '>' . $booking->provider_id;
        $lock = Cache::lock(self::CACHE_PROVIDER_CHANGE_LOCK_PREFIX . $booking->id, 30);
        if (!$lock->get()) {
            return;
        }
        try {
            if (!Cache::add($dedupKey, 1, now()->addYears(5))) {
                return;
            }

            $booking->loadMissing(['customer', 'provider.owner', 'service_address', 'detail', 'booking_partial_payments']);
            $prevExtras = $this->previousProviderReplacementMap($previousProvider);
            $vars = array_merge($this->buildReplacements($booking, null), $prevExtras);

            $customerPhone = $this->normalizePhone($booking->customer?->phone, $config);
            $this->trySendBookingMetaOnly(
                $config,
                'provider_change_customer',
                $vars,
                $customerPhone,
                'provider change (customer)',
                ['booking_id' => $booking->id]
            );

            if ($previousProvider) {
                $oldPhone = $this->resolveProviderPhone($previousProvider, $config);
                $this->trySendBookingMetaOnly(
                    $config,
                    'provider_change_previous_provider',
                    $vars,
                    $oldPhone,
                    'provider change (previous provider)',
                    ['booking_id' => $booking->id]
                );
            }

            $newProviderPhone = $this->resolveProviderPhone($booking->provider, $config);
            $this->trySendBookingMetaOnly(
                $config,
                'provider_change_new_provider',
                $vars,
                $newProviderPhone,
                'provider change (new provider)',
                ['booking_id' => $booking->id]
            );
        } finally {
            $lock->release();
        }
    }

    /**
     * @return array<string, string>
     */
    protected function previousProviderReplacementMap(?Provider $previousProvider): array
    {
        if (!$previousProvider) {
            return [
                '{previous_provider_name}' => '—',
                '{previous_provider_phone}' => '—',
            ];
        }

        $previousProvider->loadMissing(['owner']);
        $name = $previousProvider->company_name
            ?: ($previousProvider->contact_person_name ?? '—');

        return [
            '{previous_provider_name}' => $name,
            '{previous_provider_phone}' => $this->formatProviderPhoneDisplay($previousProvider),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function getConfig(): array
    {
        $row = business_config(self::SETTINGS_KEY, self::SETTINGS_TYPE);
        $stored = is_array($row?->live_values) ? $row->live_values : [];
        $base = array_merge(
            [
                'enabled' => false,
                'default_phone_prefix' => '91',
                'apply_default_phone_prefix' => true,
            ],
            self::defaultTemplateBodies()
        );

        $merged = array_replace($base, $stored);
        $merged['default_phone_prefix'] = '91';
        $merged['apply_default_phone_prefix'] = true;

        foreach (self::statusTemplateSegmentKeys() as $segment) {
            $ck = 'booking_status_invoice_customer_' . $segment;
            $pk = 'booking_status_invoice_provider_' . $segment;
            if (!array_key_exists($ck, $merged)) {
                $merged[$ck] = false;
            }
            if (!array_key_exists($pk, $merged)) {
                $merged[$pk] = false;
            }
            $merged[$ck] = filter_var($merged[$ck], FILTER_VALIDATE_BOOLEAN);
            $merged[$pk] = filter_var($merged[$pk], FILTER_VALIDATE_BOOLEAN);
        }

        foreach (self::configurableMessageKeys() as $msgKey) {
            $wk = $msgKey . '_wa_tpl_id';
            $bp = $msgKey . '_wa_body_params';
            if (!array_key_exists($wk, $merged)) {
                $merged[$wk] = null;
            } elseif ($merged[$wk] !== null && $merged[$wk] !== '') {
                $merged[$wk] = (int) $merged[$wk];
            } else {
                $merged[$wk] = null;
            }
            if (!isset($merged[$bp]) || !is_array($merged[$bp])) {
                $merged[$bp] = [];
            }
            $hp = $msgKey . '_wa_header_params';
            if (!isset($merged[$hp]) || !is_array($merged[$hp])) {
                $merged[$hp] = [];
            }
        }

        return $merged;
    }

    protected function triggerAdminId(): ?int
    {
        $u = Auth::user();

        return $u ? (int) $u->getAuthIdentifier() : null;
    }

    /**
     * @param  array<string, string>  $vars
     * @param  array<int, mixed>  $paramKeys
     * @return array<int, string>
     */
    protected function buildWaBodyParameterValues(array $vars, array $paramKeys): array
    {
        $out = [];
        foreach ($paramKeys as $key) {
            $k = is_string($key) ? trim($key) : '';
            if ($k === '' || !isset($vars[$k])) {
                $out[] = '';
            } else {
                $out[] = (string) $vars[$k];
            }
        }

        return $out;
    }

    /**
     * @param  array<string, mixed>  $logCtx
     * @param  array<int, string>  $headerTextParameters
     * @param  array<string, mixed>|null  $bodyPlan
     * @param  array<string, mixed>|null  $headerTextPlan
     */
    protected function sendTemplateAndRecord(
        string $phone,
        WhatsAppMarketingTemplate $template,
        array $bodyParameters,
        string $logLabel,
        array $logCtx,
        array $headerTextParameters = [],
        ?array $bodyPlan = null,
        ?array $headerTextPlan = null,
        ?string $headerMediaUrl = null,
        ?string $headerMediaFormat = null,
    ): bool {
        $err = null;
        $graphContext = null;
        $waId = $this->cloud->sendTemplateMessage(
            $phone,
            $template->name,
            $template->language,
            $bodyParameters,
            $err,
            $graphContext,
            $headerTextParameters,
            $headerMediaUrl,
            $headerMediaFormat,
            $bodyPlan,
            $headerTextPlan,
        );
        if ($err) {
            Log::warning('WhatsApp ' . $logLabel . ' failed (Meta template)', array_merge($logCtx, ['error' => $err]));

            return false;
        }
        if (!$waId) {
            return false;
        }
        $preview = '[' . translate('WhatsApp_template_message_label') . '] ' . $template->name;
        if ($bodyParameters !== []) {
            $preview .= "\n" . implode(' | ', $bodyParameters);
        }
        $this->tryPersistBookingOutbound($phone, $preview, $waId, 'TEXT', null);

        return true;
    }

    /**
     * @param  array<string, string>  $vars
     * @param  array<string, mixed>  $logCtx
     */
    protected function trySendWaTemplate(
        array $config,
        string $waStorageKey,
        array $vars,
        string $phone,
        string $logLabel,
        array $logCtx,
        ?string $headerMediaUrl = null,
        ?string $headerMediaFormat = null,
    ): bool {
        $tplId = (int) ($config[$waStorageKey . '_wa_tpl_id'] ?? 0);
        if ($tplId <= 0) {
            return false;
        }
        $template = WhatsAppMarketingTemplate::query()->find($tplId);
        if (!$template || strtoupper((string) $template->status) !== 'APPROVED') {
            return false;
        }
        $components = is_array($template->components) ? $template->components : [];
        $bodyPlan = WhatsAppCloudService::resolveBodyParameterPlanFromTemplate(['components' => $components]);
        $headerTextPlan = WhatsAppCloudService::resolveHeaderTextParameterPlanFromTemplate(['components' => $components]);

        $expectedBody = ($bodyPlan['format'] ?? '') === 'named'
            ? count($bodyPlan['named_param_names'] ?? [])
            : (int) ($bodyPlan['positional_count'] ?? 0);
        $expectedHeader = ($headerTextPlan['format'] ?? '') === 'named'
            ? count($headerTextPlan['named_param_names'] ?? [])
            : (int) ($headerTextPlan['positional_count'] ?? 0);

        $paramKeys = $config[$waStorageKey . '_wa_body_params'] ?? [];
        if (!is_array($paramKeys)) {
            $paramKeys = [];
        }
        $paramKeys = array_values($paramKeys);
        if (count($paramKeys) !== $expectedBody) {
            Log::warning('WhatsApp booking Meta template parameter count mismatch', [
                'message_key' => $waStorageKey,
                'template' => $template->name,
                'expected' => $expectedBody,
                'got' => count($paramKeys),
            ]);

            return false;
        }
        $bodyParams = $this->buildWaBodyParameterValues($vars, $paramKeys);

        $headerKeys = $config[$waStorageKey . '_wa_header_params'] ?? [];
        if (!is_array($headerKeys)) {
            $headerKeys = [];
        }
        $headerKeys = array_values($headerKeys);
        if (count($headerKeys) !== $expectedHeader) {
            Log::warning('WhatsApp booking Meta template header parameter count mismatch', [
                'message_key' => $waStorageKey,
                'template' => $template->name,
                'expected' => $expectedHeader,
                'got' => count($headerKeys),
            ]);

            return false;
        }
        $headerParams = $this->buildWaBodyParameterValues($vars, $headerKeys);

        return $this->sendTemplateAndRecord(
            $phone,
            $template,
            $bodyParams,
            $logLabel,
            $logCtx,
            $headerParams,
            $bodyPlan,
            $headerTextPlan,
            $headerMediaUrl,
            $headerMediaFormat
        );
    }

    /**
     * Public HTTPS URL for a file on the public disk (required by Meta for template header media links).
     */
    protected function absolutePublicUrlForStoragePath(string $relativePath): string
    {
        $path = Storage::disk('public')->url($relativePath);
        $override = (string) config('whatsappmodule.public_media_base_url', '');
        if ($override !== '') {
            $urlPath = parse_url($path, PHP_URL_PATH) ?: '';
            $query = parse_url($path, PHP_URL_QUERY);
            $suffix = $urlPath . ($query !== null && $query !== '' ? '?' . $query : '');

            return $override . $suffix;
        }
        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            return $path;
        }

        return rtrim((string) config('app.url'), '/') . '/' . ltrim($path, '/');
    }

    /**
     * @param  array<string, string>  $vars
     * @param  array<string, mixed>  $logCtx
     */
    protected function trySendBookingMetaOnly(
        array $config,
        string $messageKey,
        array $vars,
        ?string $phone,
        string $logLabel,
        array $logCtx
    ): void {
        if (!$phone) {
            return;
        }
        $this->trySendWaTemplate($config, $messageKey, $vars, $phone, $logLabel, $logCtx);
    }

    public function templateBodyText(WhatsAppMarketingTemplate $template): string
    {
        $components = is_array($template->components) ? $template->components : [];
        foreach ($components as $c) {
            if (!is_array($c)) {
                continue;
            }
            if (strtoupper((string) ($c['type'] ?? '')) === 'BODY') {
                return (string) ($c['text'] ?? '');
            }
        }

        return '';
    }

    /**
     * @param  array<int, string>  $positionalValues  Index 0 = {{1}}
     */
    protected function synthesizeFilledTemplateBody(string $bodyTemplate, array $positionalValues): string
    {
        $out = preg_replace_callback('/\{\{(\d+)\}\}/', function (array $m) use ($positionalValues) {
            $n = (int) $m[1];

            return $positionalValues[$n - 1] ?? '';
        }, $bodyTemplate);

        return is_string($out) ? $out : $bodyTemplate;
    }

    /**
     * @param  array<string, string>  $vars
     */
    protected function synthesizeCaptionFromBookingTemplate(
        WhatsAppMarketingTemplate $template,
        array $config,
        string $waStorageKey,
        array $vars
    ): string {
        $components = is_array($template->components) ? $template->components : [];
        $bodyPlan = WhatsAppCloudService::resolveBodyParameterPlanFromTemplate(['components' => $components]);
        $paramKeys = $config[$waStorageKey . '_wa_body_params'] ?? [];
        if (!is_array($paramKeys)) {
            $paramKeys = [];
        }
        $values = $this->buildWaBodyParameterValues($vars, array_values($paramKeys));
        $bodyText = $this->templateBodyText($template);

        if (($bodyPlan['format'] ?? '') === 'named' && !empty($bodyPlan['named_param_names'])) {
            foreach ($bodyPlan['named_param_names'] as $i => $name) {
                $bodyText = str_replace('{{' . $name . '}}', $values[$i] ?? '', $bodyText);
            }

            return $bodyText;
        }

        return $this->synthesizeFilledTemplateBody($bodyText, $values);
    }

    /**
     * Meta template binding for automated status messages (per-segment overrides general).
     *
     * @return array{params_storage_key: string, template_id: int}|null
     */
    protected function resolveWaBindingForStatus(array $config, string $party, string $segment): ?array
    {
        $suffix = $party === 'customer' ? 'customer' : 'provider';
        $specificKey = 'booking_status_' . $suffix . '_' . $segment;
        $generalKey = 'booking_status_' . $suffix;

        if (!empty($config[$specificKey . '_wa_tpl_id'])) {
            return [
                'params_storage_key' => $specificKey,
                'template_id' => (int) $config[$specificKey . '_wa_tpl_id'],
            ];
        }
        if (!empty($config[$generalKey . '_wa_tpl_id'])) {
            return [
                'params_storage_key' => $generalKey,
                'template_id' => (int) $config[$generalKey . '_wa_tpl_id'],
            ];
        }

        return null;
    }

    /**
     * Whether a Meta template is configured for this status segment (per-status row or general fallback).
     * Matches {@see self::resolveWaBindingForStatus()} so validation matches runtime sends.
     *
     * @param  array<string, mixed>  $config
     */
    public static function hasResolvedStatusWaTemplate(array $config, string $party, string $segment): bool
    {
        $suffix = $party === 'customer' ? 'customer' : 'provider';
        $specificKey = 'booking_status_' . $suffix . '_' . $segment;
        $generalKey = 'booking_status_' . $suffix;

        return ! empty($config[$specificKey . '_wa_tpl_id'] ?? null)
            || ! empty($config[$generalKey . '_wa_tpl_id'] ?? null);
    }

    /**
     * @param  array<string, mixed>  $logCtx
     */
    protected function sendTextAndRecord(string $phone, string $body, string $logLabel, array $logCtx): void
    {
        $err = null;
        $waId = $this->cloud->sendText($phone, $body, $err);
        if ($err) {
            Log::warning('WhatsApp ' . $logLabel . ' failed', array_merge($logCtx, ['error' => $err]));

            return;
        }
        if (!$waId) {
            return;
        }
        $this->tryPersistBookingOutbound($phone, $body, $waId, 'TEXT', null);
    }

    protected function tryPersistBookingOutbound(
        string $phone,
        string $body,
        string $waId,
        string $messageType = 'TEXT',
        ?string $mediaPath = null
    ): void {
        try {
            $this->messagePersistence->persistOutboundAutomation(
                $phone,
                $body,
                $waId,
                'Booking',
                $this->triggerAdminId(),
                $messageType,
                $mediaPath
            );
        } catch (\Throwable $e) {
            Log::warning('WhatsApp booking outbound persist failed', [
                'phone' => $phone,
                'message' => $e->getMessage(),
            ]);
        }
    }

    protected function archiveInvoiceCopyForConversation(string $relativePath): ?string
    {
        if ($relativePath === '' || !Storage::disk('public')->exists($relativePath)) {
            return null;
        }
        $dir = 'whatsapp-conversation-media/booking-invoices';
        Storage::disk('public')->makeDirectory($dir);
        $dest = $dir . '/' . str_replace('.', '', uniqid('inv_', true)) . '.pdf';
        if (!Storage::disk('public')->copy($relativePath, $dest)) {
            return null;
        }

        return $dest;
    }

    /**
     * @param  array<string, mixed>  $config  Unused; prefix is read from the same business settings as Cloud sends.
     */
    public function normalizePhone(?string $phone, array $config): ?string
    {
        if (!$phone) {
            return null;
        }

        return $this->cloud->normalizeRecipientPhone($phone);
    }

    /**
     * @return array<string, string>
     */
    public function buildReplacements(Booking $booking, ?string $previousBookingStatus): array
    {
        $booking->loadMissing([
            'detail',
            'serviceman.user',
            'latestParentCancellationStatusHistory.cancellationReason',
            'latestParentHoldStatusHistory.holdReopenReason',
        ]);

        $serviceNames = $booking->detail->pluck('service_name')->filter()->unique()->implode(', ');
        if ($serviceNames === '') {
            $serviceNames = '—';
        }

        $customer = $booking->customer;
        $customerName = $customer
            ? trim(($customer->first_name ?? '') . ' ' . ($customer->last_name ?? ''))
            : '';
        if ($customerName === '') {
            $customerName = '—';
        }

        $addr = $booking->service_address;
        $customerAddress = '—';
        if ($addr) {
            $parts = array_filter([
                $addr->address ?? null,
                $addr->street ?? null,
                $addr->city ?? null,
                $addr->zip_code ?? null,
                $addr->country ?? null,
            ]);
            $customerAddress = $parts !== [] ? implode(', ', $parts) : '—';
        }

        $provider = $booking->provider;
        $providerName = $provider?->company_name
            ?: ($provider?->contact_person_name ?? '—');

        $totalBill = get_booking_total_amount($booking);
        $amountPaid = get_booking_total_paid($booking);
        $due = max(0, round($totalBill - $amountPaid, 2));

        $schedule = $this->formatServiceDateTimeForMessages(
            $booking->service_schedule ? (string) $booking->service_schedule : null
        );

        $serviceWhere = $this->serviceWhereLabel($booking, $customerAddress, $provider);

        $statusKey = (string) ($booking->booking_status ?? '');
        $statusLabel = $statusKey !== '' ? ucwords(str_replace('_', ' ', $statusKey)) : '—';

        $prevKey = $previousBookingStatus ?? '';
        $prevLabel = $prevKey !== '' ? ucwords(str_replace('_', ' ', $prevKey)) : '—';

        $sm = $this->servicemanDisplayPair($booking->serviceman);

        $cancellationReasonText = trim((string) ($booking->latestParentCancellationStatusHistory?->cancellationReason?->name ?? ''));
        if ($cancellationReasonText === '') {
            $cancellationReasonText = '—';
        }

        $onHoldReasonText = trim((string) ($booking->latestParentHoldStatusHistory?->holdReopenReason?->name ?? ''));
        if ($onHoldReasonText === '') {
            $onHoldReasonText = '—';
        }

        $reopenEv = $booking->reopenFromCompletedDisplayEvent();
        $reopenFromCompletedText = '—';
        if ($reopenEv) {
            $reopenEv->loadMissing('holdReopenReason');
            $reopenFromCompletedText = trim((string) ($reopenEv->holdReopenReason?->name ?? ''));
            if ($reopenFromCompletedText === '') {
                $reopenFromCompletedText = trim((string) ($reopenEv->complaint_notes ?? ''));
            }
            if ($reopenFromCompletedText === '') {
                $reopenFromCompletedText = '—';
            }
        }

        $base = [
            '{service_name}' => $serviceNames,
            '{customer_address}' => $customerAddress,
            '{customer_name}' => $customerName,
            '{customer_phone}' => $customer?->phone ? (string) $customer->phone : '—',
            '{provider_name}' => $providerName,
            '{provider_phone}' => $this->formatProviderPhoneDisplay($provider),
            '{booking_id}' => (string) ($booking->readable_id ?? $booking->id),
            '{booking_datetime}' => $schedule,
            '{service_where}' => $serviceWhere,
            '{total_bill}' => function_exists('with_currency_symbol') ? with_currency_symbol($totalBill) : (string) $totalBill,
            '{amount_paid}' => function_exists('with_currency_symbol') ? with_currency_symbol($amountPaid) : (string) $amountPaid,
            '{due_amount}' => function_exists('with_currency_symbol') ? with_currency_symbol($due) : (string) $due,
            '{booking_status}' => $statusLabel,
            '{previous_booking_status}' => $prevLabel,
            '{previous_service_schedule}' => '—',
            '{payment_status}' => $this->paymentPaidLabel((int) ($booking->is_paid ?? 0)),
            '{previous_payment_status}' => '—',
            '{serviceman_name}' => $sm['name'],
            '{serviceman_phone}' => $sm['phone'],
            '{previous_serviceman_name}' => '—',
            '{previous_serviceman_phone}' => '—',
            '{verification_status}' => $this->verificationStateLabel((int) ($booking->is_verified ?? 0)),
            '{previous_verification_status}' => '—',
            '{verification_action}' => '—',
            '{reopen_resolve_remarks}' => '—',
            '{booking_cancellation_reason}' => $cancellationReasonText,
            '{on_hold_reason}' => $onHoldReasonText,
            '{reopen_from_completed_reason}' => $reopenFromCompletedText,
        ];

        $acData = self::buildAdditionalChargePlaceholderData();
        $acReplacements = [];
        foreach (enrich_booking_additional_charges_breakdown_for_display($booking) as $row) {
            $tid = (string) ($row['id'] ?? '');
            if ($tid === '') {
                continue;
            }
            $tokenKey = $acData['id_to_key'][$tid] ?? self::orphanAdditionalChargeTokenKey($tid, (string) ($row['name'] ?? ''));
            $amt = round((float) ($row['amount'] ?? 0), 2);
            $acReplacements[$tokenKey] = function_exists('with_currency_symbol') ? with_currency_symbol($amt) : (string) $amt;
        }

        return array_merge($base, $acReplacements);
    }

    protected function resolveStatusTemplateSegment(string $previousBookingStatus, string $newStatus): string
    {
        $prev = strtolower(trim($previousBookingStatus));
        $new = strtolower(trim($newStatus));
        if ($prev === 'completed' && in_array($new, ['pending', 'accepted'], true)) {
            return 'reopened';
        }

        $allowed = array_column(BOOKING_STATUSES, 'key');
        if (in_array($new, $allowed, true)) {
            return $new;
        }

        return $new !== '' ? $new : 'pending';
    }

    /**
     * @param  array<string, mixed>  $config
     */
    protected function statusInvoiceEnabled(array $config, string $party, string $segment): bool
    {
        $key = $party === 'customer'
            ? 'booking_status_invoice_customer_' . $segment
            : 'booking_status_invoice_provider_' . $segment;

        return !empty($config[$key]);
    }

    protected function truncateWhatsAppCaption(string $body): string
    {
        if (strlen($body) <= self::WHATSAPP_DOC_CAPTION_MAX) {
            return $body;
        }

        return substr($body, 0, self::WHATSAPP_DOC_CAPTION_MAX - 1) . '…';
    }

    /**
     * @param  array<string, string>  $vars
     */
    protected function deliverStatusTemplateMessage(
        array $config,
        string $segment,
        string $party,
        array $vars,
        ?string $phone,
        Booking $booking,
        ?BookingRepeat $repeat,
        string $logContext,
        string $entityId
    ): void {
        if (!$phone) {
            return;
        }

        $waBinding = $this->resolveWaBindingForStatus($config, $party, $segment);
        if ($waBinding === null) {
            return;
        }

        $template = WhatsAppMarketingTemplate::query()->find($waBinding['template_id']);
        if (!$template) {
            return;
        }

        $storageKey = $waBinding['params_storage_key'];
        $attachInvoice = $this->statusInvoiceEnabled($config, $party, $segment);
        $synthesized = $this->synthesizeCaptionFromBookingTemplate($template, $config, $storageKey, $vars);

        $relativePath = null;
        if ($attachInvoice) {
            $relativePath = $this->writeBookingInvoiceToPublicDisk($booking, $repeat);
        }

        $components = is_array($template->components) ? $template->components : [];
        $headerKind = WhatsAppCloudService::headerMediaFormatFromComponents($components);

        // Meta template with DOCUMENT header: send the approved template with the invoice PDF URL in the header
        // (body variables still use your mapping). Meta must be able to HTTP(S)-fetch that URL (not localhost/private IP).
        if ($relativePath && $headerKind === 'DOCUMENT') {
            $invoiceUrl = $this->absolutePublicUrlForStoragePath($relativePath);
            $sent = false;
            if (WhatsAppCloudService::isTemplateHeaderMediaLinkLikelyFetchableByMeta($invoiceUrl)) {
                $sent = $this->trySendWaTemplate(
                    $config,
                    $storageKey,
                    $vars,
                    $phone,
                    $logContext . ' (' . $party . ')',
                    ['entity_id' => $entityId],
                    $invoiceUrl,
                    'DOCUMENT'
                );
                if ($sent) {
                    Storage::disk('public')->delete($relativePath);
                    $invoiceParent = dirname($relativePath);
                    if (str_starts_with(basename($invoiceParent), 'wa_')) {
                        Storage::disk('public')->deleteDirectory($invoiceParent);
                    }

                    return;
                }

                Log::warning('WhatsApp booking template+invoice (document header) send failed; trying direct PDF send', [
                    'entity_id' => $entityId,
                    'template' => $template->name,
                ]);
            } else {
                Log::info('WhatsApp booking: skipping template DOCUMENT header for invoice (media URL not fetchable by Meta); sending PDF via direct upload', [
                    'entity_id' => $entityId,
                    'template' => $template->name,
                    'invoice_url_host' => parse_url($invoiceUrl, PHP_URL_HOST),
                ]);
            }
        }

        if ($relativePath) {
            $caption = $this->truncateWhatsAppCaption($synthesized);
            $err = null;
            $waId = $this->cloud->sendOutbound($phone, $caption, $relativePath, $err);
            $archivedPath = null;
            if ($waId) {
                $archivedPath = $this->archiveInvoiceCopyForConversation($relativePath);
            }
            Storage::disk('public')->delete($relativePath);
            $invoiceParent = dirname($relativePath);
            if (str_starts_with(basename($invoiceParent), 'wa_')) {
                Storage::disk('public')->deleteDirectory($invoiceParent);
            }
            if ($waId) {
                $persistType = $archivedPath ? 'DOCUMENT' : 'TEXT';
                $this->tryPersistBookingOutbound(
                    $phone,
                    $synthesized,
                    $waId,
                    $persistType,
                    $archivedPath
                );

                return;
            }

            Log::warning('WhatsApp ' . $logContext . ' (' . $party . ') document send failed, retrying plain text', [
                'entity_id' => $entityId,
                'error' => $err,
            ]);
            $this->sendTextAndRecord(
                $phone,
                $synthesized,
                $logContext . ' (' . $party . ') text fallback',
                ['entity_id' => $entityId]
            );

            return;
        }

        if ($attachInvoice && !$relativePath) {
            Log::warning('WhatsApp ' . $logContext . ' (' . $party . '): invoice PDF not generated', ['entity_id' => $entityId]);
        }

        $this->trySendWaTemplate(
            $config,
            $storageKey,
            $vars,
            $phone,
            $logContext . ' (' . $party . ')',
            ['entity_id' => $entityId]
        );
    }

    protected function writeBookingInvoiceToPublicDisk(Booking $booking, ?BookingRepeat $repeat): ?string
    {
        try {
            if (!class_exists(Pdf::class)) {
                return null;
            }

            $dir = 'whatsapp-booking-invoices';
            Storage::disk('public')->makeDirectory($dir);

            if ($repeat) {
                $row = BookingRepeat::query()
                    ->with([
                        'detail.service' => static fn ($q) => $q->withTrashed(),
                        'booking.extra_services',
                        'provider',
                        'serviceman',
                    ])
                    ->find($repeat->id);
                if (!$row || !$row->booking) {
                    return null;
                }
                $row->booking->loadMissing(['customer', 'service_address']);
                $row->booking->service_address = $row->booking->service_address_location != null
                    ? json_decode($row->booking->service_address_location)
                    : $row->booking->service_address;
                $pdf = Pdf::loadView('bookingmodule::admin.booking.fullbooking-single-invoice', ['booking' => $row]);
            } else {
                $b = Booking::query()
                    ->with([
                        'detail.service' => static fn ($q) => $q->withTrashed(),
                        'customer',
                        'provider',
                        'serviceman',
                        'status_histories.user',
                        'extra_services',
                        'booking_partial_payments',
                    ])
                    ->find($booking->id);
                if (!$b) {
                    return null;
                }
                $b->service_address = $b->service_address_location != null
                    ? json_decode($b->service_address_location)
                    : $b->service_address;
                $sub_total = $b->detail->sum(fn ($item) => $item->service_cost * $item->quantity);
                $extraServicesTotal = ($b->extra_services ?? collect())->sum('total');
                $pdf = Pdf::loadView('bookingmodule::admin.booking.invoice', [
                    'booking' => $b,
                    'sub_total' => $sub_total,
                    'extraServicesTotal' => $extraServicesTotal,
                ]);
            }

            $bookingRef = $repeat
                ? (string) ($repeat->readable_id ?? $booking->readable_id ?? $booking->id)
                : (string) ($booking->readable_id ?? $booking->id);
            $bookingRef = trim($bookingRef) !== '' ? trim($bookingRef) : (string) $booking->id;
            $safeRef = trim(preg_replace('/[^A-Za-z0-9\-_.]+/', '-', $bookingRef), '-');
            if ($safeRef === '') {
                $safeRef = (string) $booking->id;
            }

            $uniqueDir = $dir . '/' . str_replace('.', '', uniqid('wa_', true));
            Storage::disk('public')->makeDirectory($uniqueDir);

            $basename = 'Invoice - (' . $safeRef . ').pdf';
            $name = $uniqueDir . '/' . $basename;
            Storage::disk('public')->put($name, $pdf->output());

            return $name;
        } catch (\Throwable $e) {
            Log::warning('WhatsApp booking invoice PDF failed', [
                'booking_id' => $booking->id,
                'repeat_id' => $repeat?->id,
                'message' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * @param  array<string, mixed>  $config
     */
    protected function resolveProviderPhone(?Provider $provider, array $config): ?string
    {
        if (!$provider) {
            return null;
        }
        $raw = $provider->company_phone
            ?: $provider->contact_person_phone
            ?: $provider->owner?->phone;

        return $this->normalizePhone($raw, $config);
    }

    protected function formatProviderPhoneDisplay(?Provider $provider): string
    {
        if (!$provider) {
            return '—';
        }
        $raw = $provider->company_phone
            ?: $provider->contact_person_phone
            ?: $provider->owner?->phone;

        return $raw ? (string) $raw : '—';
    }

    protected function serviceWhereLabel(Booking $booking, string $customerAddress, ?Provider $provider): string
    {
        $loc = $booking->service_location ?? 'customer';
        if ($loc === 'provider') {
            $a = $provider?->company_address;

            return $a ? (string) $a : translate('Service_at_provider_address');
        }

        return $customerAddress !== '—' ? $customerAddress : translate('Service_at_customer_address');
    }

    /**
     * @param  array<string, string>  $vars
     */
    protected function interpolate(string $template, array $vars): string
    {
        return strtr($template, $vars);
    }
}
