<?php

namespace Modules\WhatsAppModule\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Modules\BookingModule\Entities\Booking;
use Modules\BookingModule\Entities\BookingRepeat;
use Modules\ProviderManagement\Entities\Provider;
use Modules\UserManagement\Entities\Serviceman;

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

    /** @var array<string, string> */
    public const PLACEHOLDER_HINTS = [
        '{service_name}' => 'Service name(s)',
        '{customer_address}' => 'Customer address',
        '{customer_name}' => 'Customer name',
        '{customer_phone}' => 'Customer phone',
        '{provider_name}' => 'Provider / company name',
        '{provider_phone}' => 'Provider phone',
        '{booking_id}' => 'Booking reference (readable id)',
        '{booking_datetime}' => 'Scheduled service date & time',
        '{service_where}' => 'Where service will be provided',
        '{total_bill}' => 'Total bill',
        '{amount_paid}' => 'Amount paid so far',
        '{due_amount}' => 'Amount due',
        '{booking_status}' => 'Current booking status',
        '{previous_booking_status}' => 'Previous booking status (status updates only)',
        '{previous_provider_name}' => 'Previous provider name (provider change only)',
        '{previous_provider_phone}' => 'Previous provider phone (provider change only)',
        '{previous_service_schedule}' => 'Previous service date & time (schedule change only)',
        '{payment_status}' => 'Payment status (Paid / Unpaid)',
        '{previous_payment_status}' => 'Previous payment status (payment updates only)',
        '{serviceman_name}' => 'Assigned serviceman name',
        '{serviceman_phone}' => 'Assigned serviceman phone',
        '{previous_serviceman_name}' => 'Previous serviceman name (assignment change only)',
        '{previous_serviceman_phone}' => 'Previous serviceman phone (assignment change only)',
        '{verification_status}' => 'Booking verification state (e.g. Approved, Denied)',
        '{previous_verification_status}' => 'Previous verification state',
        '{verification_action}' => 'Last verification action: approve, deny, or cancel',
    ];

    /**
     * Default bodies for new installs / migrations (customer: service + provider; provider: customer + service).
     *
     * @return array<string, string>
     */
    public static function defaultTemplateBodies(): array
    {
        return [
            'booking_confirmation_customer' => "Hello {customer_name},\n\nYour booking *{booking_id}* is confirmed.\n\n*Service*\n{service_name}\nWhen: {booking_datetime}\nWhere: {service_where}\n\n*Provider*\n{provider_name}\nPhone: {provider_phone}\n\n*Payment*\nTotal: {total_bill}\nPaid: {amount_paid}\nDue: {due_amount}\n\nThank you for choosing us. Reply here if you have questions.",
            'booking_confirmation_provider' => "New booking *{booking_id}*\n\n*Customer*\n{customer_name}\nPhone: {customer_phone}\nAddress: {customer_address}\n\n*Service*\n{service_name}\nWhen: {booking_datetime}\nService at: {service_where}\n\n*Payment*\nTotal: {total_bill}\nPaid: {amount_paid}\nDue: {due_amount}\n\nPlease review the booking in your app or dashboard.",
            'booking_status_customer' => "Booking update\n\nBooking *{booking_id}* status changed:\n{previous_booking_status} → *{booking_status}*\n\n*Service*\n{service_name}\nWhen: {booking_datetime}\n\n*Provider*\n{provider_name}\nPhone: {provider_phone}\n\nIf anything looks wrong, contact us.",
            'booking_status_provider' => "Booking update\n\nBooking *{booking_id}* status changed:\n{previous_booking_status} → *{booking_status}*\n\n*Customer*\n{customer_name}\nPhone: {customer_phone}\n\n*Service*\n{service_name}\nWhen: {booking_datetime}\n\nCheck your app for full details.",
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
        ];
    }

    public function __construct(
        protected WhatsAppCloudService $cloud
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
            $customerTpl = trim((string) ($config['booking_confirmation_customer'] ?? ''));
            $providerTpl = trim((string) ($config['booking_confirmation_provider'] ?? ''));

            $customerPhone = $this->normalizePhone($booking->customer?->phone, $config);
            if ($customerTpl !== '' && $customerPhone) {
                $body = $this->interpolate($customerTpl, $vars);
                $err = null;
                $this->cloud->sendText($customerPhone, $body, $err);
                if ($err) {
                    Log::warning('WhatsApp booking confirm (customer) failed', ['booking_id' => $booking->id, 'error' => $err]);
                }
            }

            $providerPhone = $this->resolveProviderPhone($booking->provider, $config);
            if ($providerTpl !== '' && $providerPhone) {
                $body = $this->interpolate($providerTpl, $vars);
                $err = null;
                $this->cloud->sendText($providerPhone, $body, $err);
                if ($err) {
                    Log::warning('WhatsApp booking confirm (provider) failed', ['booking_id' => $booking->id, 'error' => $err]);
                }
            }
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
            $customerTpl = trim((string) ($config['booking_status_customer'] ?? ''));
            $providerTpl = trim((string) ($config['booking_status_provider'] ?? ''));

            $customerPhone = $this->normalizePhone($booking->customer?->phone, $config);
            if ($customerTpl !== '' && $customerPhone) {
                $body = $this->interpolate($customerTpl, $vars);
                $err = null;
                $this->cloud->sendText($customerPhone, $body, $err);
                if ($err) {
                    Log::warning('WhatsApp booking status (customer) failed', ['booking_id' => $booking->id, 'error' => $err]);
                }
            }

            $providerPhone = $this->resolveProviderPhone($booking->provider, $config);
            if ($providerTpl !== '' && $providerPhone) {
                $body = $this->interpolate($providerTpl, $vars);
                $err = null;
                $this->cloud->sendText($providerPhone, $body, $err);
                if ($err) {
                    Log::warning('WhatsApp booking status (provider) failed', ['booking_id' => $booking->id, 'error' => $err]);
                }
            }
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
            $customerTpl = trim((string) ($config['booking_status_customer'] ?? ''));
            $providerTpl = trim((string) ($config['booking_status_provider'] ?? ''));

            $customerPhone = $this->normalizePhone($parent->customer?->phone, $config);
            if ($customerTpl !== '' && $customerPhone) {
                $body = $this->interpolate($customerTpl, $vars);
                $err = null;
                $this->cloud->sendText($customerPhone, $body, $err);
                if ($err) {
                    Log::warning('WhatsApp repeat booking status (customer) failed', ['booking_repeat_id' => $repeat->id, 'error' => $err]);
                }
            }

            $provider = $repeat->provider ?? $parent->provider;
            $providerPhone = $this->resolveProviderPhone($provider, $config);
            if ($providerTpl !== '' && $providerPhone) {
                $body = $this->interpolate($providerTpl, $vars);
                $err = null;
                $this->cloud->sendText($providerPhone, $body, $err);
                if ($err) {
                    Log::warning('WhatsApp repeat booking status (provider) failed', ['booking_repeat_id' => $repeat->id, 'error' => $err]);
                }
            }
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

        $schedule = $repeat->service_schedule
            ? \Carbon\Carbon::parse($repeat->service_schedule)->format('Y-m-d H:i')
            : '—';
        $vars['{booking_datetime}'] = $schedule;

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
        $customerTpl = trim((string) ($config[$customerKey] ?? ''));
        $providerTpl = trim((string) ($config[$providerKey] ?? ''));

        $customerPhone = $this->normalizePhone($customerPhoneRaw, $config);
        if ($customerTpl !== '' && $customerPhone) {
            $body = $this->interpolate($customerTpl, $vars);
            $err = null;
            $this->cloud->sendText($customerPhone, $body, $err);
            if ($err) {
                Log::warning('WhatsApp booking ' . $logContext . ' (customer) failed', ['id' => $entityId, 'error' => $err]);
            }
        }

        $providerPhone = $this->resolveProviderPhone($provider, $config);
        if ($providerTpl !== '' && $providerPhone) {
            $body = $this->interpolate($providerTpl, $vars);
            $err = null;
            $this->cloud->sendText($providerPhone, $body, $err);
            if ($err) {
                Log::warning('WhatsApp booking ' . $logContext . ' (provider) failed', ['id' => $entityId, 'error' => $err]);
            }
        }
    }

    protected function formatScheduleToken(?string $raw): string
    {
        if (!$raw) {
            return '—';
        }
        try {
            return \Carbon\Carbon::parse($raw)->format('Y-m-d H:i');
        } catch (\Throwable) {
            return '—';
        }
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

            $customerTpl = trim((string) ($config['provider_change_customer'] ?? ''));
            $oldTpl = trim((string) ($config['provider_change_previous_provider'] ?? ''));
            $newTpl = trim((string) ($config['provider_change_new_provider'] ?? ''));

            $customerPhone = $this->normalizePhone($booking->customer?->phone, $config);
            if ($customerTpl !== '' && $customerPhone) {
                $body = $this->interpolate($customerTpl, $vars);
                $err = null;
                $this->cloud->sendText($customerPhone, $body, $err);
                if ($err) {
                    Log::warning('WhatsApp provider change (customer) failed', ['booking_id' => $booking->id, 'error' => $err]);
                }
            }

            if ($previousProvider && $oldTpl !== '') {
                $oldPhone = $this->resolveProviderPhone($previousProvider, $config);
                if ($oldPhone) {
                    $body = $this->interpolate($oldTpl, $vars);
                    $err = null;
                    $this->cloud->sendText($oldPhone, $body, $err);
                    if ($err) {
                        Log::warning('WhatsApp provider change (previous provider) failed', ['booking_id' => $booking->id, 'error' => $err]);
                    }
                }
            }

            $newProviderPhone = $this->resolveProviderPhone($booking->provider, $config);
            if ($newTpl !== '' && $newProviderPhone) {
                $body = $this->interpolate($newTpl, $vars);
                $err = null;
                $this->cloud->sendText($newProviderPhone, $body, $err);
                if ($err) {
                    Log::warning('WhatsApp provider change (new provider) failed', ['booking_id' => $booking->id, 'error' => $err]);
                }
            }
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
                'default_phone_prefix' => '',
            ],
            self::defaultTemplateBodies()
        );

        return array_replace($base, $stored);
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
        $booking->loadMissing(['detail', 'serviceman.user']);

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

        $schedule = $booking->service_schedule
            ? \Carbon\Carbon::parse($booking->service_schedule)->format('Y-m-d H:i')
            : '—';

        $serviceWhere = $this->serviceWhereLabel($booking, $customerAddress, $provider);

        $statusKey = (string) ($booking->booking_status ?? '');
        $statusLabel = $statusKey !== '' ? ucwords(str_replace('_', ' ', $statusKey)) : '—';

        $prevKey = $previousBookingStatus ?? '';
        $prevLabel = $prevKey !== '' ? ucwords(str_replace('_', ' ', $prevKey)) : '—';

        $sm = $this->servicemanDisplayPair($booking->serviceman);

        return [
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
        ];
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
