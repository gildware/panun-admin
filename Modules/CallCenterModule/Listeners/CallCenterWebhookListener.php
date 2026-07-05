<?php

namespace Modules\CallCenterModule\Listeners;

use Modules\BookingModule\Entities\Booking;
use Modules\CallCenterModule\Entities\CustomerProfile;
use Modules\CallCenterModule\Services\CallCenterWebhookDispatcher;
use Modules\CallCenterModule\Services\CustomerProfileService;
use Modules\ProviderManagement\Entities\CustomerIncident;
use Modules\UserManagement\Entities\User;

class CallCenterWebhookListener
{
    public function __construct(
        private readonly CallCenterWebhookDispatcher $webhooks,
        private readonly CustomerProfileService $profiles,
    ) {
    }

    public function handleUserCreated(User $user): void
    {
        if ($this->shouldSkipWebhook() || !$this->isCustomerUser($user)) {
            return;
        }

        try {
            $profile = $this->profiles->getProfileForUser($user);
            $this->webhooks->dispatch('customer.created', $profile->id, [
                'customer_ref' => $profile->customer_ref,
                'phone' => $user->phone,
            ]);
        } catch (\Throwable $e) {
            report($e);
        }
    }

    public function handleUserUpdated(User $user): void
    {
        if ($this->shouldSkipWebhook() || !$this->isCustomerUser($user)) {
            return;
        }

        $profileId = CustomerProfile::query()->where('user_id', $user->id)->value('id');
        if (!$profileId) {
            return;
        }

        $this->webhooks->dispatch('customer.updated', (int) $profileId, [
            'phone' => $user->phone,
            'email' => $user->email,
        ]);
    }

    public function handleBookingCreated(Booking $booking): void
    {
        if ($this->shouldSkipWebhook() || !$booking->customer_id) {
            return;
        }

        $profileId = $this->webhooks->resolveCustomerProfileIdForUser($booking->customer_id);
        if (!$profileId) {
            return;
        }

        $this->webhooks->dispatch('booking.created', $profileId, [
            'booking_id' => $booking->id,
            'booking_ref' => $booking->readable_id,
            'status' => $booking->booking_status,
        ]);
    }

    public function handleBookingUpdated(Booking $booking): void
    {
        if ($this->shouldSkipWebhook() || !$booking->customer_id) {
            return;
        }

        $profileId = $this->webhooks->resolveCustomerProfileIdForUser($booking->customer_id);
        if (!$profileId) {
            return;
        }

        $payload = [
            'booking_id' => $booking->id,
            'booking_ref' => $booking->readable_id,
            'status' => $booking->booking_status,
        ];

        $this->webhooks->dispatch('booking.updated', $profileId, $payload);

        if ($booking->wasChanged('booking_status')) {
            $this->webhooks->dispatch('order.updated', $profileId, [
                'order_id' => $booking->id,
                'order_ref' => $booking->readable_id,
                'status' => $booking->booking_status,
            ]);
        }
    }

    public function handleComplaintCreated(CustomerIncident $incident): void
    {
        if ($this->shouldSkipWebhook() || $incident->incident_type !== 'COMPLAINT' || !$incident->customer_id) {
            return;
        }

        $profileId = $this->webhooks->resolveCustomerProfileIdForUser($incident->customer_id);
        if (!$profileId) {
            return;
        }

        $this->webhooks->dispatch('complaint.created', $profileId, [
            'incident_id' => $incident->id,
            'booking_id' => $incident->booking_id,
        ]);
    }

    private function shouldSkipWebhook(): bool
    {
        if (app()->runningInConsole()) {
            return false;
        }

        if (!request()->is('api/v1/*')) {
            return false;
        }

        $provided = request()->header('X-API-Key') ?: request()->bearerToken();
        $expected = config('services.call_center.api_key');

        return $expected && $provided && hash_equals($expected, (string) $provided);
    }

    private function isCustomerUser(User $user): bool
    {
        return in_array($user->user_type, CUSTOMER_USER_TYPES, true);
    }
}
