<?php

namespace Modules\AdminModule\Listeners;

use Modules\AdminModule\Services\AdminInboxNotificationService;
use Modules\BookingModule\Entities\Booking;
use Modules\BookingModule\Events\BookingRequested;

class CreateAdminBookingNotification
{
    public function __construct(
        protected AdminInboxNotificationService $notificationService,
    ) {}

    public function handle(BookingRequested $event): void
    {
        $booking = $event->booking->loadMissing('customer');
        if (!$booking instanceof Booking) {
            return;
        }

        $readableId = $booking->readable_id ?? $booking->id;
        $customerName = trim(($booking->customer?->first_name ?? '') . ' ' . ($booking->customer?->last_name ?? ''));

        $this->notificationService->notifyAllAdmins(
            type: \Modules\AdminModule\Entities\UserNotification::TYPE_BOOKING,
            title: translate('New_Booking') . ' #' . $readableId,
            body: $customerName !== ''
                ? translate('A_new_booking_has_been_placed_by') . ' ' . $customerName
                : translate('You_have_new_booking_arrived'),
            actionUrl: route('admin.booking.details', ['id' => $booking->id]),
            referenceType: 'booking',
            referenceId: (string) $booking->id,
        );
    }
}
