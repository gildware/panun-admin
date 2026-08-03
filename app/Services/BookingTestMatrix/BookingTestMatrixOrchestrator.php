<?php

namespace App\Services\BookingTestMatrix;

use Illuminate\Support\Facades\DB;
use Modules\BookingModule\Entities\Booking;
use Modules\BookingModule\Entities\BookingCompensation;
use Modules\BookingModule\Entities\BookingDetail;
use Modules\BookingModule\Entities\BookingPartialPayment;
use Modules\BookingModule\Entities\BookingReopenEvent;
use Modules\BookingModule\Entities\BookingStatusHistory;
use Modules\BookingModule\Services\AdminBookingDeletionService;
use Modules\BookingModule\Services\BookingFinancialSettlementService;
use Modules\ProviderManagement\Entities\Provider;
use Modules\ServiceManagement\Entities\Service;
use Modules\TransactionModule\Entities\LedgerTransaction;
use Modules\UserManagement\Entities\User;

class BookingTestMatrixOrchestrator
{
    public const TAG = '[LIFECYCLE-TEST-MATRIX]';

    public const CUSTOMER_PHONE = '9353294016';

    public const PROVIDER_PHONE = '9090909090';

    public function __construct(
        private readonly BookingPlacementBridge $placement,
        private readonly BookingLifecycleActions $actions,
    ) {}

    /**
     * @return array<string, Booking>
     */
    public function seedAll(bool $fresh = true): array
    {
        $ctx = $this->resolveContext();
        if ($fresh) {
            $this->wipe();
        }

        $this->suppressOutboundMessaging();

        $created = [];
        DB::transaction(function () use ($ctx, &$created) {
            $tag = static fn (string $key): string => self::TAG . ' ' . $key;
            $schedulePast = now()->subHour()->format('Y-m-d H:i:s');
            $scheduleFuture = now()->addDays(2)->format('Y-m-d H:i:s');

            // 1. Pending (unassigned — shows in provider Requests)
            $created['pending'] = $this->placement->place(
                $ctx['customer']->id,
                $ctx['service'],
                [
                    'payment_method' => 'cash_after_service',
                    'service_schedule' => $scheduleFuture,
                ],
                $tag('pending'),
            );

            // 2. Accepted
            $b = $this->placement->place($ctx['customer']->id, $ctx['service'], [
                'payment_method' => 'cash_after_service',
                'service_schedule' => $schedulePast,
                'provider_id' => $ctx['provider']->id,
            ], $tag('accepted'));
            $created['accepted'] = $this->actions->providerAccept($b, $ctx['provider_user'], $ctx['provider']);

            // 3. Canceled (customer cancel from pending)
            $b = $this->placement->place($ctx['customer']->id, $ctx['service'], [
                'payment_method' => 'cash_after_service',
                'service_schedule' => $scheduleFuture,
                'provider_id' => $ctx['provider']->id,
            ], $tag('canceled'));
            $b = $this->actions->providerAccept($b, $ctx['provider_user'], $ctx['provider']);
            $created['canceled'] = $this->actions->customerCancel($b, $ctx['customer']);

            // 4. Ongoing
            $b = $this->placement->place($ctx['customer']->id, $ctx['service'], [
                'payment_method' => 'cash_after_service',
                'service_schedule' => $schedulePast,
                'provider_id' => $ctx['provider']->id,
            ], $tag('ongoing'));
            $created['ongoing'] = $this->actions->advanceToOngoing($b, $ctx['provider_user']);

            // 5. Completed — company received (digital at checkout)
            $b = $this->placement->place($ctx['customer']->id, $ctx['service'], [
                'payment_method' => 'digital_payment',
                'service_schedule' => $schedulePast,
                'provider_id' => $ctx['provider']->id,
            ], $tag('completed_company_paid'));
            $b = $this->actions->advanceToOngoing($b, $ctx['provider_user']);
            $created['completed'] = $this->actions->providerStatus($b, $ctx['provider_user'], 'completed', null, true);

            // 6. Completed — provider collected CAS on site
            $b = $this->placement->place($ctx['customer']->id, $ctx['service'], [
                'payment_method' => 'cash_after_service',
                'service_schedule' => $schedulePast,
                'provider_id' => $ctx['provider']->id,
            ], $tag('completed_provider_paid'));
            $b = $this->actions->advanceToOngoing($b, $ctx['provider_user']);
            $created['completed_provider_paid'] = $this->actions->completeCasWithProviderPayment($b, $ctx['provider_user']);

            // 7. On hold
            $b = $this->placement->place($ctx['customer']->id, $ctx['service'], [
                'payment_method' => 'cash_after_service',
                'service_schedule' => $schedulePast,
                'provider_id' => $ctx['provider']->id,
            ], $tag('on_hold'));
            $b = $this->actions->advanceToOngoing($b, $ctx['provider_user']);
            $created['on_hold'] = $this->actions->providerStatus($b, $ctx['provider_user'], 'on_hold', $ctx['hold_reason_id']);

            // 8. Hold after visit (ongoing → on_hold)
            $b = $this->placement->place($ctx['customer']->id, $ctx['service'], [
                'payment_method' => 'cash_after_service',
                'service_schedule' => $schedulePast,
                'provider_id' => $ctx['provider']->id,
            ], $tag('hold_after_visit'));
            $b = $this->actions->advanceToOngoing($b, $ctx['provider_user']);
            $created['hold_after_visit'] = $this->actions->providerStatus($b, $ctx['provider_user'], 'on_hold', $ctx['hold_reason_id']);

            // 9. Reopened (in-place from completed)
            $b = $this->placement->place($ctx['customer']->id, $ctx['service'], [
                'payment_method' => 'digital_payment',
                'service_schedule' => $schedulePast,
                'provider_id' => $ctx['provider']->id,
            ], $tag('reopened'));
            $b = $this->actions->advanceToOngoing($b, $ctx['provider_user']);
            $b = $this->actions->providerStatus($b, $ctx['provider_user'], 'completed', null, true);
            $created['reopened'] = $this->actions->reopenInPlace($b, $ctx['admin'], 'accepted');

            // 10. Resolved (reopen → complete → resolve case)
            $b = $this->placement->place($ctx['customer']->id, $ctx['service'], [
                'payment_method' => 'digital_payment',
                'service_schedule' => $schedulePast,
                'provider_id' => $ctx['provider']->id,
            ], $tag('resolved'));
            $b = $this->actions->advanceToOngoing($b, $ctx['provider_user']);
            $b = $this->actions->providerStatus($b, $ctx['provider_user'], 'completed', null, true);
            $b = $this->actions->reopenInPlace($b, $ctx['admin'], 'accepted');
            $b = $this->actions->advanceToOngoing($b, $ctx['provider_user']);
            $b = $this->actions->providerStatus($b, $ctx['provider_user'], 'completed', null, true);
            $created['resolved'] = $this->actions->resolveReopen($b, $ctx['admin']);

            // 11. Disputed + cancelled (full refund from ongoing — never completed before dispute)
            $b = $this->placement->place($ctx['customer']->id, $ctx['service'], [
                'payment_method' => 'digital_payment',
                'service_schedule' => $schedulePast,
                'provider_id' => $ctx['provider']->id,
            ], $tag('disputed_cancelled'));
            $b = $this->actions->advanceToOngoing($b, $ctx['provider_user']);
            $b = $this->ensureRecordedCustomerPayment($b);
            $paid = round((float) get_booking_total_paid($b->fresh()), 2);
            $split = booking_customer_paid_split_by_receiver($b->fresh());
            $refundCompany = round((float) $split['company'] + (float) $split['unassigned'], 2);
            $refundProvider = round((float) $split['provider'], 2);
            if (($refundCompany + $refundProvider) < 0.01 && $paid > 0) {
                $refundCompany = $paid;
            }
            $created['disputed_cancelled'] = $this->actions->adminDisputedRefund($b, $ctx['admin'], [
                'booking_dispute_reason_id' => $ctx['dispute_reason_id'],
                'refund_company_amount' => $refundCompany,
                'refund_provider_amount' => $refundProvider,
                'refund_company_transaction_id' => 'TEST-REF-CO-' . substr($b->readable_id, -4),
                'refund_provider_transaction_id' => $paid > 0.01 ? 'TEST-REF-PR-' . substr($b->readable_id, -4) : '',
                'reopen_dispute_remarks' => 'Lifecycle test matrix — disputed full refund',
            ]);

            // 12. Disputed + completed (partial refund, customer retains portion)
            $b = $this->placement->place($ctx['customer']->id, $ctx['service'], [
                'payment_method' => 'digital_payment',
                'service_schedule' => $schedulePast,
                'provider_id' => $ctx['provider']->id,
            ], $tag('disputed_completed'));
            $b = $this->actions->advanceToOngoing($b, $ctx['provider_user']);
            $b = $this->actions->providerStatus($b, $ctx['provider_user'], 'completed', null, true);
            $b = $this->actions->reopenInPlace($b, $ctx['admin'], 'accepted');
            $b = $this->actions->advanceToOngoing($b, $ctx['provider_user']);
            $b = $this->ensureRecordedCustomerPayment($b);
            $paid = round((float) get_booking_total_paid($b->fresh()), 2);
            $retain = $this->resolveDisputedPartialRetain($paid);
            $refundTotal = round(max(0.0, $paid - $retain), 2);
            $created['disputed_completed'] = $this->actions->adminDisputedRefund($b, $ctx['admin'], [
                'booking_dispute_reason_id' => $ctx['dispute_reason_id'],
                'refund_company_amount' => round($refundTotal * 0.6, 2),
                'refund_provider_amount' => round($refundTotal * 0.4, 2),
                'refund_company_transaction_id' => 'TEST-REF-CO2-' . substr($b->readable_id, -4),
                'refund_provider_transaction_id' => 'TEST-REF-PR2-' . substr($b->readable_id, -4),
                'final_services_retained_from_customer' => $retain,
                'reopen_dispute_remarks' => 'Lifecycle test matrix — disputed partial refund',
            ]);

            // 13. Complete with no / little service (visit fee split)
            $b = $this->placement->place($ctx['customer']->id, $ctx['service'], [
                'payment_method' => 'cash_after_service',
                'service_schedule' => $schedulePast,
                'provider_id' => $ctx['provider']->id,
            ], $tag('completed_no_or_little'));
            $b = $this->actions->advanceToOngoing($b, $ctx['provider_user']);
            $visitPaid = 200.0;
            $closingPaid = 100.0;
            $this->actions->adminRecordPayment($b, $ctx['admin'], $visitPaid, 'provider');
            $this->actions->adminRecordPayment($b->fresh(), $ctx['admin'], $closingPaid, 'company');
            $created['completed_no_or_little'] = $this->actions->adminFinancialSaveAndComplete($b->fresh(), $ctx['admin'], [
                'settlement_outcome' => BookingFinancialSettlementService::OUTCOME_VISIT_FEE_SPLIT,
                'visit_charges_paid' => $visitPaid,
                'visit_fee_company_percent' => 30,
                'closing_amount_paid' => $closingPaid,
                'closing_company_share' => 40,
                'closing_provider_share' => 60,
            ]);

            // 14. Cancel after visit
            $b = $this->placement->place($ctx['customer']->id, $ctx['service'], [
                'payment_method' => 'cash_after_service',
                'service_schedule' => $schedulePast,
                'provider_id' => $ctx['provider']->id,
            ], $tag('cancelled_after_visit'));
            $b = $this->actions->advanceToOngoing($b, $ctx['provider_user']);
            $visitOnly = 250.0;
            $this->actions->adminRecordPayment($b, $ctx['admin'], $visitOnly, 'provider');
            $created['cancelled_after_visit'] = $this->actions->adminFinancialSaveAndCancel($b->fresh(), $ctx['admin'], [
                'settlement_outcome' => BookingFinancialSettlementService::OUTCOME_VISIT_RETAINED_CANCEL,
                'booking_cancellation_reason_id' => $ctx['cancel_reason_id'],
                'visit_charges_paid' => $visitOnly,
                'visit_fee_company_percent' => 30,
                'closing_amount_paid' => 0,
                'closing_company_share' => 0,
                'closing_provider_share' => 0,
            ]);

            // 15. Loss making (pending recovery)
            $b = $this->placement->place($ctx['customer']->id, $ctx['service'], [
                'payment_method' => 'cash_after_service',
                'service_schedule' => $schedulePast,
                'provider_id' => $ctx['provider']->id,
            ], $tag('loss_making_pending'));
            $grand = round((float) get_booking_total_amount($b->fresh()), 2);
            $declaredPaid = round($grand * 0.45, 2);
            $loss = round($grand - $declaredPaid, 2);
            $created['loss_making_pending'] = $this->actions->applyScaledLossSettlement(
                $b,
                $ctx['admin'],
                $declaredPaid,
                round($loss * 0.55, 2),
                round($loss * 0.45, 2),
            );

            // 16. Loss recovered (scaled + full recovery payments)
            $b = $this->placement->place($ctx['customer']->id, $ctx['service'], [
                'payment_method' => 'cash_after_service',
                'service_schedule' => $schedulePast,
                'provider_id' => $ctx['provider']->id,
            ], $tag('loss_recovered'));
            $grand = round((float) get_booking_total_amount($b->fresh()), 2);
            $firstPay = round($grand * 0.5, 2);
            $b = $this->actions->applyScaledLossSettlement($b, $ctx['admin'], $firstPay, round(($grand - $firstPay) * 0.5, 2), round(($grand - $firstPay) * 0.5, 2));
            $remaining = round((float) get_booking_total_amount($b->fresh()) - (float) get_booking_total_paid($b->fresh()), 2);
            if ($remaining > 0.01) {
                $this->actions->providerRecordPayment($b, $remaining);
                $b = $b->fresh();
                $service = app(BookingFinancialSettlementService::class);
                $b->settlement_snapshot = $service->buildPreview($b);
                $b->save();
            }
            $created['loss_recovered'] = $b->fresh();

            // 17. Loss settled (write-off)
            $b = $this->placement->place($ctx['customer']->id, $ctx['service'], [
                'payment_method' => 'cash_after_service',
                'service_schedule' => $schedulePast,
                'provider_id' => $ctx['provider']->id,
            ], $tag('loss_settled'));
            $grand = round((float) get_booking_total_amount($b->fresh()), 2);
            $paid = round($grand * 0.4, 2);
            $loss = round($grand - $paid, 2);
            $b = $this->actions->applyScaledLossSettlement(
                $b,
                $ctx['admin'],
                $paid,
                round($loss * 0.5, 2),
                round($loss * 0.5, 2),
            );
            $created['loss_settled'] = $this->actions->adminWriteOffScaledLoss($b, $ctx['admin']);

            $this->seedCompensations($created, $ctx['admin']);
        });

        return $created;
    }

    /**
     * @param  array<string, Booking>  $created
     */
    private function seedCompensations(array $created, User $admin): void
    {
        $samples = [
            'completed' => [
                'from_party' => BookingCompensation::PARTY_COMPANY,
                'to_party' => BookingCompensation::PARTY_CUSTOMER,
                'amount' => 150.0,
                'reference_note' => 'Lifecycle demo — company compensated customer for service delay',
            ],
            'canceled' => [
                'from_party' => BookingCompensation::PARTY_PROVIDER,
                'to_party' => BookingCompensation::PARTY_CUSTOMER,
                'amount' => 75.0,
                'reference_note' => 'Lifecycle demo — provider goodwill after cancellation',
            ],
            'loss_settled' => [
                'from_party' => BookingCompensation::PARTY_COMPANY,
                'to_party' => BookingCompensation::PARTY_PROVIDER,
                'amount' => 120.0,
                'reference_note' => 'Lifecycle demo — company compensated provider for scaled loss share',
            ],
        ];

        foreach ($samples as $key => $sample) {
            $booking = $created[$key] ?? null;
            if (! $booking instanceof Booking) {
                continue;
            }

            BookingCompensation::query()->create([
                'booking_id' => $booking->id,
                'customer_id' => $booking->customer_id,
                'provider_id' => $booking->provider_id,
                'from_party' => $sample['from_party'],
                'to_party' => $sample['to_party'],
                'amount' => $sample['amount'],
                'transaction_id' => 'DEMO-COMP-' . strtoupper(substr($key, 0, 4)) . '-' . substr($booking->readable_id, -4),
                'reference_note' => $sample['reference_note'],
                'date' => now()->toDateString(),
                'created_by' => $admin->id,
            ]);
        }
    }

    public function wipe(): void
    {
        $pattern = self::TAG . '%';
        $ids = Booking::query()->where('service_description', 'like', $pattern)->pluck('id');
        if ($ids->isEmpty()) {
            return;
        }

        BookingReopenEvent::query()
            ->where(function ($q) use ($ids) {
                $q->whereIn('source_booking_id', $ids)->orWhereIn('child_booking_id', $ids);
            })
            ->delete();
        BookingPartialPayment::query()->whereIn('booking_id', $ids)->delete();
        BookingStatusHistory::query()->whereIn('booking_id', $ids)->delete();
        BookingDetail::query()->whereIn('booking_id', $ids)->delete();

        $service = app(AdminBookingDeletionService::class);
        Booking::query()
            ->whereIn('id', $ids)
            ->with([
                'repeat', 'detail', 'details_amounts', 'schedule_histories', 'status_histories',
                'booking_offline_payments', 'ignores', 'reviews', 'booking_partial_payments', 'extra_services',
                'repeat.detail', 'repeat.details_amounts', 'repeat.statusHistories',
                'repeat.scheduleHistories', 'repeat.repeatHistories',
            ])
            ->get()
            ->each(fn (Booking $b) => $service->deleteBookingAndRelations($b));
    }

    /**
     * @return array{
     *   customer: User,
     *   provider: Provider,
     *   provider_user: User,
     *   admin: User,
     *   service: array{service_id: string, category_id: string, sub_category_id: string, variant_key: string, zone_id: string, service_address_id: int},
     *   hold_reason_id: int,
     *   cancel_reason_id: int,
     *   dispute_reason_id: int,
     * }
     */
    public function resolveContext(?string $customerPhone = null, ?string $providerPhone = null): array
    {
        $customerPhone = $customerPhone ?: self::CUSTOMER_PHONE;
        $providerPhone = $providerPhone ?: self::PROVIDER_PHONE;

        $phoneDigits = User::normalizeContactPhoneDigits($customerPhone);

        // Same digits can exist on provider and customer rows; use the customer account only.
        $customer = User::ofType(CUSTOMER_USER_TYPES)
            ->whereRaw("REGEXP_REPLACE(COALESCE(phone, ''), '[^0-9]', '') LIKE ?", ['%' . $phoneDigits])
            ->orderByRaw("CASE WHEN phone LIKE '+91%' THEN 0 ELSE 1 END")
            ->first();

        if (! $customer) {
            throw new \RuntimeException('Customer app user not found for phone ' . $customerPhone);
        }

        $providerDigits = User::normalizeContactPhoneDigits($providerPhone);
        $providerUser = User::query()
            ->whereRaw("REGEXP_REPLACE(COALESCE(phone, ''), '[^0-9]', '') LIKE ?", ['%' . $providerDigits])
            ->whereIn('user_type', ['provider-admin'])
            ->first();
        if (! $providerUser) {
            throw new \RuntimeException('Provider user not found for phone ' . $providerPhone);
        }

        $provider = Provider::query()->where('user_id', $providerUser->id)->first();
        if (! $provider) {
            throw new \RuntimeException('Provider record not found for phone ' . $providerPhone);
        }

        $admin = User::query()
            ->whereIn('user_type', ['super-admin', 'admin-employee'])
            ->orderBy('id')
            ->first();
        if (! $admin) {
            throw new \RuntimeException('No admin user found');
        }

        $zoneIds = $provider->coveredLeafZoneIds();
        $zoneId = (string) ($zoneIds[0] ?? $provider->zone_id);
        if ($zoneId === '') {
            throw new \RuntimeException('Provider has no zone');
        }

        $subIds = DB::table('subscribed_services')
            ->where('provider_id', $provider->id)
            ->where('is_subscribed', 1)
            ->pluck('sub_category_id');

        $service = Service::query()
            ->whereIn('sub_category_id', $subIds)
            ->whereHas('variations', fn ($q) => $q->where('price', '>', 100))
            ->with(['variations' => fn ($q) => $q->where('price', '>', 100)->orderByDesc('price')])
            ->get()
            ->sortByDesc(fn (Service $s) => (float) ($s->variations->first()?->price ?? 0))
            ->first();
        if (! $service) {
            throw new \RuntimeException('No subscribed service with price > 100');
        }

        $variation = $service->variations
            ->sortByDesc(fn ($v) => (float) $v->price)
            ->first();
        if (! $variation || (float) $variation->price <= 100) {
            throw new \RuntimeException('Service variation missing');
        }

        $addressId = DB::table('user_addresses')->where('user_id', $customer->id)->value('id');
        if (! $addressId) {
            $addressId = DB::table('user_addresses')->insertGetId([
                'user_id' => $customer->id,
                'lat' => '34.0837',
                'lon' => '74.7973',
                'city' => 'Srinagar',
                'street' => 'Demo Street, Jawahar Nagar',
                'zip_code' => '190008',
                'country' => 'India',
                'address' => 'Demo Street, Jawahar Nagar, Srinagar',
                'landmark' => 'Near demo landmark',
                'address_type' => 'home',
                'contact_person_name' => trim($customer->first_name . ' ' . $customer->last_name),
                'contact_person_number' => $customer->phone,
                'address_label' => 'Home',
                'zone_id' => $zoneId,
                'is_guest' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
        DB::table('user_addresses')->where('id', $addressId)->update([
            'zone_id' => $zoneId,
            'lat' => '34.0837',
            'lon' => '74.7973',
            'contact_person_name' => trim($customer->first_name . ' ' . $customer->last_name),
            'contact_person_number' => $customer->phone,
        ]);

        $holdReasonId = (int) (DB::table('booking_hold_reopen_reasons')->where('is_active', 1)->value('id') ?? 0);
        $cancelReasonId = (int) (DB::table('booking_cancellation_reasons')->where('is_active', 1)->value('id') ?? 0);
        $disputeReasonId = (int) (DB::table('booking_dispute_reasons')->where('is_active', 1)->value('id') ?? 0);

        if ($holdReasonId < 1 || $cancelReasonId < 1 || $disputeReasonId < 1) {
            throw new \RuntimeException('Missing active hold/cancel/dispute reason rows');
        }

        return [
            'customer' => $customer,
            'provider' => $provider,
            'provider_user' => $providerUser,
            'admin' => $admin,
            'service' => [
                'service_id' => (string) $service->id,
                'category_id' => (string) $service->category_id,
                'sub_category_id' => (string) $service->sub_category_id,
                'variant_key' => (string) $variation->variant_key,
                'variation_id' => (string) $variation->id,
                'zone_id' => $zoneId,
                'service_address_id' => (int) $addressId,
            ],
            'hold_reason_id' => $holdReasonId,
            'cancel_reason_id' => $cancelReasonId,
            'dispute_reason_id' => $disputeReasonId,
        ];
    }

    /**
     * @return array<string, int>
     */
    public function providerTabCounts(Provider $provider): array
    {
        $max = business_config('max_booking_amount', 'booking_setup')->live_values;

        return [
            'all' => Booking::query()
                ->where('provider_id', $provider->id)
                ->orWhere(fn ($q) => $q->providerPendingBookings($provider, $max))
                ->count(),
            'pending' => Booking::providerPendingBookings($provider, $max)->count(),
            'accepted' => Booking::where('provider_id', $provider->id)->where('booking_status', 'accepted')->count(),
            'canceled' => Booking::where('provider_id', $provider->id)->whereIn('booking_status', ['canceled', 'cancelled'])->count(),
            'ongoing' => Booking::where('provider_id', $provider->id)->where('booking_status', 'ongoing')->count(),
            'completed' => Booking::where('provider_id', $provider->id)->where('booking_status', 'completed')->count(),
            'loss_making_pending' => Booking::where('provider_id', $provider->id)->lossMakingPending()->count(),
            'loss_recovered' => Booking::where('provider_id', $provider->id)->lossRecovered()->count(),
            'loss_settled' => Booking::where('provider_id', $provider->id)->lossSettled()->count(),
        ];
    }

    public function ledgerCountForMatrix(): int
    {
        $ids = Booking::query()->where('service_description', 'like', self::TAG . '%')->pluck('id');

        return LedgerTransaction::query()->whereIn('booking_id', $ids)->count();
    }

    private function ensureRecordedCustomerPayment(Booking $booking): Booking
    {
        $booking = $booking->fresh(['booking_partial_payments']);
        if (get_booking_total_paid($booking) > 0.009) {
            return $booking;
        }

        $amount = round((float) get_booking_total_amount($booking), 2);
        if ($amount < 0.01) {
            throw new \RuntimeException('Cannot ensure payment on zero-amount booking ' . $booking->readable_id);
        }

        $this->actions->providerRecordPayment($booking, $amount);

        return $booking->fresh(['booking_partial_payments']);
    }

    /**
     * Customer-retained portion for disputed-and-completed: always leaves a positive refund when paid > 0.
     */
    private function resolveDisputedPartialRetain(float $paid): float
    {
        if ($paid < 0.01) {
            return 0.0;
        }

        return round(min($paid - 1.0, max($paid * 0.4, min(200.0, $paid * 0.6))), 2);
    }

    private function suppressOutboundMessaging(): void
    {
        config(['queue.default' => 'sync']);

        foreach (['booking_notification', 'booking_notification_type'] as $key) {
            try {
                $row = DB::table('business_settings')
                    ->where('key_name', $key)
                    ->where('settings_type', 'business_information')
                    ->first();
                if ($row) {
                    DB::table('business_settings')
                        ->where('id', $row->id)
                        ->update(['live_values' => $key === 'booking_notification' ? '0' : 'none']);
                }
            } catch (\Throwable) {
            }
        }

        try {
            DB::table('business_settings')
                ->where('key_name', 'like', 'whatsapp%')
                ->where('settings_type', 'like', '%whatsapp%')
                ->update(['live_values' => '0']);
        } catch (\Throwable) {
        }
    }
}
