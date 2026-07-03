<?php

namespace Modules\AdminModule\Listeners;

use Modules\AdminModule\Entities\UserNotification;
use Modules\AdminModule\Services\AdminInboxNotificationService;
use Modules\BookingModule\Entities\Booking;
use Modules\BookingModule\Events\ProviderWithdrewFromBooking;
use Modules\ProviderManagement\Entities\Provider;

class CreateAdminProviderWithdrawalNotification
{
    public function __construct(
        protected AdminInboxNotificationService $notificationService,
    ) {}

    public function handle(ProviderWithdrewFromBooking $event): void
    {
        $booking = $event->booking->loadMissing([
            'customer',
            'latestParentProviderCancellationStatusHistory.providerCancellationReason',
        ]);

        if (! $booking instanceof Booking) {
            return;
        }

        $provider = Provider::query()->find($event->providerId);
        $readableId = $booking->readable_id ?? $booking->id;
        $providerName = $provider?->company_name ?: translate('Provider');
        $reason = $booking->latestParentProviderCancellationStatusHistory?->providerCancellationReason?->name;

        $body = $providerName . ' ' . translate('Provider_withdrew_from_booking_admin_body') . ' #' . $readableId;

        if ($reason) {
            $body .= '. ' . translate('Reason') . ': ' . $reason;
        }

        $this->notificationService->notifyAllAdmins(
            type: UserNotification::TYPE_PROVIDER_WITHDRAWAL,
            title: translate('Provider_withdrew_from_booking_admin_title') . ' #' . $readableId,
            body: $body,
            actionUrl: route('admin.booking.details', ['id' => $booking->id, 'web_page' => 'details']),
            referenceType: 'provider',
            referenceId: (string) $event->providerId,
        );
    }
}
