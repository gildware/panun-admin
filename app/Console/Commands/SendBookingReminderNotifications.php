<?php

namespace App\Console\Commands;

use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Modules\BookingModule\Entities\Booking;
use Modules\BookingModule\Entities\BookingRepeat;

class SendBookingReminderNotifications extends Command
{
    protected $signature = 'notifications:send-booking-reminders';

    protected $description = 'Send push reminders to customers and providers before upcoming booking service times';

    public function handle(): int
    {
        $minutesBefore = (int) (business_config('booking_reminder_minutes_before', 'notification_settings')?->live_values ?? 60);
        if ($minutesBefore < 1) {
            $minutesBefore = 60;
        }

        $windowStart = now()->addMinutes($minutesBefore - 5);
        $windowEnd = now()->addMinutes($minutesBefore + 5);

        $this->sendForBookings($windowStart, $windowEnd);
        $this->sendForRepeats($windowStart, $windowEnd);

        return self::SUCCESS;
    }

    private function sendForBookings(Carbon $windowStart, Carbon $windowEnd): void
    {
        Booking::query()
            ->with('customer')
            ->where(function ($query) {
                $query->where('is_repeated', 0)->orWhereNull('is_repeated');
            })
            ->whereIn('booking_status', ['accepted', 'ongoing'])
            ->whereNotNull('service_schedule')
            ->whereBetween('service_schedule', [$windowStart, $windowEnd])
            ->orderBy('id')
            ->chunkById(100, function ($bookings) {
                foreach ($bookings as $booking) {
                    $this->sendReminderOnce('booking', (string) $booking->id, function () use ($booking) {
                        send_booking_reminder_notification($booking);
                    });
                }
            });
    }

    private function sendForRepeats(Carbon $windowStart, Carbon $windowEnd): void
    {
        BookingRepeat::query()
            ->with(['booking.customer', 'booking.provider.owner'])
            ->whereIn('booking_status', ['accepted', 'ongoing'])
            ->whereNotNull('service_schedule')
            ->whereBetween('service_schedule', [$windowStart, $windowEnd])
            ->orderBy('id')
            ->chunkById(100, function ($repeats) {
                foreach ($repeats as $repeat) {
                    $booking = $repeat->booking;
                    if (! $booking) {
                        continue;
                    }

                    $this->sendReminderOnce('repeat', (string) $repeat->id, function () use ($booking, $repeat) {
                        send_booking_reminder_notification($booking, $repeat);
                    });
                }
            });
    }

    private function sendReminderOnce(string $type, string $id, callable $sender): void
    {
        $cacheKey = "booking_reminder_sent:{$type}:{$id}";

        if (! Cache::add($cacheKey, true, now()->addDays(3))) {
            return;
        }

        $sender();
    }
}
