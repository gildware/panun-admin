<?php

namespace Modules\CallCenterModule\Transformers;

use Modules\BookingModule\Entities\Booking;

class BookingTransformer
{
    public function transform(Booking $booking): array
    {
        $booking->loadMissing(['service_address', 'category', 'subCategory', 'detail.service']);

        $firstDetail = $booking->detail->first();
        $serviceName = $booking->subCategory?->name
            ?? $booking->category?->name
            ?? $firstDetail?->service?->name
            ?? 'Service';

        return [
            'id' => $booking->id,
            'booking_ref' => $booking->readable_id,
            'status' => $this->mapStatus($booking->booking_status),
            'service_type' => $serviceName,
            'scheduled_at' => $booking->service_schedule
                ? $booking->service_schedule->utc()->toIso8601String()
                : null,
            'address' => $booking->service_address?->address,
            'amount' => (float) ($booking->total_booking_amount ?? 0),
            'currency' => 'INR',
            'notes' => $booking->service_description ?? null,
            'created_at' => $booking->created_at?->utc()->toIso8601String(),
            'updated_at' => $booking->updated_at?->utc()->toIso8601String(),
        ];
    }

    private function mapStatus(?string $status): string
    {
        $status = strtolower((string) $status);
        if ($status === 'cancelled') {
            return 'cancelled';
        }
        if ($status === 'canceled') {
            return 'cancelled';
        }
        if ($status === 'accepted') {
            return 'confirmed';
        }
        if (in_array($status, ['pending', 'ongoing', 'on_hold'], true)) {
            return 'active';
        }

        return $status;
    }
}
