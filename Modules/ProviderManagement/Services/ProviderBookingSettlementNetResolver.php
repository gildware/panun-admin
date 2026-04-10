<?php

namespace Modules\ProviderManagement\Services;

use Illuminate\Support\Facades\DB;
use Modules\BookingModule\Entities\Booking;
use Modules\BookingModule\Entities\BookingRepeat;

/**
 * Resolves booking settlement net (with manual ledger adjustments) for provider payment tab / WhatsApp context.
 */
class ProviderBookingSettlementNetResolver
{
    /**
     * @return array{booking_settlement_net: float, booking_settlement_net_before_ledger: float, ledger_manual_totals: array<string, float>}
     */
    public function resolveForProviderId(string $providerId): array
    {
        $providerBookingIds = DB::table('bookings')->where('provider_id', $providerId)->pluck('id')->toArray();
        $bookingIdsWithRepeats = DB::table('booking_repeats')->whereNotNull('booking_id')->distinct()->pluck('booking_id')->toArray();

        $oneTimeQuery = DB::table('bookings')->where('provider_id', $providerId)->where(function ($q) {
            provider_payment_tab_one_time_revenue_bookings_inner($q);
        });
        if (!empty($bookingIdsWithRepeats)) {
            $oneTimeQuery->whereNotIn('id', $bookingIdsWithRepeats);
        }
        $completedOneTimeBookingIds = $oneTimeQuery->pluck('id');

        $oneTimeSettlementById = $completedOneTimeBookingIds->isEmpty()
            ? collect()
            : Booking::whereIn('id', $completedOneTimeBookingIds)->pluck('settlement_outcome', 'id');

        $specialOneTimeIds = $completedOneTimeBookingIds->filter(function ($bid) use ($oneTimeSettlementById) {
            return trim((string) $oneTimeSettlementById->get($bid)) !== '';
        })->values();
        $normalOneTimeIds = $completedOneTimeBookingIds->diff($specialOneTimeIds)->values();

        $completedRepeatIds = collect();
        if (!empty($providerBookingIds)) {
            $completedRepeatIds = DB::table('booking_repeats')->where('booking_status', 'completed')->whereIn('booking_id', $providerBookingIds)->pluck('id');
        }

        $normalRepeatIds = collect();
        $specialRepeatIds = collect();
        if ($completedRepeatIds->isNotEmpty()) {
            foreach (BookingRepeat::whereIn('id', $completedRepeatIds)->with('booking')->get() as $repeatRow) {
                if (trim((string) ($repeatRow->booking->settlement_outcome ?? '')) !== '') {
                    $specialRepeatIds->push($repeatRow->id);
                } else {
                    $normalRepeatIds->push($repeatRow->id);
                }
            }
        }

        $oneTimeBookingsForRevenue = Booking::whereIn('id', $completedOneTimeBookingIds)->with('extra_services')->get();
        $repeatsForRevenue = $completedRepeatIds->isNotEmpty()
            ? BookingRepeat::whereIn('id', $completedRepeatIds)->with('booking.extra_services')->get()
            : collect();

        $bookingSettlementAggregate = aggregate_provider_booking_settlement_net_for_completed_jobs($oneTimeBookingsForRevenue, $repeatsForRevenue);
        $ledgerManualTotals = provider_ledger_manual_flow_totals_for_provider($providerId);
        $bookingSettlementNetBeforeLedger = (float) $bookingSettlementAggregate['settlement_net'];
        $bookingSettlementNet = round(
            $bookingSettlementNetBeforeLedger - $ledgerManualTotals['payout_out_total'] + $ledgerManualTotals['collect_in_total'],
            2
        );

        return [
            'booking_settlement_net' => $bookingSettlementNet,
            'booking_settlement_net_before_ledger' => $bookingSettlementNetBeforeLedger,
            'ledger_manual_totals' => $ledgerManualTotals,
        ];
    }
}
