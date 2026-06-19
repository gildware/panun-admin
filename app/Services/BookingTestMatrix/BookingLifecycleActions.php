<?php

namespace App\Services\BookingTestMatrix;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Modules\BookingModule\Entities\Booking;
use Modules\BookingModule\Entities\BookingStatusHistory;
use Modules\BookingModule\Http\Controllers\Web\Admin\BookingController as AdminBookingController;
use Modules\BookingModule\Services\BookingFinancialSettlementService;
use Modules\BookingModule\Services\BookingReopenService;
use Modules\ProviderManagement\Entities\Provider;
use Modules\UserManagement\Entities\User;

/**
 * Provider / admin lifecycle steps mirroring API and admin web controllers.
 */
class BookingLifecycleActions
{
    public function providerAccept(Booking $booking, User $providerUser, Provider $provider): Booking
    {
        return DB::transaction(function () use ($booking, $providerUser, $provider) {
            $booking = $booking->fresh();
            $booking->provider_id = $provider->id;
            $booking->booking_status = 'accepted';
            $booking->save();

            $history = new BookingStatusHistory;
            $history->booking_id = $booking->id;
            $history->changed_by = $providerUser->id;
            $history->booking_status = 'accepted';
            $history->save();

            return $booking->fresh();
        });
    }

    public function providerStatus(
        Booking $booking,
        User $providerUser,
        string $status,
        ?int $holdReasonId = null,
        bool $paymentReceivedConfirmed = false,
    ): Booking {
        $booking = $booking->fresh();
        if ($status === 'ongoing') {
            $booking->service_schedule = now()->subHour();
            $booking->save();
        }

        $booking->booking_status = $status;
        if ($status === 'completed' && $paymentReceivedConfirmed) {
            $booking->provider_payment_confirmed_at = now();
        }
        $booking->save();

        $history = new BookingStatusHistory;
        $history->booking_id = $booking->id;
        $history->changed_by = $providerUser->id;
        $history->booking_status = $status;
        $history->booking_hold_reopen_reason_id = $holdReasonId;
        $history->save();

        return $booking->fresh();
    }

    public function providerRecordPayment(Booking $booking, float $amount): void
    {
        record_provider_booking_customer_payment($booking->fresh(['booking_partial_payments']), $amount);
    }

    public function customerCancel(Booking $booking, User $customer): Booking
    {
        $booking = $booking->fresh();
        $booking->booking_status = 'canceled';
        $booking->save();

        $history = new BookingStatusHistory;
        $history->booking_id = $booking->id;
        $history->changed_by = $customer->id;
        $history->booking_status = 'canceled';
        $history->save();

        return $booking->fresh();
    }

    public function completeCasWithProviderPayment(Booking $booking, User $providerUser): Booking
    {
        $booking = $this->prepareCasForOnSiteProviderCollection($booking->fresh(['booking_partial_payments']));
        $due = round((float) get_booking_total_amount($booking) - (float) get_booking_total_paid($booking), 2);
        if ($due > 0.009) {
            $this->providerRecordPayment($booking, $due);
        }

        return $this->providerStatus($booking, $providerUser, 'completed', null, true);
    }

    public function reopenInPlace(Booking $booking, User $admin, string $targetStatus = 'accepted'): Booking
    {
        app(BookingReopenService::class)->reopenInPlace(
            $booking->fresh(),
            $admin,
            'Lifecycle test matrix reopen',
            $targetStatus,
        );

        return $booking->fresh();
    }

    public function resolveReopen(Booking $booking, User $admin): Booking
    {
        $booking = $booking->fresh();
        $booking->reopen_resolved_at = now();
        $booking->reopen_resolved_by = $admin->id;
        $booking->reopen_resolve_remarks = 'Lifecycle test matrix resolved';
        $booking->save();

        return $booking->fresh();
    }

    public function adminRecordPayment(Booking $booking, User $admin, float $amount, string $receivedBy = 'company'): void
    {
        Gate::before(static fn () => true);
        Auth::guard('web')->login($admin);

        $payload = [
            'amount' => round($amount, 2),
            'received_by' => $receivedBy,
            'date' => now()->toDateString(),
        ];

        if ($receivedBy === 'company') {
            $allowed = \Modules\BookingModule\Services\AdminCompanyInflowPaymentService::allowedAdvanceMethodKeys();
            $payload['advance_payment_method'] = $allowed[0] ?? 'cash_after_service';
            $payload['advance_transaction_id'] = 'TEST-ADM-' . substr($booking->readable_id, -6);
        }

        $request = Request::create('/admin/booking/add-payment/' . $booking->id, 'POST', $payload);
        $request->headers->set('Accept', 'application/json');
        $request->setUserResolver(static fn () => $admin);

        $response = app(AdminBookingController::class)->addPayment($request, $booking->id);
        if ($response->getStatusCode() >= 400) {
            throw new \RuntimeException('addPayment failed: ' . $response->getContent());
        }
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function adminFinancialSaveAndComplete(Booking $booking, User $admin, array $payload): Booking
    {
        Gate::before(static fn () => true);
        Auth::guard('web')->login($admin);

        $request = Request::create(
            '/admin/booking/financial-settlement/' . $booking->id . '/save-and-complete',
            'POST',
            $payload,
        );
        $request->setUserResolver(static fn () => $admin);

        $response = app(AdminBookingController::class)->financialSettlementSaveAndComplete($request, $booking->id);
        if ($response->getStatusCode() >= 400) {
            throw new \RuntimeException('financialSettlementSaveAndComplete failed: ' . $response->getContent());
        }

        return $booking->fresh();
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function adminFinancialSaveAndCancel(Booking $booking, User $admin, array $payload): Booking
    {
        Gate::before(static fn () => true);
        Auth::guard('web')->login($admin);

        $request = Request::create(
            '/admin/booking/financial-settlement/' . $booking->id . '/save-and-cancel',
            'POST',
            $payload,
        );
        $request->setUserResolver(static fn () => $admin);

        $response = app(AdminBookingController::class)->financialSettlementSaveAndCancel($request, $booking->id);
        if ($response->getStatusCode() >= 400) {
            throw new \RuntimeException('financialSettlementSaveAndCancel failed: ' . $response->getContent());
        }

        return $booking->fresh();
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function adminDisputedRefund(Booking $booking, User $admin, array $payload): Booking
    {
        Gate::before(static fn () => true);
        Auth::guard('web')->login($admin);

        $request = Request::create(
            '/admin/booking/reopen-scenario/disputed-refund/' . $booking->id,
            'POST',
            $payload,
        );
        $request->setUserResolver(static fn () => $admin);

        app(AdminBookingController::class)->reopenScenarioDisputedRefund($request, $booking->id);

        return $booking->fresh();
    }

    public function adminWriteOffScaledLoss(Booking $booking, User $admin): Booking
    {
        Gate::before(static fn () => true);
        Auth::guard('web')->login($admin);

        $request = Request::create('/admin/booking/loss-writeoff/' . $booking->id, 'POST');
        $request->setUserResolver(static fn () => $admin);

        app(AdminBookingController::class)->writeOffScaledLoss($request, $booking->id);

        $booking = $booking->fresh();
        $booking->settlement_snapshot = app(BookingFinancialSettlementService::class)->buildPreview($booking);
        $booking->save();

        return $booking->fresh();
    }

    public function advanceToOngoing(Booking $booking, User $providerUser): Booking
    {
        if ($booking->provider_id === null) {
            throw new \RuntimeException('Booking must be accepted before ongoing');
        }

        $booking = $this->prepareCasForOnSiteProviderCollection($booking);
        $booking->service_schedule = now()->subHour();
        $booking->save();

        if ($booking->booking_status === 'pending') {
            $booking = $this->providerStatus($booking, $providerUser, 'accepted');
        }

        return $this->providerStatus($booking, $providerUser, 'ongoing');
    }

    /**
     * Checkout records a company partial for CAS even though collection happens on site.
     * Clear that so provider payment flows behave like real field collection.
     */
    public function prepareCasForOnSiteProviderCollection(Booking $booking): Booking
    {
        $booking = $booking->fresh(['booking_partial_payments']);
        if (strtolower((string) $booking->payment_method) !== 'cash_after_service') {
            return $booking;
        }

        if ($booking->booking_partial_payments->isNotEmpty()) {
            $booking->booking_partial_payments()->delete();
        }

        $booking->is_paid = 0;
        $booking->provider_payment_confirmed_at = null;
        $booking->save();

        return $booking->fresh(['booking_partial_payments']);
    }

    public function applyScaledLossSettlement(Booking $booking, User $admin, float $declaredPaid, float $lossCompany, float $lossProvider, ?float $writeoff = null): Booking
    {
        $booking = $this->advanceToOngoing($booking, User::findOrFail(Provider::findOrFail($booking->provider_id)->user_id));

        $paid = round((float) get_booking_total_paid($booking->fresh()), 2);
        if ($paid < $declaredPaid - 0.02) {
            $this->providerRecordPayment($booking, round($declaredPaid - $paid, 2));
        }

        $config = [
            'scaled_customer_paid_amount' => $declaredPaid,
            'scaled_loss_company_amount' => $lossCompany,
            'scaled_loss_provider_amount' => $lossProvider,
        ];
        if ($writeoff !== null) {
            $config['scaled_loss_writeoff_amount'] = $writeoff;
        }

        return $this->adminFinancialSaveAndComplete($booking, $admin, [
            'settlement_outcome' => BookingFinancialSettlementService::OUTCOME_SCALED_TO_PAYMENTS,
            'settlement_remarks' => 'Lifecycle test matrix loss settlement',
            ...$config,
        ]);
    }
}
