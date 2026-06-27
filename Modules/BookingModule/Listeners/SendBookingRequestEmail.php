<?php

namespace Modules\BookingModule\Listeners;

use Illuminate\Support\Facades\Mail;
use Modules\BookingModule\Emails\BookingMail;
use Modules\BookingModule\Events\BookingRequested;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;

class SendBookingRequestEmail
{
    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     *
     * @param BookingRequested $event
     * @return void
     */
    public function handle(BookingRequested $event)
    {
        try {
            $email = isNotificationActive(null, 'booking', 'email', 'user');
            $emailServices =  business_config('email_config_status', 'email_config');

            if (isset($event->booking->customer->email) && isset($emailServices) && $emailServices->live_values == 1 && $email) {
                Mail::to($event->booking->customer->email)->send(new BookingMail($event->booking));
            }
        } catch (\Exception $exception) {
            info($exception);
        }

        $notification= isNotificationActive(null, 'booking', 'notification', 'user');
        $config = business_config('booking', 'notification_settings');
        if ($config->live_values['push_notification_booking']) {
            $repeatOrRegular = $event->booking?->is_repeated ? 'repeat' : 'regular';
            $title = get_push_notification_message('booking_place', 'customer_notification', $event->booking?->customer?->current_language_key);
            $description = get_push_notification_description('booking_place', 'customer_notification', $event->booking?->customer?->current_language_key);
            $customer = $event->booking->customer;
            if ($customer && $title && $notification && $customer->is_active) {
                scenario_push_notification(
                    $customer->fcm_token,
                    $title,
                    $description,
                    $event->booking->id,
                    'booking',
                    $customer->id,
                    null,
                    $repeatOrRegular,
                    'pending',
                    'customer',
                    $event->booking->zone_id
                );
            }
        }

        if ($event->booking->provider_id && function_exists('send_booking_new_service_request_to_assigned_provider')) {
            send_booking_new_service_request_to_assigned_provider($event->booking);
        }
    }
}
