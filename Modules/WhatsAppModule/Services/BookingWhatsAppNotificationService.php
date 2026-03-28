<?php

namespace Modules\WhatsAppModule\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Modules\BookingModule\Entities\Booking;
use Modules\ProviderManagement\Entities\Provider;

class BookingWhatsAppNotificationService
{
    public const SETTINGS_KEY = 'whatsapp_booking_templates';

    public const SETTINGS_TYPE = 'whatsapp';

    public const CACHE_PREVIOUS_STATUS_PREFIX = 'wa:booking_prev_status:';

    public const CACHE_CONFIRM_LOCK_PREFIX = 'wa:lock:bcf:';

    public const CACHE_CONFIRM_SENT_PREFIX = 'wa:bcf:sent:';

    public const CACHE_STATUS_LOCK_PREFIX = 'wa:lock:bst:';

    public const CACHE_STATUS_SENT_PREFIX = 'wa:bst:sent:';

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
    ];

    /**
     * Default bodies for new installs / migrations (customer: service + provider; provider: customer + service).
     *
     * @return array<string, string>
     */
    public static function defaultTemplateBodies(): array
    {
        return [
            'booking_confirmation_customer' => "Hello {customer_name},\n\nYour booking *{booking_id}* is confirmed.\n\n*Service*\n{service_name}\nWhen: {booking_datetime}\nWhere: {service_where}\n\n*Provider*\n{provider_name}\nPhone: {provider_phone}\n\n*Payment*\nTotal: {total_bill}\nPaid: {amount_paid}\nDue: {due_amount}",
            'booking_confirmation_provider' => "New booking *{booking_id}*\n\n*Customer*\n{customer_name}\nPhone: {customer_phone}\nAddress: {customer_address}\n\n*Service*\n{service_name}\nWhen: {booking_datetime}\nService at: {service_where}\n\nTotal: {total_bill}\nPaid: {amount_paid}\nDue: {due_amount}",
            'booking_status_customer' => "Booking update\n\nBooking *{booking_id}* status changed:\n{previous_booking_status} → *{booking_status}*\n\nService: {service_name}\nWhen: {booking_datetime}\nProvider: {provider_name} ({provider_phone})",
            'booking_status_provider' => "Booking update\n\nBooking *{booking_id}* status changed:\n{previous_booking_status} → *{booking_status}*\n\nCustomer: {customer_name} ({customer_phone})\nService: {service_name}\nWhen: {booking_datetime}",
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
     * @return array<string, mixed>
     */
    public function getConfig(): array
    {
        $row = business_config(self::SETTINGS_KEY, self::SETTINGS_TYPE);

        return is_array($row?->live_values) ? $row->live_values : [];
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
